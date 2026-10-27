import { useEffect, useMemo } from 'react';

/**
 * 選択したファイルのプレビュー用URLを返す。ファイルが変わるか外れたら古いURLを解放する。
 */
export function useFilePreview(file: File | null): string | null {
    const url = useMemo(
        () => (file ? URL.createObjectURL(file) : null),
        [file],
    );

    useEffect(
        () => () => {
            if (url) {
                URL.revokeObjectURL(url);
            }
        },
        [url],
    );

    return url;
}
