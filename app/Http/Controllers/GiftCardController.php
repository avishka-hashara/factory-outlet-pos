<?php

namespace App\Http\Controllers;

use App\Models\GiftCard;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class GiftCardController extends Controller
{
    /**
     * Sidebar dashboard: counts, list + search with live computed status.
     */
    public function index(Request $request)
    {
        $counts = [
            'total' => GiftCard::count(),
            'available' => GiftCard::where('status', GiftCard::STATUS_AVAILABLE)->count(),
            'sold' => GiftCard::where('status', GiftCard::STATUS_SOLD)->count(),
            'gifted' => GiftCard::where('status', GiftCard::STATUS_GIFTED)->count(),
            'redeemed' => GiftCard::where('status', GiftCard::STATUS_REDEEMED)->count(),
        ];

        $query = GiftCard::with(['issuedSale', 'redeemedSale'])->latest('id');

        if ($request->filled('search')) {
            $s = trim($request->search);
            $query->where(function ($q) use ($s) {
                $q->where('card_number', 'like', "%{$s}%")
                    ->orWhere('barcode', 'like', "%{$s}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $cards = $query->paginate(15)->withQueryString();

        return view('gift-cards.index', compact('counts', 'cards'));
    }

    /**
     * Add a single card. The barcode field is filled by scanning with a
     * barcode reader (keyboard-wedge input).
     */
    public function store(Request $request)
    {
        $request->validate([
            'card_number' => 'required|string|max:100|unique:gift_cards,card_number',
            'barcode' => 'required|string|max:100|unique:gift_cards,barcode',
            'value' => ['required', 'regex:/^\d{1,10}(\.\d{1,2})?$/', 'not_in:0,0.0,0.00'],
            'issued_date' => 'nullable|date_format:Y-m-d',
            'expiry_date' => 'nullable|date_format:Y-m-d',
        ], [
            'value.regex' => 'Card value must be a positive amount with up to 2 decimals.',
            'value.not_in' => 'Card value must be greater than zero.',
        ]);

        // Barcodes and card numbers must also be unique across each other's
        // columns, otherwise POS lookup by code becomes ambiguous.
        $cardNumber = trim($request->card_number);
        $barcode = trim($request->barcode);
        if (GiftCard::where('card_number', $barcode)->orWhere('barcode', $cardNumber)->exists()) {
            return back()->withInput()->with('error', 'Card number or barcode collides with an existing card.');
        }

        GiftCard::create([
            'card_number' => $cardNumber,
            'barcode' => $barcode,
            'value' => $request->value,
            'issued_date' => $request->issued_date ?: null,
            'expiry_date' => $request->expiry_date ?: null,
            'status' => GiftCard::STATUS_AVAILABLE,
        ]);

        return back()->with('success', "Gift card {$cardNumber} added.");
    }

    /**
     * Downloadable CSV template with the correct headers.
     */
    public function template()
    {
        $csv = "card_number,barcode,value,issued_date,expiry_date\r\n"
            ."GC10000001,4791234567890,5000,2026-07-18,2027-07-18\r\n"
            ."GC10000002,4791234567891,10000,,\r\n";

        return response($csv, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="gift_cards_template.csv"',
        ]);
    }

    /**
     * Bulk import from the CSV template. Every row is validated; valid rows
     * are imported inside one transaction and rejected rows are reported with
     * reasons. Nothing is ever half-imported silently.
     */
    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt|max:5120',
        ], [
            'file.mimes' => 'Please upload a CSV file saved from the provided template (Excel: File → Save As → CSV).',
        ]);

        $handle = fopen($request->file('file')->getRealPath(), 'r');
        if ($handle === false) {
            return back()->with('error', 'Could not read the uploaded file.');
        }

        $header = fgetcsv($handle);
        if ($header === false) {
            fclose($handle);

            return back()->with('error', 'The uploaded file is empty.');
        }

        // Strip UTF-8 BOM (Excel adds it) and normalise header names.
        $header[0] = preg_replace('/^\xEF\xBB\xBF/', '', (string) $header[0]);
        $columns = array_map(fn ($h) => strtolower(trim((string) $h)), $header);
        $required = ['card_number', 'barcode', 'value'];
        $missing = array_diff($required, $columns);
        if ($missing) {
            fclose($handle);

            return back()->with('error', 'Missing required column(s): '.implode(', ', $missing).'. Use the provided template.');
        }
        $pos = array_flip($columns);

        $valid = [];
        $rejected = [];
        $seenNumbers = [];
        $seenBarcodes = [];
        $rowNum = 1;
        $now = now();

        while (($row = fgetcsv($handle)) !== false) {
            $rowNum++;
            // Skip fully blank lines
            if (count(array_filter($row, fn ($c) => trim((string) $c) !== '')) === 0) {
                continue;
            }

            $get = fn (string $col) => isset($pos[$col], $row[$pos[$col]]) ? trim((string) $row[$pos[$col]]) : '';

            $cardNumber = $get('card_number');
            $barcode = $get('barcode');
            $value = $get('value');
            $issuedRaw = $get('issued_date');
            $expiryRaw = $get('expiry_date');

            $reasons = [];

            if ($cardNumber === '') {
                $reasons[] = 'missing card_number';
            }
            if ($barcode === '') {
                $reasons[] = 'missing barcode';
            }
            if ($value === '') {
                $reasons[] = 'missing value';
            } elseif (! preg_match('/^\d{1,10}(\.\d{1,2})?$/', $value)) {
                $reasons[] = "non-numeric value \"{$value}\"";
            } elseif (bccomp($value, '0', 2) !== 1) {
                $reasons[] = 'value must be greater than zero';
            }

            $issuedDate = null;
            if ($issuedRaw !== '') {
                $issuedDate = $this->parseImportDate($issuedRaw);
                if ($issuedDate === null) {
                    $reasons[] = "bad issued_date format \"{$issuedRaw}\" (use YYYY-MM-DD)";
                }
            }
            $expiryDate = null;
            if ($expiryRaw !== '') {
                $expiryDate = $this->parseImportDate($expiryRaw);
                if ($expiryDate === null) {
                    $reasons[] = "bad expiry_date format \"{$expiryRaw}\" (use YYYY-MM-DD)";
                }
            }

            if ($cardNumber !== '' && isset($seenNumbers[$cardNumber])) {
                $reasons[] = "duplicate card_number within file (first at row {$seenNumbers[$cardNumber]})";
            }
            if ($barcode !== '' && isset($seenBarcodes[$barcode])) {
                $reasons[] = "duplicate barcode within file (first at row {$seenBarcodes[$barcode]})";
            }

            if ($reasons) {
                $rejected[] = ['row' => $rowNum, 'card_number' => $cardNumber ?: '—', 'reasons' => $reasons];

                continue;
            }

            $seenNumbers[$cardNumber] = $rowNum;
            $seenBarcodes[$barcode] = $rowNum;

            $valid[] = [
                'row' => $rowNum,
                'card_number' => $cardNumber,
                'barcode' => $barcode,
                'value' => $value,
                'issued_date' => $issuedDate,
                'expiry_date' => $expiryDate,
                'status' => GiftCard::STATUS_AVAILABLE,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }
        fclose($handle);

        $imported = 0;

        DB::transaction(function () use (&$valid, &$rejected, &$imported) {
            // Duplicates against the DB, checked inside the transaction.
            $numbers = array_column($valid, 'card_number');
            $barcodes = array_column($valid, 'barcode');
            $existing = GiftCard::whereIn('card_number', $numbers)
                ->orWhereIn('barcode', $numbers)
                ->orWhereIn('card_number', $barcodes)
                ->orWhereIn('barcode', $barcodes)
                ->get(['card_number', 'barcode']);
            $taken = $existing->pluck('card_number')
                ->merge($existing->pluck('barcode'))
                ->flip();

            $insertable = [];
            foreach ($valid as $rowData) {
                $reasons = [];
                if (isset($taken[$rowData['card_number']])) {
                    $reasons[] = "card_number \"{$rowData['card_number']}\" already exists";
                }
                if (isset($taken[$rowData['barcode']])) {
                    $reasons[] = "barcode \"{$rowData['barcode']}\" already exists";
                }
                if ($reasons) {
                    $rejected[] = ['row' => $rowData['row'], 'card_number' => $rowData['card_number'], 'reasons' => $reasons];

                    continue;
                }
                unset($rowData['row']);
                $insertable[] = $rowData;
            }

            foreach (array_chunk($insertable, 200) as $chunk) {
                GiftCard::insert($chunk);
            }
            $imported = count($insertable);
        });

        usort($rejected, fn ($a, $b) => $a['row'] <=> $b['row']);

        return back()->with('gc_import_report', [
            'imported' => $imported,
            'rejected' => $rejected,
        ]);
    }

    /**
     * Gift a card free of charge: out of shop immediately, no bill, no revenue.
     */
    public function gift(GiftCard $giftCard)
    {
        if (! $giftCard->markGifted()) {
            return back()->with('error',
                "Card {$giftCard->card_number} cannot be gifted — only 'available' cards can be issued (current status: {$giftCard->fresh()->status}).");
        }

        return back()->with('success', "Card {$giftCard->card_number} marked as gifted (no revenue).");
    }

    /**
     * Sell a card: open the POS with the card pre-bound in the cart. The card
     * stays 'available' until checkout actually completes — no state is
     * changed here, so an abandoned sale leaves no limbo.
     */
    public function sell(GiftCard $giftCard)
    {
        if ($giftCard->status !== GiftCard::STATUS_AVAILABLE) {
            return back()->with('error',
                "Card {$giftCard->card_number} cannot be sold — only 'available' cards can be issued (current status: {$giftCard->status}).");
        }

        return redirect()->route('pos.index', ['sell_gift_card' => $giftCard->id]);
    }

    /**
     * POS lookup by card number or scanned barcode: live status for anyone
     * with POS access.
     */
    public function lookup(Request $request): JsonResponse
    {
        $code = trim((string) $request->get('code', ''));
        if ($code === '') {
            return response()->json(['found' => false, 'state' => 'not_found', 'message' => 'No code given.'], 422);
        }

        $card = GiftCard::findByCode($code);

        if (! $card) {
            return response()->json([
                'found' => false,
                'state' => 'not_found',
                'message' => 'No gift card found for this code.',
            ]);
        }

        [$state, $message] = match (true) {
            $card->status === GiftCard::STATUS_REDEEMED => ['redeemed', 'This card has already been redeemed'.($card->redeemed_at ? ' on '.$card->redeemed_at->format('Y-m-d') : '').'.'],
            $card->status === GiftCard::STATUS_AVAILABLE => ['not_issued', 'This card has not been issued yet — it must be sold or gifted first.'],
            $card->status === GiftCard::STATUS_CANCELLED => ['cancelled', 'This card has been cancelled.'],
            $card->isExpired() => ['expired', 'This card expired on '.$card->expiry_date->format('Y-m-d').'.'],
            default => ['ready', 'Card is valid and ready to redeem.'],
        };

        return response()->json([
            'found' => true,
            'state' => $state,
            'message' => $message,
            'card' => [
                'id' => $card->id,
                'card_number' => $card->card_number,
                'value' => (string) $card->value,
                'status' => $card->status,
                'live_status' => $card->liveStatus(),
                'issued_date' => $card->issued_date?->format('Y-m-d'),
                'expiry_date' => $card->expiry_date?->format('Y-m-d'),
                'issued_at' => $card->issued_at?->format('Y-m-d'),
                'redeemed_at' => $card->redeemed_at?->format('Y-m-d'),
            ],
        ]);
    }

    /**
     * Strict date parsing for import: value must round-trip the format so
     * Carbon's overflow leniency (e.g. 2026-13-45) is rejected.
     */
    private function parseImportDate(string $value): ?string
    {
        foreach (['Y-m-d', 'Y/m/d', 'd/m/Y', 'd-m-Y'] as $format) {
            try {
                $dt = Carbon::createFromFormat('!'.$format, $value);
            } catch (\Throwable) {
                continue;
            }
            if ($dt !== false && $dt->format($format) === $value) {
                return $dt->format('Y-m-d');
            }
        }

        return null;
    }
}
