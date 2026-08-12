import { Badge } from '@/shared/Components/Badge';

type StockBadgeProps = {
    inStock: boolean;
};

/** 在庫は「在庫あり／在庫切れ」の二値のみを表示する（在庫数は表示しない） */
const TONE = {
    inStock: { fg: '#2b6f64', bg: '#E4F2EF' },
    soldOut: { fg: '#8a4030', bg: '#FBE7E1' },
};

export function StockBadge({ inStock }: StockBadgeProps) {
    return (
        <Badge
            tone={inStock ? TONE.inStock : TONE.soldOut}
            className="px-3.5 py-1.5 text-[12.5px]"
        >
            {inStock ? '在庫あり' : '在庫切れ'}
        </Badge>
    );
}
