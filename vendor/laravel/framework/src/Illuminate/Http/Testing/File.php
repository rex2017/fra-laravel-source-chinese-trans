<?php
/**
 * Http，文件
 */

namespace Illuminate\Http\Testing;

use Illuminate\Http\UploadedFile;

class File extends UploadedFile
{
    /**
     * The name of the file.
	 * 文件名
     *
     * @var string
     */
    public $name;

    /**
     * The temporary file resource.
	 * 临时文件资源
     *
     * @var resource
     */
    public $tempFile;

    /**
     * The "size" to report.
	 * 要报告的大小 
     *
     * @var int
     */
    public $sizeToReport;

    /**
     * The MIME type to report.
	 * 要报告的MIME类型
     *
     * @var string|null
     */
    public $mimeTypeToReport;

    /**
     * Create a new file instance.
	 * 创建新的文件实例
     *
     * @param  string  $name
     * @param  resource  $tempFile
     * @return void
     */
    public function __construct($name, $tempFile)
    {
        $this->name = $name;
        $this->tempFile = $tempFile;

        parent::__construct(
            $this->tempFilePath(), $name, $this->getMimeType(),
            null, true
        );
    }

    /**
     * Create a new fake file.
	 * 创建新的伪装文件
     *
     * @param  string  $name
     * @param  string|int  $kilobytes
     * @return \Illuminate\Http\Testing\File
     */
    public static function create($name, $kilobytes = 0)
    {
        return (new FileFactory)->create($name, $kilobytes);
    }

    /**
     * Create a new fake file with content.
	 * 创建新的伪装文件使用内容
     *
     * @param  string  $name
     * @param  string  $content
     * @return \Illuminate\Http\Testing\File
     */
    public static function createWithContent($name, $content)
    {
        return (new FileFactory)->createWithContent($name, $content);
    }

    /**
     * Create a new fake image.
	 * 创建新的伪装图片
     *
     * @param  string  $name
     * @param  int  $width
     * @param  int  $height
     * @return \Illuminate\Http\Testing\File
     */
    public static function image($name, $width = 10, $height = 10)
    {
        return (new FileFactory)->image($name, $width, $height);
    }

    /**
     * Set the "size" of the file in kilobytes.
	 * 设置文件的"大小"，单位为千字节。
     *
     * @param  int  $kilobytes
     * @return $this
     */
    public function size($kilobytes)
    {
        $this->sizeToReport = $kilobytes * 1024;

        return $this;
    }

    /**
     * Get the size of the file.
	 * 得到文件大小 
     *
     * @return int
     */
    public function getSize()
    {
        return $this->sizeToReport ?: parent::getSize();
    }

    /**
     * Set the "MIME type" for the file.
	 * 设置文件的"MIME类型"
     *
     * @param  string  $mimeType
     * @return $this
     */
    public function mimeType($mimeType)
    {
        $this->mimeTypeToReport = $mimeType;

        return $this;
    }

    /**
     * Get the MIME type of the file.
	 * 得到文件的MIME类型
     *
     * @return string
     */
    public function getMimeType()
    {
        return $this->mimeTypeToReport ?: MimeType::from($this->name);
    }

    /**
     * Get the path to the temporary file.
	 * 得到临时文件的路径
     *
     * @return string
     */
    protected function tempFilePath()
    {
        return stream_get_meta_data($this->tempFile)['uri'];
    }
}
