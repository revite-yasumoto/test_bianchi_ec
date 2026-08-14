<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

/**
 * 郵便番号から住所を引けなかった状態を表す。該当する住所が無い場合ではなく、
 * 外部サービスへ到達できない・応答を解釈できない場合に投げる。
 */
class PostalCodeLookupFailedException extends RuntimeException {}
