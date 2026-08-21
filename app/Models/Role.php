<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Role extends Model
{
    protected $fillable = ['name', 'description', 'permissions', 'is_system'];

    protected $casts = [
        'permissions' => 'array',
        'is_system'   => 'boolean',
    ];

    /**
     * All available permission pages in the system.
     */
    public static function allPermissions(): array
    {
        return [
            'dashboard' => 'Dashboard',
            'pos'       => 'POS Billing',
            'pos_log'   => 'POS Sales Log',
            'products'  => 'Products',
            'sales'     => 'Sales History',
            'returns'   => 'Returns & Refunds',
            'reports'     => 'Reports',
            'cash_drawer' => 'Cash Drawer',
            'gift_cards'  => 'Gift Cards',
        ];
    }

    /**
     * Users assigned this role.
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'role_id');
    }

    /**
     * Check if this role grants access to a given page.
     */
    public function hasPermission(string $page): bool
    {
        return in_array($page, $this->permissions ?? []);
    }
}
