/** バックエンドの `App\Enums\*` と値を揃える区分値定義 */

export const SpecOptionType = {
    Size: 'size',
    Color: 'color',
} as const;

export type SpecOptionType =
    (typeof SpecOptionType)[keyof typeof SpecOptionType];

export const PaymentMethod = {
    BankTransfer: 'bank_transfer',
    Cod: 'cod',
} as const;

export type PaymentMethod = (typeof PaymentMethod)[keyof typeof PaymentMethod];

export const UserStatus = {
    Active: 'active',
    Suspended: 'suspended',
    Withdrawn: 'withdrawn',
} as const;

export type UserStatus = (typeof UserStatus)[keyof typeof UserStatus];

export const PriceRange = {
    Under10000: 'under_10000',
    From10000: 'from_10000',
    From50000: 'from_50000',
    From150000: 'from_150000',
} as const;

export type PriceRange = (typeof PriceRange)[keyof typeof PriceRange];

export const OrderStatus = {
    Received: 'received',
    AwaitingPayment: 'awaiting_payment',
    PaymentConfirmed: 'payment_confirmed',
    Preparing: 'preparing',
    Shipped: 'shipped',
    Cancelled: 'cancelled',
} as const;

export type OrderStatus = (typeof OrderStatus)[keyof typeof OrderStatus];

export const ContactStatus = {
    Unhandled: 'unhandled',
    InProgress: 'in_progress',
    Handled: 'handled',
} as const;

export type ContactStatus = (typeof ContactStatus)[keyof typeof ContactStatus];

/** お問い合わせ管理の一覧タブ。`product_id` の有無で振り分ける */
export const ContactTab = {
    General: 'general',
    Product: 'product',
} as const;

export type ContactTab = (typeof ContactTab)[keyof typeof ContactTab];
