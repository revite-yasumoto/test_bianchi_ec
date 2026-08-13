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
} as const;

export type UserStatus = (typeof UserStatus)[keyof typeof UserStatus];
