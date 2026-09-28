export function jakartaDateKey(value: string | Date = new Date()): string {
    if (typeof value === 'string' && /^\d{4}-\d{2}-\d{2}$/.test(value)) {
        return value;
    }

    const parts = new Intl.DateTimeFormat('en-CA', {
        timeZone: 'Asia/Jakarta',
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
    }).formatToParts(new Date(value));
    const part = (type: string) =>
        parts.find((item) => item.type === type)?.value;

    return `${part('year')}-${part('month')}-${part('day')}`;
}

export function jakartaDateTimeInput(value: Date = new Date()): string {
    const parts = new Intl.DateTimeFormat('en-GB', {
        timeZone: 'Asia/Jakarta',
        hour: '2-digit',
        minute: '2-digit',
        hourCycle: 'h23',
    }).formatToParts(value);
    const part = (type: string) =>
        parts.find((item) => item.type === type)?.value;

    return `${jakartaDateKey(value)}T${part('hour')}:${part('minute')}`;
}
