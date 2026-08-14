import { useForm } from '@inertiajs/react';
import { useState } from 'react';
import type { FormEventHandler } from 'react';
import { ConfirmDialog } from '@/admin/Components/ConfirmDialog';
import {
    ShippingSettingRow,
    type ShippingSettingRowData,
} from './ShippingSettingRow';
import { useAdminToast } from '@/admin/Layouts/AdminLayout';

export type ShippingSettingEditorProps = {
    settings: ShippingSettingRowData[];
};

type SettingInput = {
    id: number;
    fee: string;
    delivery_days: string;
};

type ShippingSettingsForm = {
    settings: SettingInput[];
};

function toInputs(settings: ShippingSettingRowData[]): SettingInput[] {
    return settings.map((setting) => ({
        id: setting.id,
        fee: String(setting.fee),
        delivery_days: String(setting.delivery_days),
    }));
}

export function ShippingSettingEditor({
    settings,
}: ShippingSettingEditorProps) {
    const { showToast } = useAdminToast();
    const [isConfirmOpen, setIsConfirmOpen] = useState(false);
    const { data, setData, put, processing, errors, transform } =
        useForm<ShippingSettingsForm>({ settings: toInputs(settings) });

    // 47件の各行のエラーは `settings.0.fee` のようなキーで返るため、キー名で引ける形にする
    const fieldErrors = errors as Record<string, string>;

    transform((form) => ({
        settings: form.settings.map((setting) => ({
            id: setting.id,
            fee: Number(setting.fee),
            delivery_days: Number(setting.delivery_days),
        })),
    }));

    const updateInput = (index: number, changes: Partial<SettingInput>) => {
        setData(
            'settings',
            data.settings.map((setting, currentIndex) =>
                currentIndex === index ? { ...setting, ...changes } : setting,
            ),
        );
    };

    const handleSubmit: FormEventHandler = (event) => {
        event.preventDefault();
        setIsConfirmOpen(true);
    };

    const submit = () => {
        setIsConfirmOpen(false);

        put(route('admin.shipping-settings.update'), {
            preserveScroll: true,
            onSuccess: () => showToast('設定を保存しました'),
        });
    };

    return (
        <form onSubmit={handleSubmit}>
            <div className="flex flex-wrap items-center gap-3 rounded-xl border border-admin-line bg-white px-5 py-4">
                <p className="text-xs leading-relaxed text-admin-ink-muted">
                    47都道府県ごとに送料と配送予定日数を設定します。初期値：北海道・沖縄
                    1,000円／その他 500円。
                </p>
                <button
                    type="submit"
                    disabled={processing}
                    className="ml-auto rounded-lg bg-admin-brand px-5 py-2.5 text-[12.5px] font-extrabold whitespace-nowrap text-white disabled:opacity-60"
                >
                    設定を保存
                </button>
            </div>

            {Object.keys(fieldErrors).length > 0 ? (
                <p
                    role="alert"
                    className="mt-3.5 rounded-xl border border-admin-danger bg-white px-5 py-3 text-[12px] font-bold text-admin-danger"
                >
                    入力内容に誤りがあります。赤枠の都道府県を確認してください（送料は0〜100,000円、配送予定日数は1〜30日）。
                </p>
            ) : null}

            <div className="mt-3.5 rounded-xl border border-admin-line bg-white px-5 py-4">
                <ul className="grid grid-cols-[repeat(auto-fill,minmax(230px,1fr))] gap-2.5">
                    {settings.map((setting, index) => (
                        <ShippingSettingRow
                            key={setting.id}
                            row={setting}
                            fee={data.settings[index]?.fee ?? ''}
                            deliveryDays={
                                data.settings[index]?.delivery_days ?? ''
                            }
                            feeError={fieldErrors[`settings.${index}.fee`]}
                            deliveryDaysError={
                                fieldErrors[`settings.${index}.delivery_days`]
                            }
                            onChangeFee={(value) =>
                                updateInput(index, { fee: value })
                            }
                            onChangeDeliveryDays={(value) =>
                                updateInput(index, { delivery_days: value })
                            }
                        />
                    ))}
                </ul>
            </div>

            <ConfirmDialog
                isOpen={isConfirmOpen}
                title="送料設定の保存"
                message="変更内容はフロント側の送料計算・金額表示に即時反映されます。確定済みの注文の金額は変わりません。"
                confirmLabel="保存する"
                onConfirm={submit}
                onCancel={() => setIsConfirmOpen(false)}
            />
        </form>
    );
}
