export const formatNumber = (value: number | undefined) =>
    new Intl.NumberFormat('id-ID').format(value ?? 0);

export const formatPercent = (value: number | undefined, digits = 1) =>
    `${Number(value ?? 0).toFixed(digits)}%`;

export const formatCurrency = (value: number | undefined) =>
    new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0,
    }).format(value ?? 0);

export const formatDuration = (seconds: number | undefined) => {
    const value = Math.round(seconds ?? 0);

    if (value < 60) {
        return `${value}s`;
    }

    return `${Math.floor(value / 60)}m ${value % 60}s`;
};

export const titleCase = (value: string) =>
    value
        .replaceAll('_', ' ')
        .replace(/\b\w/g, (letter) => letter.toUpperCase());
