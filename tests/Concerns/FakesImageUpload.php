<?php

declare(strict_types=1);

namespace Tests\Concerns;

use Illuminate\Http\UploadedFile;

trait FakesImageUpload
{
    /**
     * GD拡張が無い環境でも `image` / `dimensions` ルールを通せるよう、1x1のPNG実バイナリを使う。
     */
    protected function fakeImage(string $name): UploadedFile
    {
        $png = base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==',
            true,
        );

        return UploadedFile::fake()->createWithContent($name, (string) $png);
    }
}
