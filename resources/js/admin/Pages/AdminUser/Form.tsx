import { Link, useForm } from '@inertiajs/react';
import type { FormEventHandler } from 'react';
import { AdminLayout } from '@/admin/Layouts/AdminLayout';

type AdminUserData = {
    id: number;
    admin_code: string;
    name: string;
    email: string;
};

type Props = { admin: AdminUserData | null };

type AdminUserForm = {
    name: string;
    email: string;
    password: string;
    password_confirmation: string;
};

const FIELD_CLASS =
    'w-full rounded-lg border border-admin-line px-3 py-2 text-base';

const LABEL_CLASS = 'mb-1.5 block text-[11.5px] font-bold text-admin-ink';

export default function Form({ admin }: Props) {
    const { data, setData, post, put, processing, errors, reset } =
        useForm<AdminUserForm>({
            name: admin?.name ?? '',
            email: admin?.email ?? '',
            password: '',
            password_confirmation: '',
        });

    const handleSubmit: FormEventHandler = (event) => {
        event.preventDefault();

        const options = {
            onFinish: () => reset('password', 'password_confirmation'),
        };

        if (admin) {
            put(route('admin.admins.update', [admin.id]), options);

            return;
        }

        post(route('admin.admins.store'), options);
    };

    return (
        <AdminLayout title={admin ? '管理者編集' : '管理者登録'}>
            <form onSubmit={handleSubmit} className="max-w-[560px]">
                <div className="rounded-xl border border-admin-line bg-white p-5">
                    {admin ? (
                        <p className="mb-3.5 text-[12.5px] text-admin-ink-muted">
                            管理者ID{' '}
                            <span className="font-mono font-bold text-admin-ink">
                                {admin.admin_code}
                            </span>
                            （変更できません）
                        </p>
                    ) : (
                        <p className="mb-3.5 text-[11.5px] text-admin-ink-muted">
                            管理者IDは登録時に自動で採番されます。
                        </p>
                    )}

                    <div className="flex flex-col gap-3">
                        <div>
                            <label htmlFor="name" className={LABEL_CLASS}>
                                氏名
                            </label>
                            <input
                                id="name"
                                type="text"
                                value={data.name}
                                autoComplete="name"
                                className={FIELD_CLASS}
                                onChange={(event) =>
                                    setData('name', event.target.value)
                                }
                            />
                            {errors.name ? (
                                <p className="mt-1 text-[11px] font-bold text-admin-danger">
                                    {errors.name}
                                </p>
                            ) : null}
                        </div>

                        <div>
                            <label htmlFor="email" className={LABEL_CLASS}>
                                メールアドレス
                            </label>
                            <input
                                id="email"
                                type="email"
                                value={data.email}
                                autoComplete="email"
                                className={FIELD_CLASS}
                                onChange={(event) =>
                                    setData('email', event.target.value)
                                }
                            />
                            {errors.email ? (
                                <p className="mt-1 text-[11px] font-bold text-admin-danger">
                                    {errors.email}
                                </p>
                            ) : null}
                        </div>

                        <div>
                            <label htmlFor="password" className={LABEL_CLASS}>
                                パスワード
                                {admin ? '（変更する場合のみ入力）' : ''}
                            </label>
                            <input
                                id="password"
                                type="password"
                                value={data.password}
                                autoComplete="new-password"
                                placeholder="8文字以上"
                                className={FIELD_CLASS}
                                onChange={(event) =>
                                    setData('password', event.target.value)
                                }
                            />
                            {errors.password ? (
                                <p className="mt-1 text-[11px] font-bold text-admin-danger">
                                    {errors.password}
                                </p>
                            ) : null}
                        </div>

                        <div>
                            <label
                                htmlFor="password_confirmation"
                                className={LABEL_CLASS}
                            >
                                パスワード（確認）
                            </label>
                            <input
                                id="password_confirmation"
                                type="password"
                                value={data.password_confirmation}
                                autoComplete="new-password"
                                className={FIELD_CLASS}
                                onChange={(event) =>
                                    setData(
                                        'password_confirmation',
                                        event.target.value,
                                    )
                                }
                            />
                        </div>
                    </div>
                </div>

                <div className="mt-4 flex gap-2.5">
                    <button
                        type="submit"
                        disabled={processing}
                        className="flex-1 rounded-lg bg-admin-brand py-3 text-[13.5px] font-extrabold text-white disabled:opacity-60"
                    >
                        保存する
                    </button>
                    <Link
                        href={route('admin.admins.index')}
                        className="rounded-lg border border-admin-line bg-white px-5 py-3 text-[13px] font-bold text-admin-ink"
                    >
                        キャンセル
                    </Link>
                </div>
            </form>
        </AdminLayout>
    );
}
