<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SmsOutbox extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_SENT = 'sent';
    public const STATUS_FAILED = 'failed';

    public const PURPOSE_BILL = 'bill';

    /**
     * A row still pending with NO recorded attempt after this many seconds
     * means nothing is consuming the queue (the worker is down). There is no
     * point spinning — surface it as failed.
     *
     * On the live server the queue is drained by cron (`schedule:run` /
     * `queue:work` once a minute), so a perfectly healthy job can sit
     * untouched for up to ~60s before its first attempt is recorded. This
     * window must stay comfortably above that worst-case pickup delay or
     * bills that DO send get declared failed while still queued.
     */
    public const WORKER_STALL_SECONDS = 150;

    /**
     * Absolute ceiling: any row still pending this long (even mid-retry) is
     * treated as failed so the cashier is never left waiting indefinitely.
     *
     * Sized for the full retry chain under a cron-driven queue: pickup (~60s)
     * + 3 attempts separated by the job's 30s/120s backoff, each retry waiting
     * for the next cron tick (~350s worst case). Anything past this really is
     * stuck.
     */
    public const HARD_TIMEOUT_SECONDS = 420;

    protected $table = 'sms_outbox';

    // The table tracks created_at + sent_at only (no updated_at column)
    public const UPDATED_AT = null;

    protected $fillable = [
        'invoice_id',
        'purpose',
        'to_number',
        'body',
        'status',
        'attempts',
        'provider_message_id',
        'provider_response',
        'error',
        'sent_at',
    ];

    protected $casts = [
        'provider_response' => 'array',
        'sent_at' => 'datetime',
        'attempts' => 'integer',
    ];

    public function sale()
    {
        return $this->belongsTo(Sale::class, 'invoice_id');
    }

    /**
     * Whether this pending row has been waiting long enough that we should
     * stop showing "Sending…" and call it failed.
     *
     * - No attempt recorded + WORKER_STALL_SECONDS elapsed → queue worker is
     *   almost certainly not running (nothing ever picked the job up).
     * - Any pending row past HARD_TIMEOUT_SECONDS → give up regardless.
     */
    public function isTimedOut(): bool
    {
        if ($this->status !== self::STATUS_PENDING) {
            return false;
        }

        // Plain timestamp arithmetic — unambiguous across Carbon versions.
        $age = $this->created_at === null
            ? PHP_INT_MAX
            : now()->getTimestamp() - $this->created_at->getTimestamp();

        if ($this->attempts < 1 && $age >= self::WORKER_STALL_SECONDS) {
            return true;
        }

        return $age >= self::HARD_TIMEOUT_SECONDS;
    }

    /**
     * Flip a timed-out pending row to failed with a clear, cashier-readable
     * reason. Returns true if it changed state. Concurrency-safe: the WHERE
     * clause ensures a job that finishes at the same instant still wins.
     */
    public function resolveIfTimedOut(): bool
    {
        if (!$this->isTimedOut()) {
            return false;
        }

        $reason = $this->attempts < 1
            ? 'The SMS service did not pick up this message (the SMS worker may not be running). Please retry or notify support.'
            : "The SMS service did not respond after {$this->attempts} attempt(s). Please retry.";

        $changed = static::where('id', $this->id)
            ->where('status', self::STATUS_PENDING)
            ->update([
                'status' => self::STATUS_FAILED,
                'error' => $reason,
            ]);

        if ($changed) {
            $this->status = self::STATUS_FAILED;
            $this->error = $reason;
        }

        return (bool) $changed;
    }
}
