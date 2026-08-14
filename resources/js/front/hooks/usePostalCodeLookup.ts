import { useEffect, useRef, useState } from 'react';
import {
    isPostalCodeLookupResult,
    type PostalCodeLookupResult,
} from '@/front/lib/postalCode';

export type PostalCodeLookupStatus = 'idle' | 'loading' | 'unresolved';

/** 応答が返らないまま「検索しています」が残り続けないよう、サーバー側のタイムアウトより長めに打ち切る */
const TIMEOUT_MS = 10000;

type PostalCodeLookup = {
    status: PostalCodeLookupStatus;
    /** ハイフンを含まない7桁を渡す。同じ郵便番号は引き直さない */
    lookup: (postalCode: string) => void;
    reset: () => void;
};

export function usePostalCodeLookup(
    onResolved: (result: PostalCodeLookupResult) => void,
): PostalCodeLookup {
    const [status, setStatus] = useState<PostalCodeLookupStatus>('idle');
    const abortRef = useRef<AbortController | null>(null);
    const lastLookedUpRef = useRef('');

    useEffect(() => () => abortRef.current?.abort(), []);

    const request = async (postalCode: string): Promise<void> => {
        abortRef.current?.abort();
        const controller = new AbortController();
        abortRef.current = controller;

        let timedOut = false;
        const timeoutId = setTimeout(() => {
            timedOut = true;
            controller.abort();
        }, TIMEOUT_MS);

        setStatus('loading');

        try {
            const response = await fetch(
                route('postal-codes.show', { postalCode }),
                {
                    headers: { Accept: 'application/json' },
                    signal: controller.signal,
                },
            );

            if (!response.ok) {
                setStatus('unresolved');

                return;
            }

            const body: unknown = await response.json();

            if (!isPostalCodeLookupResult(body)) {
                setStatus('unresolved');

                return;
            }

            setStatus('idle');
            onResolved(body);
        } catch {
            // 入力の続きで前のリクエストを中断した場合は、案内を出さず次の検索に任せる
            if (controller.signal.aborted && !timedOut) {
                return;
            }

            setStatus('unresolved');
        } finally {
            clearTimeout(timeoutId);
        }
    };

    return {
        status,
        lookup: (postalCode: string): void => {
            if (lastLookedUpRef.current === postalCode) {
                return;
            }

            lastLookedUpRef.current = postalCode;
            void request(postalCode);
        },
        reset: (): void => {
            lastLookedUpRef.current = '';
            setStatus('idle');
        },
    };
}
