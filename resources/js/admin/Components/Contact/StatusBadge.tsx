import { Badge } from '@/shared/Components/Badge';
import type { Tone } from '@/shared/lib/tone';

type StatusBadgeProps = {
    label: string;
    /** 配色は `App\Enums\ContactStatus::color()` が正本。サーバーから受け取る */
    tone: Tone;
};

export function StatusBadge({ label, tone }: StatusBadgeProps) {
    return <Badge tone={tone}>{label}</Badge>;
}
