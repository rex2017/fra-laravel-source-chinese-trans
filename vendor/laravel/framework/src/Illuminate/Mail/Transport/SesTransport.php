<?php
/**
 * 邮件，Amazon SES传输
 */

namespace Illuminate\Mail\Transport;

use Aws\Ses\SesClient;
use Swift_Mime_SimpleMessage;

class SesTransport extends Transport
{
    /**
     * The Amazon SES instance.
	 * Amazon SES 实例
     *
     * @var \Aws\Ses\SesClient
     */
    protected $ses;

    /**
     * The Amazon SES transmission options.
	 * SES传输选项
     *
     * @var array
     */
    protected $options = [];

    /**
     * Create a new SES transport instance.
	 * 创建新的SES传输实例
     *
     * @param  \Aws\Ses\SesClient  $ses
     * @param  array  $options
     * @return void
     */
    public function __construct(SesClient $ses, $options = [])
    {
        $this->ses = $ses;
        $this->options = $options;
    }

    /**
     * {@inheritdoc}
     */
    public function send(Swift_Mime_SimpleMessage $message, &$failedRecipients = null)
    {
        $this->beforeSendPerformed($message);

        $result = $this->ses->sendRawEmail(
            array_merge(
                $this->options, [
                    'Source' => key($message->getSender() ?: $message->getFrom()),
                    'RawMessage' => [
                        'Data' => $message->toString(),
                    ],
                ]
            )
        );

        $message->getHeaders()->addTextHeader('X-SES-Message-ID', $result->get('MessageId'));

        $this->sendPerformed($message);

        return $this->numberOfRecipients($message);
    }

    /**
     * Get the Amazon SES client for the SesTransport instance.
	 * 得到SesTransport实例
     *
     * @return \Aws\Ses\SesClient
     */
    public function ses()
    {
        return $this->ses;
    }

    /**
     * Get the transmission options being used by the transport.
	 * 得到传输所使用的传输选项
     *
     * @return array
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * Set the transmission options being used by the transport.
	 * 设置传输所使用的传输选项
     *
     * @param  array  $options
     * @return array
     */
    public function setOptions(array $options)
    {
        return $this->options = $options;
    }
}
