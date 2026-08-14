import { Link, useForm } from '@inertiajs/react';
import type { FormEventHandler } from 'react';
import { FrontLayout } from '@/front/Layouts/FrontLayout';
import { Button } from '@/shared/Components/Button';
import { TextInput } from '@/shared/Components/TextInput';

type ForgotPasswordForm = {
    email: string;
};

export default function ForgotPassword() {
    const { data, setData, post, processing, errors } =
        useForm<ForgotPasswordForm>({ email: '' });

    const handleSubmit: FormEventHandler = (event) => {
        event.preventDefault();

        post(route('password.email'), { preserveScroll: true });
    };

    return (
        <FrontLayout
            title="パスワードの再設定"
            description="Bianchi オンラインストアのパスワード再設定ページです。"
        >
            <div className="flex justify-center px-5 py-11 pb-16">
                <form onSubmit={handleSubmit} className="w-full max-w-[420px]">
                    <h1 className="text-center text-2xl font-black">
                        パスワードの再設定
                    </h1>
                    <p className="mt-2 text-center text-[13px] leading-[1.9] text-ink2">
                        ご登録のメールアドレスを入力してください。
                        再設定用のリンクをお送りします。
                    </p>

                    <div className="mt-7 flex flex-col gap-4">
                        <TextInput
                            id="email"
                            label="メールアドレス"
                            type="email"
                            autoComplete="email"
                            required
                            value={data.email}
                            error={errors.email}
                            placeholder="you@example.com"
                            onChange={(event) =>
                                setData('email', event.target.value)
                            }
                        />

                        <Button
                            type="submit"
                            disabled={processing}
                            className="mt-1.5 py-4 text-[15px]"
                        >
                            再設定メールを送る
                        </Button>

                        <Link
                            href={route('login')}
                            className="text-center text-[13px] font-bold text-brand"
                        >
                            ログイン画面へ戻る →
                        </Link>
                    </div>
                </form>
            </div>
        </FrontLayout>
    );
}
