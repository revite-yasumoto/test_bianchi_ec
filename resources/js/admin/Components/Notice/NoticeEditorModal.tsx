import { useForm } from '@inertiajs/react';
import type { FormEventHandler } from 'react';
import { Modal } from '@/shared/Components/Modal';
import type { NoticeRow } from './NoticeManager';

type NoticeEditorModalProps = {
    /** 編集対象。新規作成のときは `null` */
    row: NoticeRow | null;
    onClose: () => void;
    onSaved: () => void;
};

type NoticeForm = {
    title: string;
    display_start_on: string;
    display_end_on: string;
    body: string;
};

const LABEL_CLASS = 'mb-1.5 block text-[11.5px] font-bold text-admin-ink';

const FIELD_CLASS =
    'w-full rounded-lg border border-admin-line px-3 py-2 text-base';

const ERROR_CLASS = 'mt-1 text-[11px] font-bold text-admin-danger';

export function NoticeEditorModal({
    row,
    onClose,
    onSaved,
}: NoticeEditorModalProps) {
    const { data, setData, post, put, processing, errors } =
        useForm<NoticeForm>({
            title: row?.title ?? '',
            display_start_on: row?.display_start_on ?? '',
            display_end_on: row?.display_end_on ?? '',
            body: row?.body ?? '',
        });

    const handleSubmit: FormEventHandler = (event) => {
        event.preventDefault();

        const options = { preserveScroll: true, onSuccess: onSaved };

        if (row) {
            put(route('admin.notices.update', [row.id]), options);

            return;
        }

        post(route('admin.notices.store'), options);
    };

    return (
        <Modal
            isOpen
            title={row ? 'お知らせを編集' : 'お知らせを作成'}
            onClose={onClose}
            className="max-w-[520px] rounded-xl p-6"
        >
            <form onSubmit={handleSubmit} className="flex flex-col gap-3">
                <div>
                    <label htmlFor="notice_title" className={LABEL_CLASS}>
                        タイトル
                    </label>
                    <input
                        id="notice_title"
                        type="text"
                        value={data.title}
                        placeholder="タイトルを入力"
                        className={FIELD_CLASS}
                        aria-invalid={errors.title !== undefined}
                        onChange={(event) =>
                            setData('title', event.target.value)
                        }
                    />
                    {errors.title ? (
                        <p className={ERROR_CLASS}>{errors.title}</p>
                    ) : null}
                </div>

                <div className="grid grid-cols-2 gap-2.5">
                    <div>
                        <label
                            htmlFor="notice_display_start_on"
                            className={LABEL_CLASS}
                        >
                            掲載開始
                        </label>
                        <input
                            id="notice_display_start_on"
                            type="date"
                            value={data.display_start_on}
                            className={FIELD_CLASS}
                            aria-invalid={errors.display_start_on !== undefined}
                            onChange={(event) =>
                                setData('display_start_on', event.target.value)
                            }
                        />
                        {errors.display_start_on ? (
                            <p className={ERROR_CLASS}>
                                {errors.display_start_on}
                            </p>
                        ) : null}
                    </div>

                    <div>
                        <label
                            htmlFor="notice_display_end_on"
                            className={LABEL_CLASS}
                        >
                            掲載終了
                        </label>
                        <input
                            id="notice_display_end_on"
                            type="date"
                            value={data.display_end_on}
                            className={FIELD_CLASS}
                            aria-invalid={errors.display_end_on !== undefined}
                            onChange={(event) =>
                                setData('display_end_on', event.target.value)
                            }
                        />
                        {errors.display_end_on ? (
                            <p className={ERROR_CLASS}>
                                {errors.display_end_on}
                            </p>
                        ) : null}
                    </div>
                </div>

                <div>
                    <label htmlFor="notice_body" className={LABEL_CLASS}>
                        本文
                    </label>
                    <textarea
                        id="notice_body"
                        rows={5}
                        value={data.body}
                        className={FIELD_CLASS}
                        aria-invalid={errors.body !== undefined}
                        onChange={(event) =>
                            setData('body', event.target.value)
                        }
                    />
                    {errors.body ? (
                        <p className={ERROR_CLASS}>{errors.body}</p>
                    ) : null}
                </div>

                <button
                    type="submit"
                    disabled={processing}
                    className="rounded-lg bg-admin-brand py-3 text-[13px] font-extrabold text-white disabled:opacity-60"
                >
                    保存する
                </button>
            </form>
        </Modal>
    );
}
