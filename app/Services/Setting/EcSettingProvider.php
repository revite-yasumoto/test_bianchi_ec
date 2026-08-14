<?php

declare(strict_types=1);

namespace App\Services\Setting;

use App\Models\EcSetting;

class EcSettingProvider
{
    /** EC基本設定は単一行で、idは常に1 */
    private const SETTING_ID = 1;

    private ?EcSetting $setting = null;

    /**
     * 送料計算・注文確定で同一リクエスト中に繰り返し参照されるため、インスタンス内で保持する。
     * 設定変更を即時反映させるため、アプリケーションキャッシュには載せない。
     */
    public function get(): EcSetting
    {
        return $this->setting ??= EcSetting::query()->findOrFail(self::SETTING_ID);
    }
}
