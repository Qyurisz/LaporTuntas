@props([
    'variant' => 'primary',
    'size' => 'md',
    'type' => 'button',
    'href' => null,
])

@php
    $sizes = [
        'lg' => 'min-h-[48px] px-6 text-[15px] rounded-xl',
        'md' => 'min-h-[44px] px-4 text-sm rounded-xl',
        'sm' => 'min-h-[40px] px-3.5 text-xs rounded-xl',
        'xs' => 'min-h-[36px] px-3 text-xs rounded-full',
    ];

    $variants = [
        'primary'   => 'bg-primary hover:bg-primary-hover text-white shadow-[0_4px_14px_rgba(22,166,216,0.35)] border border-transparent',
        'secondary' => 'bg-navy hover:bg-primary-hover text-white border border-transparent',
        'tertiary'  => 'bg-white hover:bg-canvas border border-border text-text-main shadow-2xs',
        'ghost'     => 'bg-transparent hover:bg-canvas text-text-muted hover:text-text-main',
        'danger'    => 'bg-danger hover:opacity-90 text-white border border-transparent',
    ];

    $baseClasses = 'inline-flex items-center justify-center gap-2 font-extrabold transition cursor-pointer select-none focus-visible:outline-none focus-visible:ring-3 focus-visible:ring-primary/35 disabled:opacity-60 disabled:cursor-not-allowed active:scale-[0.99] whitespace-nowrap';
    $sizeClass = $sizes[$size] ?? $sizes['md'];
    $variantClass = $variants[$variant] ?? $variants['primary'];
@endphp

@if($href)
    <a href="{{ $href }}" {{ $attributes->class([$baseClasses, $sizeClass, $variantClass]) }}>
        {{ $slot }}
    </a>
@else
    <button type="{{ $type }}" {{ $attributes->class([$baseClasses, $sizeClass, $variantClass]) }}>
        {{ $slot }}
    </button>
@endif

