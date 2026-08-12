import { useCallback, useEffect, useState } from 'react';

/**
 * 一定間隔で次のスライドへ進めるタイマーを管理する。
 * 手動で選び直したときはタイマーを張り直し、直後に切り替わらないようにする。
 */
export function useAutoSlide(count: number, intervalMs: number) {
    const [index, setIndex] = useState(0);
    const [restartCount, setRestartCount] = useState(0);

    useEffect(() => {
        if (count <= 1) {
            return;
        }

        const timer = setInterval(
            () => setIndex((previous) => (previous + 1) % count),
            intervalMs,
        );

        return () => clearInterval(timer);
    }, [count, intervalMs, restartCount]);

    const select = useCallback((next: number) => {
        setIndex(next);
        setRestartCount((previous) => previous + 1);
    }, []);

    return { index: count > 0 ? index % count : 0, select };
}
