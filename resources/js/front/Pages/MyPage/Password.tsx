import { useForm } from '@inertiajs/react';
import { MyPageLayout } from '@/front/Layouts/MyPageLayout';
import { TextInput } from '@/shared/Components/TextInput';

export default function Password() {
    const { data, setData, put, processing, errors, reset } = useForm({
        current_password: '',
        password: '',
        password_confirmation: '',
    });

    return (
        <MyPageLayout
            title="パスワード変更"
            description="ログインに使うパスワードを変更できます。"
            heading="パスワード変更"
        >
            <form
                onSubmit={(event) => {
                    event.preventDefault();
                    put(route('mypage.password.update'), {
                        preserveScroll: true,
                        onSuccess: () => reset(),
                    });
                }}
                className="flex max-w-md flex-col gap-3.5"
            >
                <TextInput
                    id="current-password"
                    label="現在のパスワード"
                    type="password"
                    required
                    autoComplete="current-password"
                    value={data.current_password}
                    error={errors.current_password}
                    onChange={(event) =>
                        setData('current_password', event.target.value)
                    }
                />
                <TextInput
                    id="new-password"
                    label="新しいパスワード"
                    type="password"
                    required
                    autoComplete="new-password"
                    value={data.password}
                    error={errors.password}
                    onChange={(event) =>
                        setData('password', event.target.value)
                    }
                />
                <TextInput
                    id="new-password-confirmation"
                    label="新しいパスワード（確認）"
                    type="password"
                    required
                    autoComplete="new-password"
                    value={data.password_confirmation}
                    onChange={(event) =>
                        setData('password_confirmation', event.target.value)
                    }
                />

                <button
                    type="submit"
                    disabled={processing}
                    className="mt-1 rounded-full bg-brand py-3.5 text-sm font-extrabold text-white disabled:opacity-60"
                >
                    パスワードを変更する
                </button>
            </form>
        </MyPageLayout>
    );
}
