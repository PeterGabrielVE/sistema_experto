// Lab values as typed in Chile: decimal comma, no thousands separator.

/**
 * "5,6" -> 5.6; "" -> null; anything that is not a plain decimal -> NaN.
 */
export function parseDecimal(value) {
    const text = String(value ?? '').trim().replace(',', '.');
    if (!text) {
        return null;
    }
    return /^\d+(\.\d+)?$/.test(text) ? Number(text) : NaN;
}

export function formatDecimal(value, digits = 2) {
    if (value === null || value === undefined || value === '') {
        return '';
    }
    return Number(value).toLocaleString('es-CL', { maximumFractionDigits: digits, useGrouping: false });
}

/**
 * normal | high | low against the reference range of an analyte (LabResult::ANALYTES); null when empty or invalid.
 */
export function rangeStatus(value, analyte) {
    if (value === null || Number.isNaN(value)) {
        return null;
    }
    if (analyte.max !== undefined && value > analyte.max) {
        return 'high';
    }
    if (analyte.min !== undefined && value < analyte.min) {
        return 'low';
    }
    return 'normal';
}

export function referenceText(analyte) {
    if (analyte.max !== undefined) {
        return `≤ ${formatDecimal(analyte.max)} ${analyte.unit}`;
    }
    return `≥ ${formatDecimal(analyte.min)} ${analyte.unit}`;
}
