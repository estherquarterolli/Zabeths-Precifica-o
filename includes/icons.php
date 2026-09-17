<?php
/**
 * Ícones de contorno (24x24, stroke=currentColor) desenhados com primitivas SVG.
 * Uso: <?= icon('dashboard') ?> — herda a cor do texto e pode ser dimensionado por CSS (.icon svg).
 */
function icon(string $name, int $size = 20): string
{
    $inner = ICONS[$name] ?? ICONS['dot'];
    return '<svg class="icon" width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $inner . '</svg>';
}

const ICONS = [
    'dashboard' => '<rect x="3.5" y="12.5" width="4" height="8" rx="1"/><rect x="10" y="8" width="4" height="12.5" rx="1"/><rect x="16.5" y="4" width="4" height="16.5" rx="1"/>',

    'cupcake' => '<path d="M7 11h10l-1.2 8.2a2 2 0 0 1-2 1.8H10.2a2 2 0 0 1-2-1.8L7 11Z"/><path d="M8 11c-1.5 0-2.5-1-2.5-2.2C5.5 7.3 7 6.5 8 7.2c.3-1.5 1.6-2.5 3-2.2.6-1 1.8-1.5 3-1 1.1.4 1.8 1.5 1.7 2.7 1.3.1 2.3 1.1 2.3 2.3 0 1.2-1 2-2.3 2Z"/><path d="M12 4v-.8"/>',

    'basket' => '<path d="M4.5 10h15l-1.4 8.4a2 2 0 0 1-2 1.6H7.9a2 2 0 0 1-2-1.6L4.5 10Z"/><path d="M8 10 9.5 5.5M16 10 14.5 5.5M9.5 13.5v3M12 13.5v3M14.5 13.5v3"/><path d="M3.5 10h17"/>',

    'gear' => '<circle cx="12" cy="12" r="3"/><path d="M12 3.5v2.2M12 18.3v2.2M20.5 12h-2.2M5.7 12H3.5M17.7 6.3l-1.5 1.5M7.8 16.2l-1.5 1.5M17.7 17.7l-1.5-1.5M7.8 7.8 6.3 6.3"/>',

    'logout' => '<path d="M9 4.5H6.2A1.7 1.7 0 0 0 4.5 6.2v11.6A1.7 1.7 0 0 0 6.2 19.5H9"/><path d="M15.5 16 20 12l-4.5-4M9.5 12H20"/>',

    'plus' => '<path d="M12 5v14M5 12h14"/>',

    'pencil' => '<path d="M5 19.5 5.6 16.7 16 6.3a1.8 1.8 0 0 1 2.5 0l.8.8a1.8 1.8 0 0 1 0 2.5L9 19.9 5 19.5Z"/><path d="M14.3 7.9l2.3 2.3"/>',

    'trash' => '<path d="M5 7h14M9.5 7V5.3A1.3 1.3 0 0 1 10.8 4h2.4a1.3 1.3 0 0 1 1.3 1.3V7M18 7l-.7 11.3A2 2 0 0 1 15.3 20H8.7a2 2 0 0 1-2-1.7L6 7"/><path d="M10.2 11v5.5M13.8 11v5.5"/>',

    'eye' => '<path d="M2.5 12S6 5.5 12 5.5 21.5 12 21.5 12 18 18.5 12 18.5 2.5 12 2.5 12Z"/><circle cx="12" cy="12" r="2.6"/>',

    'save' => '<path d="M12 4v10.5M12 14.5 8.3 10.8M12 14.5l3.7-3.7"/><path d="M5 15.5V18a1.7 1.7 0 0 0 1.7 1.7h10.6A1.7 1.7 0 0 0 19 18v-2.5"/>',

    'check-circle' => '<circle cx="12" cy="12" r="8.5"/><path d="m8.5 12.3 2.4 2.4 4.6-5.2"/>',

    'warning' => '<path d="M12 4.2 21 19.5H3L12 4.2Z"/><path d="M12 10v3.7"/><circle cx="12" cy="16.7" r="0.15" fill="currentColor" stroke="none"/>',

    'archive' => '<rect x="4" y="5" width="16" height="4" rx="1"/><path d="M5.5 9v7.3A2 2 0 0 0 7.5 18.3h9a2 2 0 0 0 2-2V9"/><path d="M10 12.7h4"/>',

    'arrow-left' => '<path d="M19 12H6M11 6.5 5.5 12l5.5 5.5"/>',

    'x' => '<path d="M6 6l12 12M18 6 6 18"/>',

    'sparkle' => '<path d="M12 3.5c.5 3 2 4.5 5 5-3 .5-4.5 2-5 5-.5-3-2-4.5-5-5 3-.5 4.5-2 5-5Z"/><path d="M18.5 15c.3 1.4 1 2.1 2.4 2.4-1.4.3-2.1 1-2.4 2.4-.3-1.4-1-2.1-2.4-2.4 1.4-.3 2.1-1 2.4-2.4Z"/>',

    'dot' => '<circle cx="12" cy="12" r="2"/>',
];
