import { useRef, useState } from 'react';
import type { DragEvent } from 'react';
import { cn } from '@/lib/utils';

export type ExistingImage = { id: number; url: string };
export type NewImage = { uid: string; file: File; previewUrl: string };

export const MAX_IMAGES = 10;

type Slot =
    | { kind: 'existing'; key: string; url: string; id: number }
    | { kind: 'new'; key: string; url: string; uid: string };

type ImageUploaderProps = {
    existingImages: ExistingImage[];
    newImages: NewImage[];
    error?: string;
    onAddFiles: (files: File[]) => void;
    onRemoveExisting: (id: number) => void;
    onRemoveNew: (uid: string) => void;
};

/**
 * 先頭のスロットがメイン画像（`sort_order = 0`）になる。既存画像の後ろに新規アップロードが並ぶ。
 */
export function ImageUploader({
    existingImages,
    newImages,
    error,
    onAddFiles,
    onRemoveExisting,
    onRemoveNew,
}: ImageUploaderProps) {
    const inputRef = useRef<HTMLInputElement>(null);
    const [isDragOver, setIsDragOver] = useState(false);

    const slots: Slot[] = [
        ...existingImages.map((image): Slot => ({
            kind: 'existing',
            key: `existing-${image.id}`,
            url: image.url,
            id: image.id,
        })),
        ...newImages.map((image): Slot => ({
            kind: 'new',
            key: `new-${image.uid}`,
            url: image.previewUrl,
            uid: image.uid,
        })),
    ];

    const isFull = slots.length >= MAX_IMAGES;
    const mainSlot = slots[0] ?? null;
    const subSlots = slots.slice(1);

    const openFileDialog = () => inputRef.current?.click();

    const removeSlot = (slot: Slot) => {
        if (slot.kind === 'existing') {
            onRemoveExisting(slot.id);

            return;
        }

        onRemoveNew(slot.uid);
    };

    const handleDrop = (event: DragEvent<HTMLDivElement>) => {
        event.preventDefault();
        setIsDragOver(false);

        onAddFiles(Array.from(event.dataTransfer.files));
    };

    return (
        <div className="rounded-xl border border-admin-line bg-white p-5">
            <div className="mb-3.5 flex items-baseline gap-2.5">
                <h2 className="text-[13px] font-extrabold text-admin-ink">
                    商品画像
                </h2>
                <p className="text-[11.5px] text-admin-ink-muted">
                    メイン1枚＋サブ9枚（最大{MAX_IMAGES}枚）
                </p>
            </div>

            <div
                className={cn(
                    'flex flex-wrap items-start gap-3 rounded-lg',
                    isDragOver &&
                        'outline-2 outline-offset-4 outline-admin-brand',
                )}
                onDragOver={(event) => {
                    event.preventDefault();
                    setIsDragOver(true);
                }}
                onDragLeave={() => setIsDragOver(false)}
                onDrop={handleDrop}
            >
                {mainSlot ? (
                    <figure className="relative h-[180px] w-[180px] shrink-0 overflow-hidden rounded-[10px] border border-admin-line">
                        <img
                            src={mainSlot.url}
                            alt="メイン画像のプレビュー"
                            className="h-full w-full object-cover"
                        />
                        <figcaption className="absolute top-1.5 left-1.5 rounded bg-admin-ink/70 px-1.5 py-0.5 font-mono text-[10px] text-white">
                            MAIN
                        </figcaption>
                        <button
                            type="button"
                            aria-label="メイン画像を削除"
                            className="absolute top-1.5 right-1.5 rounded bg-admin-danger px-1.5 py-0.5 text-[10px] font-bold text-white"
                            onClick={() => removeSlot(mainSlot)}
                        >
                            削除
                        </button>
                    </figure>
                ) : (
                    <button
                        type="button"
                        className="flex h-[180px] w-[180px] shrink-0 flex-col items-center justify-center gap-1.5 rounded-[10px] border-[1.5px] border-dashed border-admin-line bg-admin-sidebar-bg"
                        onClick={openFileDialog}
                    >
                        <span className="text-[22px] text-admin-ink-muted">
                            ＋
                        </span>
                        <span className="text-[11.5px] font-bold text-admin-ink-muted">
                            メイン画像
                        </span>
                    </button>
                )}

                <ul className="grid min-w-[220px] flex-1 grid-cols-[repeat(auto-fill,minmax(76px,1fr))] gap-2">
                    {Array.from({ length: MAX_IMAGES - 1 }).map((_, index) => {
                        const slot = subSlots[index];

                        if (slot) {
                            return (
                                <li
                                    key={slot.key}
                                    className="relative aspect-square overflow-hidden rounded-lg border border-admin-line"
                                >
                                    <img
                                        src={slot.url}
                                        alt={`サブ画像${index + 1}のプレビュー`}
                                        className="h-full w-full object-cover"
                                    />
                                    <button
                                        type="button"
                                        aria-label={`サブ画像${index + 1}を削除`}
                                        className="absolute top-1 right-1 rounded bg-admin-danger px-1 py-0.5 text-[9px] font-bold text-white"
                                        onClick={() => removeSlot(slot)}
                                    >
                                        ×
                                    </button>
                                </li>
                            );
                        }

                        return (
                            <li key={`empty-${index}`}>
                                <button
                                    type="button"
                                    disabled={isFull}
                                    className="flex aspect-square w-full items-center justify-center rounded-lg border border-dashed border-admin-line bg-admin-sidebar-bg font-mono text-[10px] text-admin-ink-muted disabled:opacity-50"
                                    onClick={openFileDialog}
                                >
                                    {index + 1}
                                </button>
                            </li>
                        );
                    })}
                </ul>
            </div>

            <input
                ref={inputRef}
                type="file"
                multiple
                accept="image/jpeg,image/png,image/webp"
                className="hidden"
                onChange={(event) => {
                    onAddFiles(Array.from(event.target.files ?? []));
                    event.target.value = '';
                }}
            />

            <p className="mt-3 text-[11.5px] text-admin-ink-muted">
                jpg / png /
                webp、1枚あたり5MBまで。ドラッグ＆ドロップでも追加できます。
            </p>

            {error ? (
                <p className="mt-1.5 text-[11.5px] font-bold text-admin-danger">
                    {error}
                </p>
            ) : null}
        </div>
    );
}
