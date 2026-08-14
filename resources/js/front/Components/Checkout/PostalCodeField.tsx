import { useRef } from 'react';
import { cn } from '@/lib/utils';
import { usePostalCodeLookup } from '@/front/hooks/usePostalCodeLookup';
import {
    joinPostalCode,
    splitPostalCode,
    toDigits,
    type PostalCodeLookupResult,
} from '@/front/lib/postalCode';
import { FIELD_CONTROL_CLASS } from '@/shared/Components/FormField';

type PostalCodeFieldProps = {
    id: string;
    /** 保存する形（`123-4567`）の郵便番号 */
    value: string;
    error?: string;
    onChange: (postalCode: string) => void;
    /** 住所を引けたときだけ呼ぶ */
    onResolved: (result: PostalCodeLookupResult) => void;
};

/**
 * 郵便番号を前半3桁・後半4桁で受け取り、7桁が揃った時点で住所を引く。
 * 引けなかった場合も入力値は変えず、手入力で先へ進めるようにする。
 */
export function PostalCodeField({
    id,
    value,
    error,
    onChange,
    onResolved,
}: PostalCodeFieldProps) {
    const { first, second } = splitPostalCode(value);
    const { status, lookup, reset } = usePostalCodeLookup(onResolved);
    const secondInputRef = useRef<HTMLInputElement>(null);

    const handleChange = (nextFirst: string, nextSecond: string): void => {
        onChange(joinPostalCode(nextFirst, nextSecond));

        const digits = `${nextFirst}${nextSecond}`;

        if (digits.length < 7) {
            reset();

            return;
        }

        lookup(digits);
    };

    const messageIds = [
        error ? `${id}-error` : '',
        status === 'idle' ? '' : `${id}-status`,
    ].filter((messageId) => messageId !== '');
    const controlClass = cn(FIELD_CONTROL_CLASS, error && 'border-coral');
    const inputProps = {
        'aria-invalid': error ? true : undefined,
        'aria-describedby':
            messageIds.length > 0 ? messageIds.join(' ') : undefined,
        required: true,
        inputMode: 'numeric' as const,
    };

    return (
        <fieldset className="min-w-0">
            <legend className="mb-1.5 block text-xs font-bold text-ink">
                郵便番号
                <span className="ml-1 text-coral" aria-hidden="true">
                    *
                </span>
            </legend>

            <div className="flex items-center gap-2">
                <input
                    id={id}
                    aria-label="郵便番号 前半3桁"
                    placeholder="150"
                    value={first}
                    onChange={(event) => {
                        const digits = toDigits(event.target.value);

                        // 7桁をまとめて貼り付けたときは前半・後半へ振り分ける
                        if (digits.length > 3) {
                            handleChange(
                                digits.slice(0, 3),
                                digits.slice(3, 7),
                            );
                        } else {
                            handleChange(digits, second);
                        }

                        if (digits.length >= 3 && first.length < 3) {
                            secondInputRef.current?.focus();
                        }
                    }}
                    className={cn(controlClass, 'w-20 text-center')}
                    {...inputProps}
                />
                <span className="text-ink2" aria-hidden="true">
                    -
                </span>
                <input
                    id={`${id}-second`}
                    ref={secondInputRef}
                    aria-label="郵便番号 後半4桁"
                    placeholder="0041"
                    value={second}
                    onChange={(event) => {
                        const digits = toDigits(event.target.value);

                        if (digits.length > 4) {
                            handleChange(
                                digits.slice(0, 3),
                                digits.slice(3, 7),
                            );
                        } else {
                            handleChange(first, digits);
                        }
                    }}
                    className={cn(controlClass, 'w-24 text-center')}
                    {...inputProps}
                />
            </div>

            {error ? (
                <p
                    id={`${id}-error`}
                    className="mt-1.5 text-[11.5px] font-bold text-coral"
                >
                    {error}
                </p>
            ) : null}
            {status === 'idle' ? null : (
                <p
                    id={`${id}-status`}
                    role="status"
                    className="mt-1.5 text-[11.5px] text-ink2"
                >
                    {status === 'loading'
                        ? '住所を検索しています…'
                        : '該当する住所が見つかりませんでした。住所を手入力してください。'}
                </p>
            )}
        </fieldset>
    );
}
