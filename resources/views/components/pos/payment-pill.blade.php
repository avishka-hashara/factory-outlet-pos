@props([
    'method' => 'cash',
    'size' => 'sm', // 'sm' | 'md'
])

@php
    $normalized = strtolower(trim((string) $method));
    
    $config = match($normalized) {
        'cash' => [
            'label' => 'Cash',
            'icon' => 'payments',
            'classes' => 'bg-emerald-500/10 text-emerald-400 border-emerald-500/25',
            'dot' => 'bg-emerald-400',
        ],
        'card' => [
            'label' => 'Card',
            'icon' => 'credit_card',
            'classes' => 'bg-sky-500/10 text-sky-400 border-sky-500/25',
            'dot' => 'bg-sky-400',
        ],
        'koko' => [
            'label' => 'Koko',
            'icon' => 'smartphone',
            'classes' => 'bg-blue-500/10 text-blue-400 border-blue-500/25',
            'dot' => 'bg-blue-400',
        ],
        'mintpay' => [
            'label' => 'Mint Pay',
            'icon' => 'credit_card',
            'classes' => 'bg-amber-500/10 text-amber-400 border-amber-500/25',
            'dot' => 'bg-amber-400',
        ],
        'bank' => [
            'label' => 'Bank',
            'icon' => 'account_balance',
            'classes' => 'bg-cyan-500/10 text-cyan-400 border-cyan-500/25',
            'dot' => 'bg-cyan-400',
        ],
        'payzy' => [
            'label' => 'Payzy',
            'icon' => 'account_balance_wallet',
            'classes' => 'bg-indigo-500/10 text-indigo-400 border-indigo-500/25',
            'dot' => 'bg-indigo-400',
        ],
        'multipay' => [
            'label' => 'Multipay',
            'icon' => 'call_split',
            'classes' => 'bg-purple-500/10 text-purple-400 border-purple-500/25',
            'dot' => 'bg-purple-400',
        ],
        default => [
            'label' => ucfirst($normalized ?: 'Unknown'),
            'icon' => 'payments',
            'classes' => 'bg-slate-500/10 text-slate-300 border-slate-500/25',
            'dot' => 'bg-slate-400',
        ],
    };

    $padClasses = $size === 'md' 
        ? 'px-2.5 py-1 text-[11px] gap-1.5' 
        : 'px-2 py-0.5 text-[10px] gap-1';
    $iconSize = $size === 'md' ? 'text-[14px]' : 'text-[12px]';
@endphp

<span class="inline-flex items-center rounded border font-semibold font-['Inter'] uppercase tracking-wider {{ $config['classes'] }} {{ $padClasses }} select-none whitespace-nowrap">
    <span class="material-symbols-outlined {{ $iconSize }}" style="font-variation-settings: 'wght' 400, 'FILL' 0, 'opsz' 20;">{{ $config['icon'] }}</span>
    <span>{{ $config['label'] }}</span>
</span>
