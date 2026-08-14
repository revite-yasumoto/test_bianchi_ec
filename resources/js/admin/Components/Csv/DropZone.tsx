import { useRef, useState } from 'react';
import type { DragEvent } from 'react';
import { cn } from '@/lib/utils';

type DropZoneProps = {
    file: File | null;
    error?: string;
    onSelect: (file: File | null) => void;
};

export function DropZone({ file, error, onSelect }: DropZoneProps) {
    const inputRef = useRef<HTMLInputElement>(null);
    const [isDragOver, setIsDragOver] = useState(false);

    const handleDrop = (event: DragEvent<HTMLDivElement>) => {
        event.preventDefault();
        setIsDragOver(false);

        onSelect(event.dataTransfer.files.item(0));
    };

    return (
        <div>
            <div
                className={cn(
                    'rounded-xl border-[1.5px] border-dashed border-admin-line bg-admin-sidebar-bg px-5 py-11 text-center',
                    isDragOver && 'border-admin-brand',
                )}
                onDragOver={(event) => {
                    event.preventDefault();
                    setIsDragOver(true);
                }}
                onDragLeave={() => setIsDragOver(false)}
                onDrop={handleDrop}
            >
                <p className="text-[13px] font-bold text-admin-ink">
                    ここにCSVファイルをドロップ
                </p>
                <p className="mt-1.5 text-[11.5px] text-admin-ink-muted">
                    または
                </p>
                <button
                    type="button"
                    className="mt-3 rounded-lg border border-admin-line bg-white px-5 py-2.5 text-[12.5px] font-bold text-admin-ink"
                    onClick={() => inputRef.current?.click()}
                >
                    ファイルを選択
                </button>

                {file ? (
                    <p className="mt-3 font-mono text-[11.5px] text-admin-ink">
                        {file.name}
                        <button
                            type="button"
                            className="ml-2 font-sans font-bold text-admin-danger"
                            onClick={() => onSelect(null)}
                        >
                            取り消す
                        </button>
                    </p>
                ) : null}
            </div>

            <input
                ref={inputRef}
                type="file"
                accept=".csv,.txt,text/csv,text/plain"
                className="hidden"
                onChange={(event) => {
                    onSelect(event.target.files?.item(0) ?? null);
                    event.target.value = '';
                }}
            />

            {error ? (
                <p className="mt-2 text-[11.5px] font-bold text-admin-danger">
                    {error}
                </p>
            ) : null}
        </div>
    );
}
