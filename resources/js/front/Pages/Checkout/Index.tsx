import { Link, useForm } from '@inertiajs/react';
import { useState } from 'react';
import type { CartRow } from '@/front/Components/Cart/CartItemRow';
import { AddressModal } from '@/front/Components/Checkout/AddressModal';
import { AddressSelector } from '@/front/Components/Checkout/AddressSelector';
import type { AddressData } from '@/front/Components/Checkout/AddressSelector';
import { OrderSummaryCard } from '@/front/Components/Checkout/OrderSummaryCard';
import { PaymentSelector } from '@/front/Components/Checkout/PaymentSelector';
import { StepIndicator } from '@/front/Components/Checkout/StepIndicator';
import { FrontLayout } from '@/front/Layouts/FrontLayout';
import {
    calculateCheckoutAmounts,
    type EcSettingAmounts,
    type ShippingOption,
} from '@/front/lib/checkoutAmounts';
import { deliveryDateLabel } from '@/front/lib/delivery';
import type { PaymentMethod } from '@/shared/lib/enums';

type Props = {
    items: CartRow[];
    addresses: AddressData[];
    prefectures: { id: number; name: string }[];
    shippingByPrefecture: Record<number, ShippingOption>;
    selected: {
        address_id: number | null;
        payment_method: PaymentMethod;
    };
    ecSetting: EcSettingAmounts;
    /** 配達予定日の算出基準日（サーバーの当日。ISO 8601 の日付） */
    deliveryBaseDate: string;
};

export default function Index({
    items,
    addresses,
    prefectures,
    shippingByPrefecture,
    selected,
    ecSetting,
    deliveryBaseDate,
}: Props) {
    const [isAddressModalOpen, setIsAddressModalOpen] = useState(false);
    const { data, setData, post, processing, errors } = useForm({
        address_id: selected.address_id,
        payment_method: selected.payment_method,
    });

    const selectedAddress =
        addresses.find((address) => address.id === data.address_id) ?? null;
    const subtotal = items.reduce((total, item) => total + item.subtotal, 0);
    // 配送先・支払い方法の切り替えに即座に追従させるため、表示用の金額はこの場で算出する
    const amounts = calculateCheckoutAmounts(
        subtotal,
        selectedAddress
            ? shippingByPrefecture[selectedAddress.prefecture_id]
            : undefined,
        data.payment_method,
        ecSetting,
    );

    return (
        <FrontLayout
            title="購入手続き"
            description="お届け先とお支払い方法を選んでご注文内容を確認します。"
        >
            <div className="px-[22px] py-[26px] pb-14">
                <StepIndicator current={2} />
                <h1 className="mb-5.5 text-[26px] font-black">購入手続き</h1>

                <form
                    onSubmit={(event) => {
                        event.preventDefault();
                        post(route('checkout.store'));
                    }}
                    className="grid items-start gap-6.5 lg:grid-cols-[minmax(320px,1fr)_minmax(320px,1fr)]"
                >
                    <div className="flex flex-col gap-6">
                        <AddressSelector
                            addresses={addresses}
                            selectedId={data.address_id}
                            error={errors.address_id}
                            onChange={(addressId) =>
                                setData('address_id', addressId)
                            }
                            onAddNew={() => setIsAddressModalOpen(true)}
                        />
                        <PaymentSelector
                            selected={data.payment_method}
                            codFee={ecSetting.cod_fee}
                            onChange={(paymentMethod) =>
                                setData('payment_method', paymentMethod)
                            }
                        />
                    </div>

                    <OrderSummaryCard
                        title="ご注文内容"
                        items={items}
                        subtotal={amounts.subtotal}
                        shippingFee={amounts.shippingFee}
                        codFee={amounts.codFee}
                        total={amounts.total}
                        prefectureName={
                            selectedAddress?.prefecture_name ?? '未選択'
                        }
                        deliveryDateLabel={
                            selectedAddress
                                ? deliveryDateLabel(
                                      deliveryBaseDate,
                                      amounts.deliveryDays,
                                  )
                                : '配送先を選択してください'
                        }
                    >
                        <button
                            type="submit"
                            disabled={processing || !selectedAddress}
                            className="w-full rounded-full bg-coral py-4 text-[15px] font-extrabold text-white disabled:opacity-40"
                        >
                            注文内容を確認する
                        </button>
                        <Link
                            href={route('cart.index')}
                            className="w-full rounded-full border border-line py-3.5 text-center text-[13.5px] font-bold"
                        >
                            カートに戻る
                        </Link>
                    </OrderSummaryCard>
                </form>
            </div>

            <AddressModal
                isOpen={isAddressModalOpen}
                prefectures={prefectures}
                shippingByPrefecture={shippingByPrefecture}
                onClose={() => setIsAddressModalOpen(false)}
            />
        </FrontLayout>
    );
}
