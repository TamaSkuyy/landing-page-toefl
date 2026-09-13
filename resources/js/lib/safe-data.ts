// ============================================================
// Safe Data Utilities
// Reusable helpers for normalising PHP → JS data and formatting.
// ============================================================

/**
 * Normalise a value that might be a JS array, a PHP-serialised object
 * (keyed by "0","1",…), null, or undefined into a proper array.
 *
 * PHP Collections sometimes serialise as `{}` instead of `[]`.
 */
export function toSafeArray<T>(
    val: T[] | Record<string, T> | null | undefined,
): T[] {
    if (Array.isArray(val)) {
        return val;
    }

    if (val !== null && val !== undefined && typeof val === 'object') {
        return Object.values(val);
    }

    return [];
}

/**
 * Return a guaranteed number — falls back to `fallback` when the input
 * is null, undefined or NaN.
 */
export function safeNumber(
    val: number | null | undefined,
    fallback = 0,
): number {
    if (val === null || val === undefined || Number.isNaN(val)) {
        return fallback;
    }

    return val;
}

// ── Formatters ──────────────────────────────────────────────

/** Format IDR currency without decimals. */
export function formatCurrency(amount: number | null | undefined): string {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
    }).format(safeNumber(amount));
}

/** Format a number with Indonesian locale grouping. */
export function formatNumber(num: number | null | undefined): string {
    return new Intl.NumberFormat('id-ID').format(safeNumber(num));
}

/** Format seconds into a human-readable duration string. */
export function formatDuration(seconds: number | null | undefined): string {
    const s = safeNumber(seconds);

    if (s < 60) {
        return `${Math.round(s)}s`;
    }

    const mins = Math.floor(s / 60);
    const secs = Math.round(s % 60);

    return `${mins}m ${secs}s`;
}

/**
 * Safe percentage string — avoids crashes when the value is
 * null / undefined.
 */
export function formatPercent(
    val: number | null | undefined,
    decimals = 1,
): string {
    return safeNumber(val).toFixed(decimals);
}
