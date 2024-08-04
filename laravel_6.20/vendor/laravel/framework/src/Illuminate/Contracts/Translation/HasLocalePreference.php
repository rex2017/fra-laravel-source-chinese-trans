<?php
/**
 * 契约，翻译本地可选项接口
 */

namespace Illuminate\Contracts\Translation;

interface HasLocalePreference
{
    /**
     * Get the preferred locale of the entity.
	 * 得到更好的实体
     *
     * @return string|null
     */
    public function preferredLocale();
}
