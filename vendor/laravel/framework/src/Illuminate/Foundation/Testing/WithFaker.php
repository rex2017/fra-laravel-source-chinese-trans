<?php
/**
 * 基础，使用伪造者
 */

namespace Illuminate\Foundation\Testing;

use Faker\Factory;
use Faker\Generator;

trait WithFaker
{
    /**
     * The Faker instance.
	 * 伪造实例
     *
     * @var \Faker\Generator
     */
    protected $faker;

    /**
     * Setup up the Faker instance.
	 * 设置伪造实例
     *
     * @return void
     */
    protected function setUpFaker()
    {
        $this->faker = $this->makeFaker();
    }

    /**
     * Get the default Faker instance for a given locale.
	 * 得到给定语言环境的默认Faker实例
     *
     * @param  string|null  $locale
     * @return \Faker\Generator
     */
    protected function faker($locale = null)
    {
        return is_null($locale) ? $this->faker : $this->makeFaker($locale);
    }

    /**
     * Create a Faker instance for the given locale.
	 * 创建伪造实例
     *
     * @param  string|null  $locale
     * @return \Faker\Generator
     */
    protected function makeFaker($locale = null)
    {
        $locale = $locale ?? config('app.faker_locale', Factory::DEFAULT_LOCALE);

        if (isset($this->app) && $this->app->bound(Generator::class)) {
            return $this->app->make(Generator::class, ['locale' => $locale]);
        }

        return Factory::create($locale);
    }
}
