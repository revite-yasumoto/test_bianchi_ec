import { Link } from '@inertiajs/react';
import { AdminLayout } from '@/admin/Layouts/AdminLayout';
import { HandlingCard } from '@/admin/Components/Contact/HandlingCard';
import { StatusBadge } from '@/admin/Components/Contact/StatusBadge';
import { cn } from '@/lib/utils';
import type { Tone } from '@/shared/lib/tone';

type ContactProduct = { id: number; name: string; is_published: boolean };

type Props = {
    contact: {
        id: number;
        contact_number: string;
        received_at: string;
        name: string;
        email: string;
        body: string;
        /** 未ログインからの送信は null */
        member_code: string | null;
        /** 商品が削除されると null になり、商品名と商品識別コードは保存値が残る */
        product: ContactProduct | null;
        product_name: string | null;
        product_code: string | null;
        status: string;
        status_label: string;
        status_tone: Tone;
        admin_note: string | null;
        handled_at: string | null;
        handled_admin_name: string | null;
    };
    statusOptions: { value: string; label: string }[];
};

const CARD_CLASS = 'rounded-xl border border-admin-line bg-white p-5';

const CARD_HEADING_CLASS = 'mb-2.5 text-[13px] font-extrabold text-admin-ink';

const NOTE_CLASS = 'text-[11px] leading-relaxed text-admin-ink-muted';

export default function Show({ contact, statusOptions }: Props) {
    return (
        <AdminLayout title="お問い合わせ詳細">
            <Link
                href={route('admin.contacts.index')}
                className="text-[12.5px] font-bold text-admin-brand"
            >
                ← お問い合わせ一覧へ
            </Link>

            <div className="mt-3 flex flex-wrap items-center gap-3">
                <StatusBadge
                    label={contact.status_label}
                    tone={contact.status_tone}
                />
                <p className="text-[12.5px] text-admin-ink-muted">
                    受信日時 {contact.received_at}
                </p>
                <p className="ml-auto font-mono text-[11px] text-admin-ink-muted">
                    {contact.contact_number}
                </p>
            </div>

            <div className="mt-3.5 grid grid-cols-[minmax(320px,1fr)_minmax(280px,360px)] gap-3.5">
                <section className={CARD_CLASS}>
                    <h2 className={CARD_HEADING_CLASS}>お問い合わせ内容</h2>
                    <p className="text-[13px] leading-[1.9] whitespace-pre-wrap text-admin-ink">
                        {contact.body}
                    </p>
                    <p
                        className={cn(
                            'mt-4 border-t border-admin-line pt-3',
                            NOTE_CLASS,
                        )}
                    >
                        送信者が入力した内容は管理画面から編集・削除できません。
                    </p>
                </section>

                <div className="flex flex-col gap-3.5">
                    <section className={CARD_CLASS}>
                        <h2 className={CARD_HEADING_CLASS}>送信者</h2>
                        <dl className="flex flex-col gap-1.5 text-[12.5px]">
                            <div>
                                <dt className="sr-only">お名前</dt>
                                <dd className="font-bold">{contact.name}</dd>
                            </div>
                            <div>
                                <dt className="sr-only">メールアドレス</dt>
                                <dd>
                                    <a
                                        href={`mailto:${contact.email}`}
                                        className="text-admin-brand"
                                    >
                                        {contact.email}
                                    </a>
                                </dd>
                            </div>
                            <div>
                                <dt className="sr-only">会員</dt>
                                <dd
                                    className={cn(
                                        contact.member_code
                                            ? 'text-admin-ink-muted'
                                            : 'font-bold text-admin-danger',
                                    )}
                                >
                                    {contact.member_code
                                        ? `会員 ${contact.member_code}`
                                        : '会員登録なし（未ログインからの送信）'}
                                </dd>
                            </div>
                        </dl>

                        <h3 className="mt-3.5 mb-1.5 border-t border-admin-line pt-3.5 text-[12.5px] font-bold text-admin-ink">
                            対象商品
                        </h3>
                        <TargetProduct
                            product={contact.product}
                            productName={contact.product_name}
                            productCode={contact.product_code}
                        />
                    </section>

                    <HandlingCard
                        contactId={contact.id}
                        contactNumber={contact.contact_number}
                        currentStatus={contact.status}
                        statusOptions={statusOptions}
                        adminNote={contact.admin_note}
                        handledAt={contact.handled_at}
                        handledAdminName={contact.handled_admin_name}
                    />
                </div>
            </div>
        </AdminLayout>
    );
}

type TargetProductProps = {
    product: ContactProduct | null;
    productName: string | null;
    productCode: string | null;
};

/**
 * 商品識別コードが残っていて商品が引けない場合は「削除済み」、コードも無い場合は「手入力」と判別する
 * （判別規則は docs/front/contact.md が正本）。
 */
function TargetProduct({
    product,
    productName,
    productCode,
}: TargetProductProps) {
    // 非公開の商品はフロントの商品詳細が404を返すため、リンクにしない
    if (product?.is_published) {
        return (
            <>
                <a
                    href={route('products.show', [product.id])}
                    target="_blank"
                    rel="noopener noreferrer"
                    className="text-[12.5px] font-bold text-admin-brand"
                >
                    {product.name} →
                </a>
                {productCode ? (
                    <p className="mt-1 font-mono text-[10.5px] text-admin-ink-muted">
                        {productCode}
                    </p>
                ) : null}
            </>
        );
    }

    if (productName === null) {
        return <p className="text-[12.5px] text-admin-ink-muted">なし</p>;
    }

    return (
        <>
            <p className="text-[12.5px] text-admin-ink">{productName}</p>
            {productCode ? (
                <p className="mt-1 font-mono text-[10.5px] text-admin-ink-muted">
                    {productCode}
                </p>
            ) : null}
            <p className={cn('mt-1', NOTE_CLASS)}>
                {noteOf(product, productCode)}
            </p>
        </>
    );
}

function noteOf(
    product: ContactProduct | null,
    productCode: string | null,
): string {
    if (product !== null) {
        return '※ 商品が非公開のため、商品詳細へのリンクはありません';
    }

    return productCode !== null
        ? '※ 商品が削除済みのため、送信時に保存された商品名を表示しています'
        : '※ 商品詳細を経由しない手入力のため、商品へのリンクはありません';
}
