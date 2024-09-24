<?php
/**
 * 邮件消息
 */

namespace Illuminate\Mail;

use Illuminate\Support\Traits\ForwardsCalls;
use Swift_Attachment;
use Swift_Image;

/**
 * @mixin \Swift_Message
 */
class Message
{
    use ForwardsCalls;

    /**
     * The Swift Message instance.
	 * Swift Message实例
     *
     * @var \Swift_Message
     */
    protected $swift;

    /**
     * CIDs of files embedded in the message.
	 * 消息中嵌入文件的CID
     *
     * @var array
     */
    protected $embeddedFiles = [];

    /**
     * Create a new message instance.
	 * 创建新的消息实例
     *
     * @param  \Swift_Message  $swift
     * @return void
     */
    public function __construct($swift)
    {
        $this->swift = $swift;
    }

    /**
     * Add a "from" address to the message.
	 * 添加"发件人"地址至消息中
     *
     * @param  string|array  $address
     * @param  string|null  $name
     * @return $this
     */
    public function from($address, $name = null)
    {
        $this->swift->setFrom($address, $name);

        return $this;
    }

    /**
     * Set the "sender" of the message.
	 * 设置消息的"发送者"
     *
     * @param  string|array  $address
     * @param  string|null  $name
     * @return $this
     */
    public function sender($address, $name = null)
    {
        $this->swift->setSender($address, $name);

        return $this;
    }

    /**
     * Set the "return path" of the message.
	 * 设置消息的"返回路径"
     *
     * @param  string  $address
     * @return $this
     */
    public function returnPath($address)
    {
        $this->swift->setReturnPath($address);

        return $this;
    }

    /**
     * Add a recipient to the message.
	 * 向邮件添加收件人
     *
     * @param  string|array  $address
     * @param  string|null  $name
     * @param  bool  $override
     * @return $this
     */
    public function to($address, $name = null, $override = false)
    {
        if ($override) {
            $this->swift->setTo($address, $name);

            return $this;
        }

        return $this->addAddresses($address, $name, 'To');
    }

    /**
     * Add a carbon copy to the message.
	 * 添加一份复写件在邮件中
     *
     * @param  string|array  $address
     * @param  string|null  $name
     * @param  bool  $override
     * @return $this
     */
    public function cc($address, $name = null, $override = false)
    {
        if ($override) {
            $this->swift->setCc($address, $name);

            return $this;
        }

        return $this->addAddresses($address, $name, 'Cc');
    }

    /**
     * Add a blind carbon copy to the message.
	 * 在邮件中添加一份副本
     *
     * @param  string|array  $address
     * @param  string|null  $name
     * @param  bool  $override
     * @return $this
     */
    public function bcc($address, $name = null, $override = false)
    {
        if ($override) {
            $this->swift->setBcc($address, $name);

            return $this;
        }

        return $this->addAddresses($address, $name, 'Bcc');
    }

    /**
     * Add a reply to address to the message.
	 * 在邮件中添加回复地址
     *
     * @param  string|array  $address
     * @param  string|null  $name
     * @return $this
     */
    public function replyTo($address, $name = null)
    {
        return $this->addAddresses($address, $name, 'ReplyTo');
    }

    /**
     * Add a recipient to the message.
	 * 向邮件添加收件人
     *
     * @param  string|array  $address
     * @param  string  $name
     * @param  string  $type
     * @return $this
     */
    protected function addAddresses($address, $name, $type)
    {
        if (is_array($address)) {
            $this->swift->{"set{$type}"}($address, $name);
        } else {
            $this->swift->{"add{$type}"}($address, $name);
        }

        return $this;
    }

    /**
     * Set the subject of the message.
	 * 设置邮件的主题
     *
     * @param  string  $subject
     * @return $this
     */
    public function subject($subject)
    {
        $this->swift->setSubject($subject);

        return $this;
    }

    /**
     * Set the message priority level.
	 * 设置消息优先级
     *
     * @param  int  $level
     * @return $this
     */
    public function priority($level)
    {
        $this->swift->setPriority($level);

        return $this;
    }

    /**
     * Attach a file to the message.
	 * 附加文件到消息中
     *
     * @param  string  $file
     * @param  array  $options
     * @return $this
     */
    public function attach($file, array $options = [])
    {
        $attachment = $this->createAttachmentFromPath($file);

        return $this->prepAttachment($attachment, $options);
    }

    /**
     * Create a Swift Attachment instance.
	 * 创建一个Swift Attachment实例
     *
     * @param  string  $file
     * @return \Swift_Mime_Attachment
     */
    protected function createAttachmentFromPath($file)
    {
        return Swift_Attachment::fromPath($file);
    }

    /**
     * Attach in-memory data as an attachment.
	 * 附加内存中的数据作为附件
     *
     * @param  string  $data
     * @param  string  $name
     * @param  array  $options
     * @return $this
     */
    public function attachData($data, $name, array $options = [])
    {
        $attachment = $this->createAttachmentFromData($data, $name);

        return $this->prepAttachment($attachment, $options);
    }

    /**
     * Create a Swift Attachment instance from data.
	 * 创建一个Swift Attachment实例从data
     *
     * @param  string  $data
     * @param  string  $name
     * @return \Swift_Attachment
     */
    protected function createAttachmentFromData($data, $name)
    {
        return new Swift_Attachment($data, $name);
    }

    /**
     * Embed a file in the message and get the CID.
	 * 在消息中嵌入一个文件并获取CID
     *
     * @param  string  $file
     * @return string
     */
    public function embed($file)
    {
        if (isset($this->embeddedFiles[$file])) {
            return $this->embeddedFiles[$file];
        }

        return $this->embeddedFiles[$file] = $this->swift->embed(
            Swift_Image::fromPath($file)
        );
    }

    /**
     * Embed in-memory data in the message and get the CID.
	 * 在消息中嵌入内存数据并获得CID
     *
     * @param  string  $data
     * @param  string  $name
     * @param  string|null  $contentType
     * @return string
     */
    public function embedData($data, $name, $contentType = null)
    {
        $image = new Swift_Image($data, $name, $contentType);

        return $this->swift->embed($image);
    }

    /**
     * Prepare and attach the given attachment.
	 * 准备并附上给定的附件
     *
     * @param  \Swift_Attachment  $attachment
     * @param  array  $options
     * @return $this
     */
    protected function prepAttachment($attachment, $options = [])
    {
        // First we will check for a MIME type on the message, which instructs the
        // mail client on what type of attachment the file is so that it may be
        // downloaded correctly by the user. The MIME option is not required.
		// 首先，我们将检查消息上的MIME类型，它指示邮件客户端文件是什么类型的附件，
		// 以便用户可以正确下载。MIME选项不是必需的。
        if (isset($options['mime'])) {
            $attachment->setContentType($options['mime']);
        }

        // If an alternative name was given as an option, we will set that on this
        // attachment so that it will be downloaded with the desired names from
        // the developer, otherwise the default file names will get assigned.
		// 如果提供了一个替代名称作为选项，我们将在此附件上设置该名称，
		// 以便从开发人员那里以所需的名称下载，否则将分配默认文件名。
        if (isset($options['as'])) {
            $attachment->setFilename($options['as']);
        }

        $this->swift->attach($attachment);

        return $this;
    }

    /**
     * Get the underlying Swift Message instance.
	 * 得到底层Swift Message实例
     *
     * @return \Swift_Message
     */
    public function getSwiftMessage()
    {
        return $this->swift;
    }

    /**
     * Dynamically pass missing methods to the Swift instance.
	 * 动态地将缺少的方法传递给Swift实例
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        return $this->forwardCallTo($this->swift, $method, $parameters);
    }
}
