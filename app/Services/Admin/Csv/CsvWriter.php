<?php

declare(strict_types=1);

namespace App\Services\Admin\Csv;

use Symfony\Component\HttpFoundation\StreamedResponse;

class CsvWriter
{
    /** Excel が UTF-8 と判定できるようにする */
    private const BOM = "\xEF\xBB\xBF";

    /**
     * 全件をメモリに溜めないよう、行を1件ずつ書き出しながらレスポンスを流す。
     *
     * @param  array<int, string>  $header
     * @param  iterable<array<int, string|int|null>>  $rows
     */
    public function stream(string $filename, array $header, iterable $rows): StreamedResponse
    {
        return new StreamedResponse(
            function () use ($header, $rows): void {
                $output = fopen('php://output', 'w');
                $buffer = fopen('php://temp', 'r+');

                fwrite($output, self::BOM);
                fwrite($output, $this->toLine($buffer, $header));

                foreach ($rows as $row) {
                    fwrite($output, $this->toLine($buffer, $row));
                }

                fclose($buffer);
                fclose($output);
            },
            200,
            [
                'Content-Type' => 'text/csv; charset=UTF-8',
                'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            ],
        );
    }

    /**
     * `fputcsv` は改行に LF を使うため、Excel 向けに CRLF へ置き換える。
     *
     * @param  resource  $buffer
     * @param  array<int, string|int|null>  $row
     */
    private function toLine($buffer, array $row): string
    {
        rewind($buffer);
        ftruncate($buffer, 0);

        fputcsv($buffer, $row);

        rewind($buffer);
        $line = (string) stream_get_contents($buffer);

        return rtrim($line, "\r\n")."\r\n";
    }
}
