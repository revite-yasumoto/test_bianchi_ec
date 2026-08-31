import { PrivacyBody } from '@/front/Components/Support/PrivacyBody';
import { TermsBody } from '@/front/Components/Support/TermsBody';
import { Button } from '@/shared/Components/Button';
import { Modal } from '@/shared/Components/Modal';

export const LEGAL_DOCUMENTS = {
    Terms: 'terms',
    Privacy: 'privacy',
} as const;

export type LegalDocument =
    (typeof LEGAL_DOCUMENTS)[keyof typeof LEGAL_DOCUMENTS];

const TITLES: Record<LegalDocument, string> = {
    [LEGAL_DOCUMENTS.Terms]: '利用規約',
    [LEGAL_DOCUMENTS.Privacy]: 'プライバシーポリシー',
};

type LegalModalProps = {
    /** 表示する文書。null なら閉じた状態 */
    openDocument: LegalDocument | null;
    onClose: () => void;
};

/** 会員登録の同意チェックから、画面遷移せずに規約・ポリシーの本文を読ませるためのモーダル */
export function LegalModal({ openDocument, onClose }: LegalModalProps) {
    return (
        <Modal
            isOpen={openDocument !== null}
            title={openDocument === null ? '' : TITLES[openDocument]}
            onClose={onClose}
            className="max-w-[640px]"
        >
            {/* スクロールをモーダル本体ではなく本文に持たせる（本体側だと角丸の上にスクロールバーが乗る） */}
            <div className="max-h-[62dvh] overflow-y-auto pr-3">
                {openDocument === LEGAL_DOCUMENTS.Terms ? (
                    <TermsBody headingLevel={3} />
                ) : null}
                {openDocument === LEGAL_DOCUMENTS.Privacy ? (
                    <PrivacyBody headingLevel={3} />
                ) : null}
            </div>

            <div className="mt-5 flex justify-center">
                <Button variant="outline" onClick={onClose} className="w-1/2">
                    閉じる
                </Button>
            </div>
        </Modal>
    );
}
