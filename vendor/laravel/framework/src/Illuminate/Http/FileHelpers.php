<?php
/**
 * Http，文件帮助
 */

namespace Illuminate\Http;

use Illuminate\Support\Str;

trait FileHelpers
{
    /**
     * The cache copy of the file's hash name.
	 * 缓存文件哈希名
     *
     * @var string
     */
    protected $hashName = null;

    /**
     * Get the fully qualified path to the file.
	 * 得到文件完全路径
     *
     * @return string
     */
    public function path()
    {
        return $this->getRealPath();
    }

    /**
     * Get the file's extension.
	 * 得到文件后缀
     *
     * @return string
     */
    public function extension()
    {
        return $this->guessExtension();
    }

    /**
     * Get a filename for the file.
	 * 得到文件名
     *
     * @param  string|null  $path
     * @return string
     */
    public function hashName($path = null)
    {
        if ($path) {
            $path = rtrim($path, '/').'/';
        }

        $hash = $this->hashName ?: $this->hashName = Str::random(40);

        if ($extension = $this->guessExtension()) {
            $extension = '.'.$extension;
        }

        return $path.$hash.$extension;
    }
}
