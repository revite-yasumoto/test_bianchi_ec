import { useForm } from '@inertiajs/react';
import type { ShippingOption } from '@/front/lib/checkoutAmounts';
import { Checkbox } from '@/shared/Components/Checkbox';
import { Modal } from '@/shared/Components/Modal';
import { SelectInput } from '@/shared/Components/SelectInput';
import { TextInput } from '@/shared/Components/TextInput';
import { yen } from '@/shared/lib/yen';

type AddressModalProps = {
    isOpen: boolean;
    prefectures: { id: number; name: string }[];
    shippingByPrefecture: Record<number, ShippingOption>;
    onClose: () => void;
};

export function AddressModal({
    isOpen,
    prefectures,
    shippingByPrefecture,
    onClose,
}: AddressModalProps) {
    const { data, setData, post, processing, errors, reset, clearErrors } =
        useForm({
            label: '自宅',
            recipient_name: '',
            postal_code: '',
            prefecture_id: prefectures[0]?.id ?? 0,
            city: '',
            address_line1: '',
            address_line2: '',
            tel: '',
            is_default: false,
            // 追加した住所をそのまま配送先として選択済みにする
            use_for_checkout: true,
        });

    const shipping = shippingByPrefecture[data.prefecture_id];

    const close = () => {
        reset();
        clearErrors();
        onClose();
    };

    const submit = () => {
        post(route('addresses.store'), {
            preserveScroll: true,
            // 成功時はページを作り直し、追加した住所が選択済みになった状態をサーバーから取り込む。
            // 入力エラー時だけ状態を残して入力値を保持する
            preserveState: (page) => Object.keys(page.props.errors).length > 0,
        });
    };

    return (
        <Modal isOpen={isOpen} title="新しいお届け先" onClose={close}>
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
                    この住所を使う
                </button>
            </form>
        </Modal>
    );
}
