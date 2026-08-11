import { Link } from '@inertiajs/react';
import { AdminLayout } from '@/admin/Layouts/AdminLayout';
import { MemberStatusCard } from '@/admin/Components/Member/MemberStatusCard';
import { Badge } from '@/shared/Components/Badge';
import { TONE } from '@/shared/lib/tone';
import { UserStatus } from '@/shared/lib/enums';
import { yen } from '@/shared/lib/yen';

type Address = {
    id: number;
    label: string;
    recipient_name: string;
    postal_code: string;
    prefecture_name: string;
    city: string;
    address_line1: string;
    address_line2: string | null;
    tel: string;
    is_default: boolean;
};

type RecentOrder = {
    id: number;
    order_number: string;
    ordered_at: string;
    total: number;
    status_label: string;
};

type Props = {
    member: {
        id: number;
        member_code: string;
        name: string;
        name_kana: string | null;
        email: string;
        tel: string | null;
        registered_on: string;
        status: string;
        status_label: string;
    };
    addresses: Address[];
    recentOrders: RecentOrder[];
};

const CARD_CLASS = 'rounded-xl border border-admin-line bg-white p-5';

const HEADING_CLASS = 'mb-2.5 text-[13px] font-extrabold text-admin-ink';

const DEFINITION_TERM_CLASS = 'text-[11.5px] font-bold text-admin-ink-muted';

export default function Show({ member, addresses, recentOrders }: Props) {
    return (
        <AdminLayout title={`会員 ${member.member_code}`}>
            <Link
                href={route('admin.members.index')}
                className="mb-3.5 inline-block font-mono text-[11px] text-admin-ink-muted"
            >
                ← 会員マスタへ
            </Link>

            <div className="grid grid-cols-[repeat(auto-fit,minmax(300px,1fr))] items-start gap-4">
                <div className="flex flex-col gap-4">
                    <div className={CARD_CLASS}>
                        <div className="mb-2.5 flex items-center gap-3">
                            <h2 className="text-[13px] font-extrabold text-admin-ink">
                                会員情報
                            </h2>
                            <Badge
                                tone={
                                    member.status === UserStatus.Active
                                        ? TONE.positive
                                        : TONE.negative
                                }
                            >
                                {member.status_label}
                            </Badge>
                        </div>

                        <dl className="grid grid-cols-[auto_1fr] gap-x-4 gap-y-2 text-[12.5px]">
                            <dt className={DEFINITION_TERM_CLASS}>会員ID</dt>
                            <dd className="font-mono">{member.member_code}</dd>

                            <dt className={DEFINITION_TERM_CLASS}>氏名</dt>
                            <dd>
                                {member.name}
                                {member.name_kana ? (
                                    <span className="ml-2 text-admin-ink-muted">
                                        （{member.name_kana}）
                                    </span>
                                ) : null}
                            </dd>

                            <dt className={DEFINITION_TERM_CLASS}>
                                メールアドレス
                            </dt>
                            <dd>{member.email}</dd>

                            <dt className={DEFINITION_TERM_CLASS}>電話番号</dt>
                            <dd>{member.tel ?? '—'}</dd>

                            <dt className={DEFINITION_TERM_CLASS}>登録日</dt>
                            <dd>{member.registered_on}</dd>
                        </dl>

                        <p className="mt-3 text-[11.5px] text-admin-ink-muted">
                            会員情報の編集は会員自身がマイページで行います。管理画面からは変更できません。
                        </p>
                    </div>

                    <div className={CARD_CLASS}>
                        <h2 className={HEADING_CLASS}>配送先住所</h2>
                        {addresses.length === 0 ? (
                            <p className="text-[12.5px] text-admin-ink-muted">
                                登録されている配送先はありません。
                            </p>
                        ) : (
                            <ul className="flex flex-col gap-3">
                                {addresses.map((address) => (
                                    <li
                                        key={address.id}
                                        className="border-b border-admin-line pb-3 text-[12.5px] last:border-b-0 last:pb-0"
                                    >
                                        <div className="flex items-center gap-2">
                                            <span className="font-bold">
                                                {address.label}
                                            </span>
                                            {address.is_default ? (
                                                <Badge tone={TONE.info}>
                                                    既定
                                                </Badge>
                                            ) : null}
                                        </div>
                                        <p className="mt-1 leading-loose text-admin-ink-muted">
                                            {address.recipient_name}
                                            <br />〒{address.postal_code}{' '}
                                            {address.prefecture_name}
                                            {address.city}
                                            {address.address_line1}
                                            {address.address_line2 ? (
                                                <>
                                                    <br />
                                                    {address.address_line2}
                                                </>
                                            ) : null}
                                            <br />
                                            {address.tel}
                                        </p>
                                    </li>
                                ))}
                            </ul>
                        )}
                    </div>
                </div>

                <div className="flex flex-col gap-4">
                    <MemberStatusCard
                        memberId={member.id}
                        memberName={member.name}
                        status={member.status}
                    />

                    <div className={CARD_CLASS}>
                        <h2 className={HEADING_CLASS}>直近の注文</h2>
                        {recentOrders.length === 0 ? (
                            <p className="text-[12.5px] text-admin-ink-muted">
                                注文履歴はありません。
                            </p>
                        ) : (
                            <ul className="flex flex-col gap-2">
                                {recentOrders.map((order) => (
                                    <li
                                        key={order.id}
                                        className="flex flex-wrap items-baseline gap-x-3 border-b border-admin-line pb-2 text-[12px] last:border-b-0 last:pb-0"
                                    >
                                        <Link
                                            href={route('admin.orders.show', [
                                                order.id,
                                            ])}
                                            className="font-mono font-bold text-admin-brand"
                                        >
                                            {order.order_number}
                                        </Link>
                                        <span className="text-admin-ink-muted">
                                            {order.ordered_at}
                                        </span>
                                        <span className="text-admin-ink-muted">
                                            {order.status_label}
                                        </span>
                                        <span className="ml-auto font-mono font-bold">
                                            {yen(order.total)}
                                        </span>
                                    </li>
                                ))}
                            </ul>
                        )}
                    </div>
                </div>
            </div>
        </AdminLayout>
    );
}
