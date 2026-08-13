import { useForm } from '@inertiajs/react';
import type { AddressData } from '@/front/Components/Checkout/AddressSelector';
import type { ShippingOption } from '@/front/lib/checkoutAmounts';
import { Checkbox } from '@/shared/Components/Checkbox';
import { Modal } from '@/shared/Components/Modal';
import { SelectInput } from '@/shared/Components/SelectInput';
import { TextInput } from '@/shared/Components/TextInput';
import { yen } from '@/shared/lib/yen';

type AddressModalProps = {
    isOpen: boolean;
    prefectures: { id: number; name: string }[];
    /** 渡した場合のみ都道府県ごとの送料・お届け日数を案内する（購入手続きから開いたとき） */
    shippingByPrefecture?: Record<number, ShippingOption>;
    /** 渡すと編集モードになる。未指定なら新規追加 */
    address?: AddressData;
    onClose: () => void;
};

/**
 * 編集対象を切り替えるときは、呼び出し側で `key` を変えて入力欄の初期値を作り直すこと
 * （`useForm` の初期値は初回マウント時にしか評価されない）。
 */
export function AddressModal({
    isOpen,
    prefectures,
    shippingByPrefecture,
    address,
    onClose,
}: AddressModalProps) {
    const isEditing = address !== undefined;
    const { data, setData, post, put, processing, errors, reset, clearErrors } =
        useForm({
            label: address?.label ?? '自宅',
            recipient_name: address?.recipient_name ?? '',
            postal_code: address?.postal_code ?? '',
            prefecture_id: address?.prefecture_id ?? prefectures[0]?.id ?? 0,
            city: address?.city ?? '',
            address_line1: address?.address_line1 ?? '',
            address_line2: address?.address_line2 ?? '',
            tel: address?.tel ?? '',
            is_default: address?.is_default ?? false,
            // 追加した住所をそのまま配送先として選択済みにする
            use_for_checkout: !isEditing,
        });

    const shipping = shippingByPrefecture?.[data.prefecture_id];

    const close = () => {
        reset();
        clearErrors();
        onClose();
    };

    const submit = () => {
        const options = {
            preserveScroll: true,
            // 成功時はページを作り直し、保存後の一覧・選択状態をサーバーから取り込む。
            // 入力エラー時だけ状態を残して入力値を保持する
            preserveState: (page: { props: { errors: object } }) =>
                Object.keys(page.props.errors).length > 0,
        };

        if (address) {
            put(route('addresses.update', [address.id]), options);

            return;
        }

        post(route('addresses.store'), options);
    };

    return (
        <Modal
            isOpen={isOpen}
            title={isEditing ? 'お届け先の編集' : '新しいお届け先'}
            onClose={close}
        >
            <form
                onSubmit={(event) => {
                    event.preventDefault();
                    submit();
                }}
                className="flex flex-col gap-3.5"
            >
                <TextInput
                    id="address-label"
                    label="表示名"
                    required
                    value={data.label}
                    error={errors.label}
                    placeholder="自宅"
                    onChange={(event) => setData('label', event.target.value)}
                />
                <TextInput
                    id="address-recipient-name"
                    label="宛名"
                    required
                    value={data.recipient_name}
                    error={errors.recipient_name}
                    placeholder="山田 太郎"
                    onChange={(event) =>
                        setData('recipient_name', event.target.value)
                    }
                />
                <TextInput
                    id="address-postal-code"
                    label="郵便番号"
                    required
                    inputMode="numeric"
                    value={data.postal_code}
                    error={errors.postal_code}
                    placeholder="150-0041"
                    onChange={(event) =>
                        setData('postal_code', event.target.value)
                    }
                />

                <div>
                    <SelectInput
                        id="address-prefecture-id"
                        label="都道府県"
                        required
                        value={data.prefecture_id}
                        error={errors.prefecture_id}
                        onChange={(event) =>
                            setData('prefecture_id', Number(event.target.value))
                        }
                    >
                        {prefectures.map((prefecture) => (
                            <option key={prefecture.id} value={prefecture.id}>
                                {prefecture.name}
                            </option>
                        ))}
                    </SelectInput>
                    {shipping ? (
                        <p className="mt-1.5 text-[11.5px] text-ink2">
                            送料 {yen(shipping.fee)} ／ お届けまで
                            {shipping.delivery_days}日
                        </p>
                    ) : null}
                </div>

                <TextInput
                    id="address-city"
                    label="市区町村"
                    required
                    value={data.city}
                    error={errors.city}
                    placeholder="渋谷区"
                    onChange={(event) => setData('city', event.target.value)}
                />
                <TextInput
                    id="address-line1"
                    label="番地"
                    required
                    value={data.address_line1}
                    error={errors.address_line1}
                    placeholder="神南1-2-3"
                    onChange={(event) =>
                        setData('address_line1', event.target.value)
                    }
                />
                <TextInput
                    id="address-line2"
                    label="建物名・部屋番号"
                    value={data.address_line2}
                    error={errors.address_line2}
                    placeholder="サイクルレジデンス404"
                    onChange={(event) =>
                        setData('address_line2', event.target.value)
                    }
                />
                <TextInput
                    id="address-tel"
                    label="電話番号"
                    required
                    inputMode="tel"
                    value={data.tel}
                    error={errors.tel}
                    placeholder="090-1234-5678"
                    onChange={(event) => setData('tel', event.target.value)}
                />

                <Checkbox
                    id="address-is-default"
                    checked={data.is_default}
                    onChange={(event) =>
                        setData('is_default', event.target.checked)
                    }
                >
                    既定のお届け先にする
                </Checkbox>

                <button
                    type="submit"
                    disabled={processing}
                    className="mt-1 rounded-full bg-brand py-3.5 text-sm font-extrabold text-white disabled:opacity-60"
                >
                    {isEditing ? '保存する' : 'この住所を使う'}
                </button>
            </form>
        </Modal>
    );
}
