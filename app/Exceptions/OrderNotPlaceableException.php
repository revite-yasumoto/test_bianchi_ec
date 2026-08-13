<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

/**
 * 注文確定を続行できない状態を表す。メッセージはそのまま会員に提示するため、内部情報を含めない。
 */
class OrderNotPlaceableException extends RuntimeException
{
    /**
     * @param  string  $redirectRouteName  やり直しの起点になる画面。カートの内容が原因ならカート、選択内容が原因なら購入手続き
     */
    public function __construct(string $message, public readonly string $redirectRouteName = 'cart.index')
    {
        parent::__construct($message);
    }
}
