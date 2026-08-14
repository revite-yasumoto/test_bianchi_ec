import { useForm } from '@inertiajs/react';
import type { FormEventHandler } from 'react';
import { FrontLayout } from '@/front/Layouts/FrontLayout';
import { Button } from '@/shared/Components/Button';
import { TextInput } from '@/shared/Components/TextInput';

type ResetPasswordProps = {
    token: string;
    email: string;
};

type ResetPasswordForm = {
    token: string;
    email: string;
    password: string;
    password_confirmation: string;
};

export default function ResetPassword({ token, email }: ResetPasswordProps) {
    const { data, setData, post, processing, errors, reset } =
        useForm<ResetPasswordForm>({
            token,
            email,
            password: '',
            password_confirmation: '',
        });

    const handleSubmit: FormEventHandler = (event) => {
        event.preventDefault();

        post(route('password.update'), {
            onFinish: () => reset('password', 'password_confirmation'),
        });
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
                        新しいパスワードを入力してください。
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
                            onChange={(event) =>
                                setData('email', event.target.value)
                            }
                        />
                        <TextInput
                            id="password"
                            label="新しいパスワード"
                            type="password"
                            autoComplete="new-password"
                            required
                            value={data.password}
                            error={errors.password}
                            placeholder="8文字以上"
                            onChange={(event) =>
                                setData('password', event.target.value)
                            }
                        />
                        <TextInput
                            id="password_confirmation"
                            label="新しいパスワード（確認）"
                            type="password"
                            autoComplete="new-password"
                            required
                            value={data.password_confirmation}
                            error={errors.password_confirmation}
                            onChange={(event) =>
                                setData(
                                    'password_confirmation',
                                    event.target.value,
                                )
                            }
                        />

                        <Button
                            type="submit"
                            disabled={processing}
                            className="mt-1.5 py-4 text-[15px]"
                        >
                            パスワードを変更する
                        </Button>
                    </div>
                </form>
            </div>
        </FrontLayout>
    );
}
