<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Banner;

use App\Enums\BannerBackground;
use App\Support\ImageUploadRules;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * 作成・更新で入力項目・ルールが同一のため、両方で使う。
 */
class SaveBannerRequest extends FormRequest
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
            'tag' => ['required', 'string', 'max:50'],
            'title' => ['required', 'string', 'max:255'],
            'subtitle' => ['nullable', 'string', 'max:255'],
            'background' => ['required', Rule::enum(BannerBackground::class)],
            'product_id' => ['nullable', 'integer', Rule::exists('products', 'id')],
            // `//example.com` や `/\example.com` はブラウザが外部ホストとして解釈するため、サイト内パスとして扱わない。
            // ブラウザは URL の解析前にタブ・改行を取り除くため（`/\t/example.com` → `//example.com`）、制御文字も拒否する
            'link_url' => ['nullable', 'string', 'max:255', 'regex:/\A\/(?![\/\\\\])[^\x00-\x1F\x7F]*\z/'],
            'sort_order' => ['required', 'integer', 'min:0', 'max:9999'],
            'is_active' => ['required', 'boolean'],
            'pc_image' => ['nullable', ...ImageUploadRules::file()],
            'sp_image' => ['nullable', ...ImageUploadRules::file()],
            'remove_pc_image' => ['nullable', 'boolean'],
            'remove_sp_image' => ['nullable', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'tag' => '小ラベル',
            'title' => '見出し',
            'subtitle' => 'サブ見出し',
            'background' => '背景色',
            'product_id' => '訴求商品',
            'link_url' => 'リンク先',
            'sort_order' => '表示順',
            'is_active' => '公開状態',
            'pc_image' => 'PC用画像',
            'sp_image' => 'スマホ用画像',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'link_url.regex' => 'リンク先は「/」で始まるサイト内のパスを入力してください。',
        ];
    }
}
