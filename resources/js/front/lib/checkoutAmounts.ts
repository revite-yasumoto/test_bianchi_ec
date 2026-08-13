import { PaymentMethod } from '@/shared/lib/enums';

export type ShippingOption = {
    fee: number;
    delivery_days: number;
};

export type EcSettingAmounts = {
    free_shipping_threshold: number;
    cod_fee: number;
};

export type CheckoutAmounts = {
    subtotal: number;
    shippingFee: number;
    codFee: number;
    total: number;
    deliveryDays: number;
};

/**
 * 配送先・支払い方法を切り替えたときの金額を即時に算出する。
 * 算出規則は `App\Services\Shipping\ShippingCalculator` と揃えており、
 * ここでの結果は表示専用。注文確認と注文確定ではサーバー側で再計算した値を使う。
 */
export function calculateCheckoutAmounts(
    subtotal: number,
    shipping: ShippingOption | undefined,
    paymentMethod: PaymentMethod,
    ecSetting: EcSettingAmounts,
): CheckoutAmounts {
    const shippingFee =
        subtotal >= ecSetting.free_shipping_threshold
            ? 0
            : (shipping?.fee ?? 0);
    const codFee = paymentMethod === PaymentMethod.Cod ? ecSetting.cod_fee : 0;

    return {
        subtotal,
        shippingFee,
        codFee,
        total: subtotal + shippingFee + codFee,
        deliveryDays: shipping?.delivery_days ?? 0,
    };
}
