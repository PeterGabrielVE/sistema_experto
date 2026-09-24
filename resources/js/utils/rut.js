// Chilean RUT helpers. Same algorithm as App\Rules\Rut (server side is the source of truth).

export function clean(rut) {
    return String(rut ?? '').replace(/[^0-9kK]/g, '').toUpperCase();
}

export function checkDigit(number) {
    let sum = 0;
    let factor = 2;

    for (const digit of String(number).split('').reverse()) {
        sum += Number(digit) * factor;
        factor = factor === 7 ? 2 : factor + 1;
    }

    const remainder = 11 - (sum % 11);

    return remainder === 11 ? '0' : remainder === 10 ? 'K' : String(remainder);
}

export function isValid(rut) {
    const value = clean(rut);
    const match = value.match(/^(\d{7,8})([\dK])$/);

    return match !== null && checkDigit(match[1]) === match[2];
}

/**
 * "123456785" -> "12.345.678-5"
 */
export function format(rut) {
    const value = clean(rut);

    if (value.length < 2) {
        return value;
    }

    const body = value.slice(0, -1).replace(/\B(?=(\d{3})+(?!\d))/g, '.');

    return `${body}-${value.slice(-1)}`;
}
