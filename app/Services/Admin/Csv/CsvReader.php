<?php

declare(strict_types=1);

namespace App\Services\Admin\Csv;

use Illuminate\Http\UploadedFile;

class CsvReader
{
    private const BOM = "\xEF\xBB\xBF";

    /**
     * ヘッダー行を読み飛ばし、2行目以降を「実ファイル上の行番号 => 列の配列」で返す。
     * 行番号はエラー表示でそのまま使えるよう1始まりにする。
     *
     * @return array<int, array<int, string>>
     */
    public function read(UploadedFile $file): array
    {
        $stream = $this->toUtf8Stream((string) file_get_contents($file->getRealPath()));

        $rows = [];
        $lineNumber = 0;

        while (($columns = fgetcsv($stream)) !== false) {
            $lineNumber++;

            if ($lineNumber === 1) {
                continue;
            }

            if ($this->isBlank($columns)) {
                continue;
            }

            $rows[$lineNumber] = array_map(
                fn (?string $value): string => trim((string) $value),
                $columns,
            );
        }

        fclose($stream);

        return $rows;
    }

    /**
     * Excel から出力されたCSVは Shift_JIS のことがあるため、UTF-8 へ寄せてから読む。
     *
     * @return resource
     */
    private function toUtf8Stream(string $content)
    {
        if (str_starts_with($content, self::BOM)) {
            $content = substr($content, strlen(self::BOM));
        }

        $content = (string) mb_convert_encoding($content, 'UTF-8', ['UTF-8', 'SJIS-win']);

        $stream = fopen('php://temp', 'r+');
        fwrite($stream, $content);
        rewind($stream);

        return $stream;
    }

    /**
     * @param  array<int, string|null>  $columns
     */
    private function isBlank(array $columns): bool
    {
        foreach ($columns as $value) {
            if (trim((string) $value) !== '') {
                return false;
            }
        }

        return true;
    }
}
