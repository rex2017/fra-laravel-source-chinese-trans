<?php
/**
 * 契约，可延期提供者接口
 */

namespace Illuminate\Contracts\Support;

interface DeferrableProvider
{
    /**
     * Get the services provided by the provider.
	 * 得到服务者提供的服务
     *
     * @return array
     */
    public function provides();
}
