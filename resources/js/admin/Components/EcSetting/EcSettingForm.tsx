import { useForm } from '@inertiajs/react';
import { useState } from 'react';
import type { FormEventHandler } from 'react';
import { ConfirmDialog } from '@/admin/Components/ConfirmDialog';
import { useAdminToast } from '@/admin/Layouts/AdminLayout';

export type EcSettingFormProps = {
    setting: {
        free_shipping_threshold: number;
        cod_fee: number;
        bank_transfer_note: string;
    };
};

type EcSettingFields = {
    free_shipping_threshold: string;
    cod_fee: string;
    bank_transfer_note: string;
};

const CARD_CLASS = 'rounded-xl border border-admin-line bg-white p-5';

const LABEL_CLASS = 'mb-1.5 block text-[11.5px] font-bold text-admin-ink';

const FIELD_CLASS =
    'w-full rounded-lg border border-admin-line px-3 py-2 text-base';

const HINT_CLASS = 'mt-1.5 text-[11px] text-admin-ink-muted';

const ERROR_CLASS = 'mt-1 text-[11px] font-bold text-admin-danger';

export function EcSettingForm({ setting }: EcSettingFormProps) {
    const { showToast } = useAdminToast();
    const [isConfirmOpen, setIsConfirmOpen] = useState(false);
    const { data, setData, put, processing, errors, transform } =
        useForm<EcSettingFields>({
            free_shipping_threshold: String(setting.free_shipping_threshold),
            cod_fee: String(setting.cod_fee),
            bank_transfer_note: setting.bank_transfer_note,
        });

    transform((form) => ({
        free_shipping_threshold: Number(form.free_shipping_threshold),
        cod_fee: Number(form.cod_fee),
        bank_transfer_note: form.bank_transfer_note,
    }));

    const handleSubmit: FormEventHandler = (event) => {
        event.preventDefault();
        setIsConfirmOpen(true);
    };

    const submit = () => {
        setIsConfirmOpen(false);

        put(route('admin.ec-settings.update'), {
            preserveScroll: true,
            onSuccess: () => showToast('設定を保存しました'),
        });
    };

    return (
        <form
            onSubmit={handleSubmit}
            className="flex max-w-[640px] flex-col gap-4"
        >
            <section className={CARD_CLASS}>
                <h2 className="mb-3.5 text-[13px] font-extrabold text-admin-ink">
                    金額設定
                </h2>

                <div className="grid grid-cols-[repeat(auto-fit,minmax(200px,1fr))] gap-3.5">
                    <div>
                        <label
                            htmlFor="free_shipping_threshold"
                            className={LABEL_CLASS}
                        >
                            送料無料となる購入金額
                        </label>
                        <input
                            id="free_shipping_threshold"
                            type="number"
                            min={0}
                            max={1000000}
                            value={data.free_shipping_threshold}
                            className={FIELD_CLASS}
                            aria-invalid={
                                errors.free_shipping_threshold !== undefined
                            }
                            onChange={(event) =>
                                setData(
                                    'free_shipping_threshold',
                                    event.target.value,
                                )
                            }
                        />
                        <p className={HINT_CLASS}>
                            商品合計（税込）がこの金額以上のとき送料を無料にします。初期値
                            10,000円
                        </p>
                        {errors.free_shipping_threshold ? (
                            <p className={ERROR_CLASS}>
                                {errors.free_shipping_threshold}
                            </p>
                        ) : null}
                    </div>

                    <div>
                        <label htmlFor="cod_fee" className={LABEL_CLASS}>
                            代引き手数料
                        </label>
                        <input
                            id="cod_fee"
                            type="number"
                            min={0}
                            max={10000}
                            value={data.cod_fee}
                            className={FIELD_CLASS}
                            aria-invalid={errors.cod_fee !== undefined}
                            onChange={(event) =>
                                setData('cod_fee', event.target.value)
                            }
                        />
                        <p className={HINT_CLASS}>初期値 330円</p>
                        {errors.cod_fee ? (
                            <p className={ERROR_CLASS}>{errors.cod_fee}</p>
                        ) : null}
                    </div>
                </div>
            </section>

            <section className={CARD_CLASS}>
                <h2 className="mb-2 text-[13px] font-extrabold text-admin-ink">
                    銀行振込の案内文
                </h2>
                <p className="mb-3 text-[11.5px] text-admin-ink-muted">
                    銀行振込を選んだ注文の完了画面と注文詳細に表示されます。
                </p>
                <label htmlFor="bank_transfer_note" className="sr-only">
                    銀行振込の案内文
                </label>
                <textarea
                    id="bank_transfer_note"
                    rows={5}
                    value={data.bank_transfer_note}
                    className={FIELD_CLASS}
                    aria-invalid={errors.bank_transfer_note !== undefined}
                    onChange={(event) =>
                        setData('bank_transfer_note', event.target.value)
                    }
                />
                {errors.bank_transfer_note ? (
                    <p className={ERROR_CLASS}>{errors.bank_transfer_note}</p>
                ) : null}
            </section>

            <button
                type="submit"
                disabled={processing}
                className="self-start rounded-lg bg-admin-brand px-7 py-3.5 text-[13.5px] font-extrabold text-white disabled:opacity-60"
            >
                設定を保存
            </button>

            <ConfirmDialog
                isOpen={isConfirmOpen}
                title="EC基本設定の保存"
                message="変更内容はフロント側の送料計算・金額表示に即時反映されます。確定済みの注文の金額は変わりません。"
                confirmLabel="保存する"
                onConfirm={submit}
                onCancel={() => setIsConfirmOpen(false)}
            />
        </form>
    );
}
