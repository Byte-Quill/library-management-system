<?php
declare(strict_types=1);

function icon(string $name, string $label = ''): string
{
    $paths = [
        'logout' => '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="m16 17 5-5-5-5"/><path d="M21 12H9"/>',
        'search' => '<circle cx="11" cy="11" r="7"/><path d="m20 20-4-4"/>',
    ];
    $path = $paths[$name] ?? '';
    if ($path === '') {
        return '';
    }
    $accessible = $label === '' ? 'aria-hidden="true"' : 'role="img" aria-label="' . e($label) . '"';
    return '<svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" ' . $accessible . '>' . $path . '</svg>';
}