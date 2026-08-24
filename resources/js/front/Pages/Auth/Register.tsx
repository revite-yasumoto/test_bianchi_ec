import { Link, useForm } from '@inertiajs/react';
import type { FormEventHandler } from 'react';
import { BirthdayField } from '@/front/Components/Auth/BirthdayField';
import { PostalCodeField } from '@/front/Components/Checkout/PostalCodeField';
import { FrontLayout } from '@/front/Layouts/FrontLayout';
import { Button } from '@/shared/Components/Button';
import { Checkbox } from '@/shared/Components/Checkbox';
import { SelectInput } from '@/shared/Components/SelectInput';
import { TextInput } from '@/shared/Components/TextInput';
import { GENDER_OPTIONS } from '@/shared/lib/enums';

type Props = {
    prefectures: { id: number; name: string }[];
};

type RegisterForm = {
    name: string;
    name_kana: string;
    email: string;
    password: string;
    password_confirmation: string;
    tel: string;
    /** 生年月日は年・月・日を別に受け取り、未選択は空文字で送る */
    birth_year: string;
    birth_month: string;
    birth_day: string;
    gender: string;
    postal_code: string;
    prefecture_id: number;
    city: string;
    address_line1: string;
    address_line2: string;
    is_newsletter_subscribed: boolean;
    agree: boolean;
};

export default function Register({ prefectures }: Props) {
    const { data, setData, post, processing, errors, reset } =
        useForm<RegisterForm>({
            name: '',
            name_kana: '',
            email: '',
            password: '',
            password_confirmation: '',
            tel: '',
            birth_year: '',
            birth_month: '',
            birth_day: '',
            gender: '',
            postal_code: '',
            prefecture_id: prefectures[0]?.id ?? 0,
            city: '',
            address_line1: '',
            address_line2: '',
            // 受け取らない選択を誘発しないよう、既定でチェック済みにする
            is_newsletter_subscribed: true,
            agree: false,
        });

    const handleSubmit: FormEventHandler = (event) => {
        event.preventDefault();

        post(route('register.store'), {
            onFinish: () => reset('password', 'password_confirmation'),
        });
    };

    return (
        <FrontLayout
            title="会員登録"
            description="Bianchi オンラインストアの会員登録ページです。"
        >
            <div className="flex justify-center px-5 py-11 pb-16">
                <form onSubmit={handleSubmit} className="w-full max-w-[520px]">
                    <h1 className="text-center text-2xl font-black">
                        会員登録
                    </h1>

                    <div className="mt-7 grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <TextInput
                            id="name"
                            label="お名前"
                            autoComplete="name"
                            required
                            value={data.name}
                            error={errors.name}
                            placeholder="山田 太郎"
                            onChange={(event) =>
                                setData('name', event.target.value)
                            }
                        />
                        <TextInput
                            id="name_kana"
                            label="お名前（カナ）"
                            value={data.name_kana}
                            error={errors.name_kana}
                            placeholder="ヤマダ タロウ"
                            onChange={(event) =>
                                setData('name_kana', event.target.value)
                            }
                        />
                        <TextInput
                            id="email"
                            label="メールアドレス"
                            type="email"
                            autoComplete="email"
                            required
                            className="sm:col-span-2"
                            value={data.email}
                            error={errors.email}
                            placeholder="you@example.com"
                            onChange={(event) =>
                                setData('email', event.target.value)
                            }
                        />
                        <TextInput
                            id="password"
                            label="パスワード"
                            type="password"
                            autoComplete="new-password"
                            required
                            className="sm:col-span-2"
                            value={data.password}
                            error={errors.password}
                            placeholder="8文字以上"
                            onChange={(event) =>
                                setData('password', event.target.value)
                            }
                        />
                        <TextInput
                            id="password_confirmation"
                            label="パスワード（確認）"
                            type="password"
                            autoComplete="new-password"
                            required
                            className="sm:col-span-2"
                            value={data.password_confirmation}
                            placeholder="もう一度入力してください"
                            onChange={(event) =>
                                setData(
                                    'password_confirmation',
                                    event.target.value,
                                )
                            }
                        />
                        <TextInput
                            id="tel"
                            label="電話番号"
                            type="tel"
                            autoComplete="tel"
                            required
                            value={data.tel}
                            error={errors.tel}
                            placeholder="090-1234-5678"
                            onChange={(event) =>
                                setData('tel', event.target.value)
                            }
                        />
                        <SelectInput
                            id="gender"
                            label="性別"
                            value={data.gender}
                            error={errors.gender}
                            onChange={(event) =>
                                setData('gender', event.target.value)
                            }
                        >
                            <option value="">--</option>
                            {GENDER_OPTIONS.map((option) => (
                                <option key={option.value} value={option.value}>
                                    {option.label}
                                </option>
                            ))}
                        </SelectInput>
                        <BirthdayField
                            id="birthday"
                            className="sm:col-span-2"
                            year={data.birth_year}
                            month={data.birth_month}
                            day={data.birth_day}
                            error={errors.birth_year}
                            onChange={(part, value) =>
                                setData(`birth_${part}` as const, value)
                            }
                        />
                    </div>

                    <h2 className="mt-7 text-base font-black">お届け先</h2>
                    <p className="mt-1.5 text-[15px] leading-[1.9] text-ink2">
                        ご注文時の既定のお届け先として登録します。
                    </p>

                    <div className="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div className="sm:col-span-2">
                            <PostalCodeField
                                id="postal_code"
                                value={data.postal_code}
                                error={errors.postal_code}
                                onChange={(postalCode) =>
                                    setData('postal_code', postalCode)
                                }
                                onResolved={(result) =>
                                    setData((current) => ({
                                        ...current,
                                        prefecture_id:
                                            result.prefecture_id ??
                                            current.prefecture_id,
                                        city: result.city,
                                        // 入力済みの番地を町域名で消さない
                                        address_line1:
                                            current.address_line1 === ''
                                                ? result.town
                                                : current.address_line1,
                                    }))
                                }
                            />
                        </div>
                        <SelectInput
                            id="prefecture_id"
                            label="都道府県"
                            required
                            value={data.prefecture_id}
                            error={errors.prefecture_id}
                            onChange={(event) =>
                                setData(
                                    'prefecture_id',
                                    Number(event.target.value),
                                )
                            }
                        >
                            {prefectures.map((prefecture) => (
                                <option
                                    key={prefecture.id}
                                    value={prefecture.id}
                                >
                                    {prefecture.name}
                                </option>
                            ))}
                        </SelectInput>
                        <TextInput
                            id="city"
                            label="市区町村"
                            autoComplete="address-level2"
                            required
                            value={data.city}
                            error={errors.city}
                            placeholder="渋谷区神南"
                            onChange={(event) =>
                                setData('city', event.target.value)
                            }
                        />
                        <TextInput
                            id="address_line1"
                            label="番地"
                            autoComplete="address-line1"
                            required
                            className="sm:col-span-2"
                            value={data.address_line1}
                            error={errors.address_line1}
                            placeholder="1-2-3"
                            onChange={(event) =>
                                setData('address_line1', event.target.value)
                            }
                        />
                        <TextInput
                            id="address_line2"
                            label="建物名・部屋番号"
                            autoComplete="address-line2"
                            className="sm:col-span-2"
                            value={data.address_line2}
                            error={errors.address_line2}
                            placeholder="架空マンション 101"
                            onChange={(event) =>
                                setData('address_line2', event.target.value)
                            }
                        />
                    </div>

                    <Checkbox
                        id="is_newsletter_subscribed"
                        className="mt-6"
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

                    <Checkbox
                        id="agree"
                        className="mt-3"
                        checked={data.agree}
                        error={errors.agree}
                        onChange={(event) =>
                            setData('agree', event.target.checked)
                        }
                    >
                        <Link
                            href={route('legal.terms')}
                            className="font-bold text-brand underline"
                        >
                            利用規約
                        </Link>
                        {' および '}
                        <Link
                            href={route('legal.privacy')}
                            className="font-bold text-brand underline"
                        >
                            プライバシーポリシー
                        </Link>
                        {' に同意します'}
                    </Checkbox>

                    <Button
                        type="submit"
                        disabled={processing}
                        className="mt-5 w-full py-4 text-base"
                    >
                        登録する
                    </Button>

                    <p className="mt-5 text-center text-[15px] text-ink2">
                        すでにアカウントをお持ちの方は
                        <Link
                            href={route('login')}
                            className="ml-1 font-bold text-brand"
                        >
                            ログイン
                        </Link>
                    </p>
                </form>
            </div>
        </FrontLayout>
    );
}
