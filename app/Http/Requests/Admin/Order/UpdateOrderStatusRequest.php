<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Order;

use App\Enums\OrderStatus;
use App\Models\Order;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateOrderStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'status' => ['required', Rule::enum(OrderStatus::class)],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->has('status')) {
                return;
            }

            $this->validateTransition($validator);
        });
    }

    /**
     * 遷移の可否は `OrderStatus::canTransitionTo()` が単一情報源。
     */
    private function validateTransition(Validator $validator): void
    {
        /** @var Order $order */
        $order = $this->route('order');
        $to = $this->enum('status', OrderStatus::class);

        if ($to === null) {
            return;
        }

        if ($order->status === $to) {
            $validator->errors()->add('status', '現在と同じステータスには変更できません。');

            return;
        }

        if ($order->status->allowedTransitions() === []) {
            $validator->errors()->add('status', 'このステータスからは変更できません。');

            return;
        }

        if (! $order->status->canTransitionTo($to)) {
            $validator->errors()->add(
                'status',
                "「{$order->status->label()}」から「{$to->label()}」へは変更できません。",
            );
        }
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'status' => 'ステータス',
        ];
    }
}
