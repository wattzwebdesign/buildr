<?php

namespace Buildr\Support;

/**
 * Curated inline icon set (24x24, stroke-based, Lucide-style). Keeps public
 * pages dependency-free — icons render as inline SVG paths.
 */
final class Icons
{
    private const PATHS = [
        'check' => '<path d="m5 13 4 4L19 7"/>',
        'star' => '<path d="m12 3 2.7 5.6 6.1.9-4.4 4.3 1 6.1-5.4-2.9-5.4 2.9 1-6.1L3.2 9.5l6.1-.9z"/>',
        'phone' => '<path d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3 19.5 19.5 0 0 1-6-6 19.8 19.8 0 0 1-3-8.7A2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7c.1 1 .4 2 .7 2.8a2 2 0 0 1-.5 2.1L8.1 9.9a16 16 0 0 0 6 6l1.3-1.2a2 2 0 0 1 2.1-.5c.9.3 1.9.6 2.8.7a2 2 0 0 1 1.7 2z"/>',
        'mail' => '<rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-10 6L2 7"/>',
        'map-pin' => '<path d="M12 21s-7-5.3-7-11a7 7 0 0 1 14 0c0 5.7-7 11-7 11z"/><circle cx="12" cy="10" r="2.5"/>',
        'clock' => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 3"/>',
        'shield' => '<path d="M12 22s8-3 8-10V5l-8-3-8 3v7c0 7 8 10 8 10z"/>',
        'award' => '<circle cx="12" cy="9" r="6"/><path d="m8.5 14-1.5 8 5-3 5 3-1.5-8"/>',
        'hammer' => '<path d="m15 12-8.5 8.5a2.1 2.1 0 0 1-3-3L12 9"/><path d="M17.6 15.3 22 11l-2-2-1.5 1.5-6-6L14 3l-3-1-4 4 6.5 6.5z"/>',
        'wrench' => '<path d="M14.7 6.3a4.5 4.5 0 0 0 6 6l-8.4 8.4a2.1 2.1 0 0 1-3-3l8.4-8.4a4.5 4.5 0 0 0-6-6l2.3 2.3-3 3-2.3-2.3"/>',
        'truck' => '<path d="M1 3h15v13H1zM16 8h4l3 3v5h-7V8z"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/>',
        'home' => '<path d="m3 10 9-7 9 7v10a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><path d="M9 22V12h6v10"/>',
        'heart' => '<path d="M19 14c1.5-1.5 3-3.3 3-5.5A5.5 5.5 0 0 0 12 5 5.5 5.5 0 0 0 2 8.5c0 2.2 1.5 4 3 5.5l7 7z"/>',
        'users' => '<circle cx="9" cy="8" r="4"/><path d="M1 21c0-4 3.6-6 8-6s8 2 8 6"/><path d="M17 4a4 4 0 0 1 0 8M23 21c0-3-1.8-5-4.5-5.7"/>',
        'thumbs-up' => '<path d="M7 10v12H3V10zM7 10l4.5-7a2.4 2.4 0 0 1 2 3.7L12 10h7a2 2 0 0 1 2 2.4l-1.6 7A2 2 0 0 1 17.4 21H7"/>',
        'zap' => '<path d="M13 2 4.5 13.5H11L9.5 22 19 10h-6.5z"/>',
        'sun' => '<circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"/>',
        'droplet' => '<path d="M12 2s6 6.9 6 12a6 6 0 0 1-12 0c0-5.1 6-12 6-12z"/>',
        'leaf' => '<path d="M11 20A7 7 0 0 1 4 13c0-6 5-9 16-9 0 11-3 16-9 16z"/><path d="M4 21c3-6 7-9 12-11"/>',
        'camera' => '<path d="M4 7h3l2-3h6l2 3h3a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V9a2 2 0 0 1 2-2z"/><circle cx="12" cy="13" r="4"/>',
        'calendar' => '<rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/>',
        'dollar-sign' => '<path d="M12 2v20M17 5.5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>',
        'gift' => '<rect x="3" y="8" width="18" height="4"/><path d="M12 8v14M5 12v10h14V12M12 8s-2-6-5-4 5 4 5 4-2-6 5-4-5 4-5 4z"/>',
        'globe' => '<circle cx="12" cy="12" r="10"/><path d="M2 12h20M12 2a15 15 0 0 1 0 20 15 15 0 0 1 0-20z"/>',
        'key' => '<circle cx="7.5" cy="15.5" r="4.5"/><path d="m11 12 10-10M16 7l3 3"/>',
        'lock' => '<rect x="4" y="11" width="16" height="10" rx="2"/><path d="M8 11V7a4 4 0 0 1 8 0v4"/>',
        'settings' => '<circle cx="12" cy="12" r="3"/><path d="M12 2v3M12 19v3M2 12h3M19 12h3M4.9 4.9l2.1 2.1M17 17l2.1 2.1M4.9 19.1 7 17M17 7l2.1-2.1"/>',
        'smile' => '<circle cx="12" cy="12" r="10"/><path d="M8 14s1.5 2 4 2 4-2 4-2M9 9h.01M15 9h.01"/>',
        'tag' => '<path d="m2 12 10 10 10-10L12 2H2z"/><circle cx="7.5" cy="7.5" r="1.5"/>',
        'facebook' => '<path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/>',
        'instagram' => '<rect x="2" y="2" width="20" height="20" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17.5" cy="6.5" r="1"/>',
        'x-twitter' => '<path d="m4 4 16 16M20 4 4 20"/>',
        'youtube' => '<path d="M22.5 7.2a3 3 0 0 0-2-2.2C18.9 4.5 12 4.5 12 4.5s-6.9 0-8.4.5a3 3 0 0 0-2 2.2 31 31 0 0 0 0 9.6 3 3 0 0 0 2 2.2c1.6.5 8.4.5 8.4.5s6.9 0 8.4-.5a3 3 0 0 0 2-2.2 31 31 0 0 0 0-9.6z"/><path d="m10 15 5-3-5-3z"/>',
        'linkedin' => '<rect x="2" y="9" width="4" height="12"/><circle cx="4" cy="4" r="2"/><path d="M10 9h4v2a4.4 4.4 0 0 1 4-2c3 0 4 2 4 5v7h-4v-6c0-1.5-.5-2.5-2-2.5S14 13.5 14 15v6h-4z"/>',
        'google' => '<circle cx="12" cy="12" r="10"/><path d="M12 8v4h6"/>',
    ];

    public static function svg(?string $name, int $size = 24): string
    {
        $path = self::PATHS[$name] ?? null;
        if (! $path) {
            return '';
        }

        return '<svg xmlns="http://www.w3.org/2000/svg" width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'.$path.'</svg>';
    }

    /** value => label options for icon select fields. */
    public static function options(): array
    {
        $names = array_keys(self::PATHS);

        return array_combine($names, array_map(fn ($n) => ucwords(str_replace('-', ' ', $n)), $names));
    }
}
