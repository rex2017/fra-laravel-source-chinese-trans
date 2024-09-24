<?php
/**
 * 视图，管理翻译
 */

namespace Illuminate\View\Concerns;

trait ManagesTranslations
{
    /**
     * The translation replacements for the translation being rendered.
	 * 翻译替代正在呈现的翻译
     *
     * @var array
     */
    protected $translationReplacements = [];

    /**
     * Start a translation block.
	 * 启动一个翻译块
     *
     * @param  array  $replacements
     * @return void
     */
    public function startTranslation($replacements = [])
    {
        ob_start();

        $this->translationReplacements = $replacements;
    }

    /**
     * Render the current translation.
	 * 渲染当前的翻译
     *
     * @return string
     */
    public function renderTranslation()
    {
        return $this->container->make('translator')->get(
            trim(ob_get_clean()), $this->translationReplacements
        );
    }
}
