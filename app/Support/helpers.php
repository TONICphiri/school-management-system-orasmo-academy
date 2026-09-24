<?php

use App\Models\School;
use App\Models\Term;

if (! function_exists('current_school')) {
    function current_school(): ?School
    {
        $user = auth()->user();

        return $user?->school_id ? $user->school : null;
    }
}

if (! function_exists('current_term')) {
    function current_term(): ?Term
    {
        static $term = false;
        if ($term === false) {
            $term = current_school()?->currentTerm();
        }

        return $term;
    }
}

if (! function_exists('mwk')) {
    /** Format an amount in Malawi Kwacha, for example MK 1,250,000. */
    function mwk($value): string
    {
        return 'MK '.number_format((float) $value, 0);
    }
}

if (! function_exists('num')) {
    function num($value, int $decimals = 1, string $empty = 'n/a'): string
    {
        if ($value === null || $value === '') {
            return $empty;
        }

        return rtrim(rtrim(number_format((float) $value, $decimals), '0'), '.');
    }
}

if (! function_exists('status_tone')) {
    function status_tone(?string $status): string
    {
        return match ($status) {
            'ACTIVE', 'RELEASED', 'VALIDATED', 'SENT', 'RESOLVED', 'PRESENT', 'GOOD', 'VERY_GOOD' => 'ok',
            'PENDING_ACTIVATION', 'SUBMITTED', 'CLASS_REVIEWED', 'DEPUTY_APPROVED', 'QUEUED', 'OPEN', 'IN_PROGRESS', 'LATE', 'FAIR' => 'warn',
            'SUSPENDED', 'LOCKED', 'FAILED', 'ESCALATED', 'ABSENT', 'POOR', 'RETURNED' => 'bad',
            default => 'neutral',
        };
    }
}

if (! function_exists('label')) {
    function label(?string $code): string
    {
        return $code ? ucwords(strtolower(str_replace('_', ' ', $code))) : '';
    }
}

if (! function_exists('icon')) {
    function icon(string $name, int $size = 18, string $extra = ''): \Illuminate\Support\HtmlString
    {
        static $paths = null;
        if ($paths === null) {
            $paths = [
                'home' => '<path d="M3 11l9-7 9 7"/><path d="M5 10v10h14V10"/><path d="M10 20v-6h4v6"/>',
                'school' => '<path d="M2 10l10-5 10 5-10 5z"/><path d="M6 12v5c3 2 9 2 12 0v-5"/><path d="M22 10v6"/>',
                'users' => '<circle cx="9" cy="8" r="3.5"/><path d="M2.5 20c.5-3.5 3.2-5.5 6.5-5.5s6 2 6.5 5.5"/><path d="M16 4.5a3.5 3.5 0 010 7"/><path d="M18 14.7c2 .6 3.3 2.4 3.5 5.3"/>',
                'user' => '<circle cx="12" cy="8" r="4"/><path d="M4 21c.6-4 3.8-6.5 8-6.5s7.4 2.5 8 6.5"/>',
                'book' => '<path d="M4 4h6a3 3 0 013 3v13a2 2 0 00-2-2H4z"/><path d="M20 4h-6a3 3 0 00-3 3v13a2 2 0 012-2h7z"/>',
                'calendar' => '<rect x="3" y="5" width="18" height="16"/><path d="M3 10h18M8 3v4M16 3v4"/>',
                'clock' => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
                'check' => '<path d="M4 12l5 5L20 6"/>',
                'check-square' => '<rect x="3" y="3" width="18" height="18"/><path d="M8 12l3 3 5-6"/>',
                'clipboard' => '<rect x="5" y="4" width="14" height="17"/><path d="M9 4V2h6v2M9 10h6M9 14h6M9 18h3"/>',
                'chart' => '<path d="M4 20V4M4 20h16"/><path d="M8 16v-5M12 16V8M16 16v-3"/>',
                'bell' => '<path d="M6 16V11a6 6 0 1112 0v5l2 2H4z"/><path d="M10 20a2 2 0 004 0"/>',
                'shield' => '<path d="M12 3l8 3v6c0 5-3.5 8-8 9-4.5-1-8-4-8-9V6z"/>',
                'flag' => '<path d="M5 21V4M5 4h11l-2 4 2 4H5"/>',
                'file' => '<path d="M6 3h8l4 4v14H6z"/><path d="M14 3v4h4M9 12h6M9 16h6"/>',
                'download' => '<path d="M12 4v11M7 10l5 5 5-5M4 20h16"/>',
                'settings' => '<circle cx="12" cy="12" r="3"/><path d="M12 2v3M12 19v3M4.9 4.9l2.1 2.1M17 17l2.1 2.1M2 12h3M19 12h3M4.9 19.1L7 17M17 7l2.1-2.1"/>',
                'layers' => '<path d="M12 3l9 5-9 5-9-5z"/><path d="M3 13l9 5 9-5"/>',
                'grid' => '<rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/>',
                'message' => '<path d="M4 4h16v12H9l-5 4z"/>',
                'logout' => '<path d="M15 4h4v16h-4M10 8l-4 4 4 4M6 12h10"/>',
                'plus' => '<path d="M12 5v14M5 12h14"/>',
                'search' => '<circle cx="11" cy="11" r="7"/><path d="M20 20l-4-4"/>',
                'eye' => '<path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12z"/><circle cx="12" cy="12" r="3"/>',
                'lock' => '<rect x="5" y="11" width="14" height="10"/><path d="M8 11V7a4 4 0 018 0v4"/>',
                'map' => '<path d="M3 6l6-3 6 3 6-3v15l-6 3-6-3-6 3z"/><path d="M9 3v15M15 6v15"/>',
                'alert' => '<path d="M12 3l10 18H2z"/><path d="M12 10v5M12 18v.5"/>',
                'send' => '<path d="M21 3L10 14M21 3l-7 18-4-7-7-4z"/>',
                'printer' => '<path d="M6 9V3h12v6"/><rect x="3" y="9" width="18" height="8"/><path d="M6 14h12v7H6z"/>',
                'arrow-right' => '<path d="M5 12h14M13 6l6 6-6 6"/>',
                'edit' => '<path d="M4 20h4L19 9l-4-4L4 16z"/><path d="M13 7l4 4"/>',
                'x' => '<path d="M6 6l12 12M18 6L6 18"/>',
                'menu' => '<path d="M4 6h16M4 12h16M4 18h16"/>',
                'inbox' => '<path d="M3 13l3-8h12l3 8v7H3z"/><path d="M3 13h5l1 3h6l1-3h5"/>',
                'award' => '<circle cx="12" cy="9" r="6"/><path d="M8.5 14L7 22l5-3 5 3-1.5-8"/>',
                'upload' => '<path d="M12 20V9M7 14l5-5 5 5M4 4h16"/>',
                'wallet' => '<path d="M3 7h18v13H3zM3 7l3-4h12l3 4M16 13h2"/>',
                'id-card' => '<path d="M3 5h18v14H3zM7 10h4M7 14h6M15 9h3v4h-3z"/>',
            ];
        }

        return new \Illuminate\Support\HtmlString('<svg class="icon" width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="square" stroke-linejoin="miter" aria-hidden="true" '.$extra.'>'.($paths[$name] ?? $paths['file']).'</svg>');
    }
}

if (! function_exists('grade_tone')) {
    function grade_tone(?string $phase, $grade): string
    {
        if ($grade === null || $grade === '') {
            return '';
        }
        $g = (int) $grade;
        if ($phase === 'PRIMARY') {
            return [4 => 'g-top', 3 => 'g-mid', 2 => 'g-low', 1 => 'g-fail'][$g] ?? '';
        }

        return $g <= 2 ? 'g-top' : ($g <= 6 ? 'g-mid' : ($g <= 8 ? 'g-low' : 'g-fail'));
    }
}

if (! function_exists('pct_tone')) {
    function pct_tone($value, int $good = 80, int $fair = 60): string
    {
        if ($value === null) {
            return '';
        }

        return $value >= $good ? '' : ($value >= $fair ? 'amber' : 'red');
    }
}
