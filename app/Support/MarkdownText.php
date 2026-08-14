<?php

declare(strict_types=1);

namespace App\Support;

class MarkdownText
{
    /**
     * 利用者が入力した自由記述を Markdown メールへ埋め込める形に整える。
     * Blade の `{{ }}` は HTML だけを無害化して Markdown 記法をそのまま通すため、
     * 本文中のリンク記法が表示文字と異なる URL を指したり、行頭の記号が見出し・
     * リストに化けたりするのを防ぐ。
     */
    public static function escape(string $text): string
    {
        $escaped = (string) preg_replace('/([\\\\`*_{}\[\]()#+\-.!|>~])/', '\\\\$1', $text);
        $normalized = str_replace(["\r\n", "\r"], "\n", $escaped);

        // Markdown のソフト改行は空白1つに畳まれるため、行末に空白2つを置いて改行を保つ
        return str_replace("\n", "  \n", $normalized);
    }
}
