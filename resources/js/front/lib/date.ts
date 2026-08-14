const DATE_OPTIONS: Intl.DateTimeFormatOptions = {
    year: 'numeric',
    month: '2-digit',
    day: '2-digit',
};

/** ISO 8601 の日時を `2026.08.13` に整形する（モックの表記に合わせてドット区切りにする） */
export function dotDateLabel(iso: string): string {
    return new Date(iso)
        .toLocaleDateString('ja-JP', DATE_OPTIONS)
        .replace(/\//g, '.');
}

/** ISO 8601 の日時を `2026.08.13 14:30` に整形する */
export function dotDateTimeLabel(iso: string): string {
    const time = new Date(iso).toLocaleTimeString('ja-JP', {
        hour: '2-digit',
        minute: '2-digit',
    });

    return `${dotDateLabel(iso)} ${time}`;
}
