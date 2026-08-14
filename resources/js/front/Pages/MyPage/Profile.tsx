import { Link, useForm } from '@inertiajs/react';
import { MyPageLayout } from '@/front/Layouts/MyPageLayout';
import { TextInput } from '@/shared/Components/TextInput';

type Props = {
    profile: {
        name: string;
        name_kana: string | null;
        email: string;
        tel: string | null;
    };
};

export default function Profile({ profile }: Props) {
    const { data, setData, put, processing, errors } = useForm({
        name: profile.name,
        name_kana: profile.name_kana ?? '',
        email: profile.email,
        tel: profile.tel ?? '',
    });

    return (
        <MyPageLayout
            title="会員情報変更"
            description="お名前・メールアドレス・電話番号を変更できます。"
            heading="会員情報変更"
        >
            <form
                onSubmit={(event) => {
                    event.preventDefault();
                    put(route('mypage.profile.update'), {
                        preserveScroll: true,
                    });
                }}
                className="flex max-w-md flex-col gap-3.5"
            >
                <TextInput
                    id="profile-name"
                    label="お名前"
                    required
                    value={data.name}
                    error={errors.name}
                    placeholder="山田 太郎"
                    onChange={(event) => setData('name', event.target.value)}
                />
                <TextInput
                    id="profile-name-kana"
                    label="お名前（カナ）"
                    value={data.name_kana}
                    error={errors.name_kana}
                    placeholder="ヤマダ タロウ"
                    onChange={(event) =>
                        setData('name_kana', event.target.value)
                    }
                />
                <TextInput
                    id="profile-email"
                    label="メールアドレス"
                    type="email"
                    required
                    autoComplete="email"
                    value={data.email}
                    error={errors.email}
                    placeholder="taro@example.com"
                    onChange={(event) => setData('email', event.target.value)}
                />
                <TextInput
                    id="profile-tel"
                    label="電話番号"
                    inputMode="tel"
                    value={data.tel}
                    error={errors.tel}
                    placeholder="090-1234-5678"
                    onChange={(event) => setData('tel', event.target.value)}
                />

                <button
                    type="submit"
                    disabled={processing}
                    className="mt-1 rounded-full bg-brand py-3.5 text-sm font-extrabold text-white disabled:opacity-60"
                >
                    保存する
                </button>
            </form>

            <p className="mt-10 border-t border-line pt-5 text-[12.5px] text-ink2">
                退会をご希望の方は{' '}
                <Link
                    href={route('mypage.withdrawal')}
                    className="font-bold text-ink2 underline"
                >
                    こちら
                </Link>
            </p>
        </MyPageLayout>
    );
}
