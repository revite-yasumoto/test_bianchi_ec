export type PostalCodeLookupResult = {
    prefecture_id: number | null;
    prefecture_name: string;
    city: string;
    town: string;
};

/** 全角数字を半角に直したうえで、数字以外を取り除く */
export function toDigits(value: string): string {
    return value
        .replace(/[０-９]/g, (character) =>
            String.fromCharCode(character.charCodeAt(0) - 0xfee0),
        )
        .replace(/[^0-9]/g, '');
}

/** 保存する形（`123-4567`）を前半3桁・後半4桁に分ける */
export function splitPostalCode(postalCode: string): {
    first: string;
    second: string;
} {
    const digits = toDigits(postalCode);

    return { first: digits.slice(0, 3), second: digits.slice(3, 7) };
}

/** 前半・後半を保存する形（`123-4567`）に結合する */
export function joinPostalCode(first: string, second: string): string {
    if (first === '' || second === '') {
        return `${first}${second}`;
    }

    return `${first}-${second}`;
}

export function isPostalCodeLookupResult(
    value: unknown,
): value is PostalCodeLookupResult {
    if (typeof value !== 'object' || value === null) {
        return false;
    }

    const candidate = value as Record<string, unknown>;

    return (
        (candidate.prefecture_id === null ||
            typeof candidate.prefecture_id === 'number') &&
        typeof candidate.prefecture_name === 'string' &&
        typeof candidate.city === 'string' &&
        typeof candidate.town === 'string'
    );
}
