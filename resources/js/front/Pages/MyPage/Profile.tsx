import { Link, useForm } from '@inertiajs/react';
import { BirthdayField } from '@/front/Components/Auth/BirthdayField';
import { MyPageLayout } from '@/front/Layouts/MyPageLayout';
import { Checkbox } from '@/shared/Components/Checkbox';
import { SelectInput } from '@/shared/Components/SelectInput';
import { TextInput } from '@/shared/Components/TextInput';
import { GENDER_OPTIONS } from '@/shared/lib/enums';

type Props = {
    profile: {
        name: string;
        name_kana: string | null;
        email: string;
        tel: string | null;
        /** 未設定は空文字。生年月日は年・月・日を別に受け取る */
        birth_year: string;
        birth_month: string;
        birth_day: string;
        gender: string;
        is_newsletter_subscribed: boolean;
    };
};

export default function Profile({ profile }: Props) {
    const { data, setData, put, processing, errors } = useForm({
        name: profile.name,
        name_kana: profile.name_kana ?? '',
        email: profile.email,
        tel: profile.tel ?? '',
        birth_year: profile.birth_year,
        birth_month: profile.birth_month,
        birth_day: profile.birth_day,
        gender: profile.gender,
        is_newsletter_subscribed: profile.is_newsletter_subscribed,
    });

    return (
        <MyPageLayout
            title="会員情報変更"
            description="お名前・メールアドレス・電話番号・生年月日・性別・メールマガジンの受信設定を変更できます。"
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
                    required
                    value={data.tel}
                    error={errors.tel}
                    placeholder="090-1234-5678"
                    onChange={(event) => setData('tel', event.target.value)}
                />
                <BirthdayField
                    id="profile-birthday"
                    year={data.birth_year}
                    month={data.birth_month}
                    day={data.birth_day}
                    error={errors.birth_year}
                    onChange={(part, value) =>
                        setData(`birth_${part}` as const, value)
                    }
                />
                <SelectInput
                    id="profile-gender"
                    label="性別"
                    value={data.gender}
                    error={errors.gender}
                    onChange={(event) => setData('gender', event.target.value)}
                >
                    <option value="">--</option>
                    {GENDER_OPTIONS.map((option) => (
                        <option key={option.value} value={option.value}>
                            {option.label}
                        </option>
                    ))}
                </SelectInput>
                <Checkbox
                    id="profile-newsletter"
                    checked={data.is_newsletter_subscribed}
                    error={errors.is_newsletter_subscribed}
                    onChange={(event) =>
                        setData(
                            'is_newsletter_subscribed',
                            event.target.checked,
                        )
                    }
                >
                    メールマガジンを受け取る
                </Checkbox>

                <button
                    type="submit"
                    disabled={processing}
                    className="mt-1 rounded-full bg-brand py-3.5 text-base font-extrabold text-white disabled:opacity-60"
                >
                    保存する
                </button>
            </form>

            <p className="mt-10 border-t border-line pt-5 text-[15px] text-ink2">
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
