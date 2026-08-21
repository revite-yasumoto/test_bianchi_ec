import { Head, useForm } from '@inertiajs/react';
import type { FormEventHandler } from 'react';

type LoginForm = {
    login_id: string;
    password: string;
    remember: boolean;
};

export default function Login() {
    const { data, setData, post, processing, errors, reset } =
        useForm<LoginForm>({
            login_id: '',
            password: '',
            remember: true,
        });

    const handleSubmit: FormEventHandler = (event) => {
        event.preventDefault();

        post(route('admin.login.store'), {
            onFinish: () => reset('password'),
        });
    };

    return (
        <div className="flex min-h-dvh flex-col md:flex-row">
            <Head title="管理者ログイン">
                <meta name="robots" content="noindex,nofollow" />
            </Head>
            <div className="flex flex-col justify-between bg-gradient-to-br from-admin-brand-deep to-admin-brand-darkest px-8 py-10 text-white md:basis-[42%] md:px-11 md:py-14">
                <div>
                    <p className="text-xl font-extrabold tracking-widest">
                        Bianchi
                    </p>
                    <p className="mt-1 text-[9px] tracking-[.22em] text-white/60">
                        ADMIN CONSOLE
                    </p>
                </div>
                <div>
                    <p className="text-2xl font-black leading-snug">
                        受注から在庫まで、
                        <br />
                        ひとつの画面で。
                    </p>
                    <p className="mt-4 text-sm text-white/70">
                        管理コンソールは会員（フロント）とは独立した認証です。管理者マスタに登録されたアカウントのみログインできます。
                    </p>
                </div>
                <p className="text-[10px] tracking-widest text-white/45">
                    SECURE AREA / STAFF ONLY
                </p>
            </div>

            <main className="flex flex-1 items-center justify-center bg-admin-surface px-6 py-12">
                <form onSubmit={handleSubmit} className="w-full max-w-[360px]">
                    <h1 className="text-lg font-extrabold text-admin-ink">
                        管理者ログイン
                    </h1>
                    <p className="mt-1 text-xs text-admin-ink-muted">
                        会員アカウントではログインできません。
                    </p>

                    <div className="mt-6">
                        <label
                            htmlFor="login_id"
                            className="text-[11.5px] font-bold text-admin-ink"
                        >
                            管理者ID / メールアドレス
                        </label>
                        <input
                            id="login_id"
                            type="text"
                            autoComplete="username"
                            value={data.login_id}
                            onChange={(event) =>
                                setData('login_id', event.target.value)
                            }
                            placeholder="admin@bianchi.demo"
                            className="mt-1 w-full rounded-lg border border-admin-line px-3 py-2.5 text-base"
                        />
                        {errors.login_id ? (
                            <p className="mt-1 text-[11px] font-bold text-admin-danger">
                                {errors.login_id}
                            </p>
                        ) : null}
                    </div>

                    <div className="mt-4">
                        <label
                            htmlFor="password"
                            className="text-[11.5px] font-bold text-admin-ink"
                        >
                            パスワード
                        </label>
                        <input
                            id="password"
                            type="password"
                            autoComplete="current-password"
                            value={data.password}
                            onChange={(event) =>
                                setData('password', event.target.value)
                            }
                            placeholder="8文字以上"
                            className="mt-1 w-full rounded-lg border border-admin-line px-3 py-2.5 text-base"
                        />
                        {errors.password ? (
                            <p className="mt-1 text-[11px] font-bold text-admin-danger">
                                {errors.password}
                            </p>
                        ) : null}
                    </div>

                    <label
                        htmlFor="remember"
                        className="mt-4 flex cursor-pointer items-center gap-2 text-xs text-admin-ink-muted"
                    >
                        <input
                            id="remember"
                            type="checkbox"
                            checked={data.remember}
                            onChange={(event) =>
                                setData('remember', event.target.checked)
                            }
                            className="h-4 w-4 rounded border-admin-line"
                        />
                        この端末でログイン状態を保持する
                    </label>

                    <button
                        type="submit"
                        disabled={processing}
                        className="mt-6 w-full rounded-lg bg-admin-brand py-3.5 text-sm font-extrabold text-white disabled:opacity-60"
                    >
                        ログイン
                    </button>

                    <p className="mt-4 text-center text-[11.5px] text-admin-ink-muted">
                        パスワードをお忘れの場合はシステム管理者にご連絡ください。
                    </p>
                </form>
            </main>
        </div>
    );
}
