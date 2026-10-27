<?php

declare(strict_types=1);

namespace App\Services\Admin\Banner;

use App\Models\Banner;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class BannerSaveService
{
    private const DIRECTORY = 'banners';

    /** 画像の入力キーと保存先カラムの対応（PC用・スマホ用） */
    private const IMAGE_FIELDS = [
        'pc_image' => 'pc_image_path',
        'sp_image' => 'sp_image_path',
    ];

    /**
     * @param  array<string, mixed>  $data  SaveBannerRequest で検証済みの値
     */
    public function create(array $data): Banner
    {
        $attributes = $this->attributes($data);

        foreach (self::IMAGE_FIELDS as $input => $column) {
            $file = $data[$input] ?? null;
            $attributes[$column] = $file instanceof UploadedFile ? $this->store($file) : null;
        }

        return Banner::query()->create($attributes);
    }

    /**
     * @param  array<string, mixed>  $data  SaveBannerRequest で検証済みの値
     */
    public function update(Banner $banner, array $data): void
    {
        $attributes = $this->attributes($data);
        $obsoletePaths = [];

        foreach (self::IMAGE_FIELDS as $input => $column) {
            $file = $data[$input] ?? null;
            $currentPath = $banner->{$column};

            if ($file instanceof UploadedFile) {
                $attributes[$column] = $this->store($file);
            } elseif (filter_var($data['remove_'.$input] ?? false, FILTER_VALIDATE_BOOLEAN)) {
                $attributes[$column] = null;
            } else {
                continue;
            }

            if ($currentPath !== null) {
                $obsoletePaths[] = $currentPath;
            }
        }

        $banner->update($attributes);

        // 更新に失敗したときに表示中の画像を失わないよう、旧ファイルはレコードの更新後に消す
        Storage::disk('public')->delete($obsoletePaths);
    }

    public function delete(Banner $banner): void
    {
        $paths = array_values(array_filter([$banner->pc_image_path, $banner->sp_image_path]));

        $banner->delete();

        Storage::disk('public')->delete($paths);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function attributes(array $data): array
    {
        return [
            'tag' => $data['tag'],
            'title' => $data['title'],
            'subtitle' => $data['subtitle'] ?? null,
            'background' => $data['background'],
            'product_id' => $data['product_id'] ?? null,
            'link_url' => $data['link_url'] ?? null,
            'sort_order' => $data['sort_order'],
            // multipart で送られる真偽値は '1' / '0' の文字列になるため、boolean ルールと同じ解釈で読む
            'is_active' => filter_var($data['is_active'], FILTER_VALIDATE_BOOLEAN),
        ];
    }

    private function store(UploadedFile $file): string
    {
        return $file->store(self::DIRECTORY, 'public');
    }
}
