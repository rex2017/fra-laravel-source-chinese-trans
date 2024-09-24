<?php
/**
 * 邮件，日志传输
 */

namespace Illuminate\Mail\Transport;

use Psr\Log\LoggerInterface;
use Swift_Mime_SimpleMessage;
use Swift_Mime_SimpleMimeEntity;

class LogTransport extends Transport
{
    /**
     * The Logger instance.
	 * Logger实例
     *
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * Create a new log transport instance.
	 * 创建新的日志传输实例
     *
     * @param  \Psr\Log\LoggerInterface  $logger
     * @return void
     */
    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    public function send(Swift_Mime_SimpleMessage $message, &$failedRecipients = null)
    {
        $this->beforeSendPerformed($message);

        $this->logger->debug($this->getMimeEntityString($message));

        $this->sendPerformed($message);

        return $this->numberOfRecipients($message);
    }

    /**
     * Get a loggable string out of a Swiftmailer entity.
	 * 得到可记录的字符串从Swiftmailer实体中
     *
     * @param  \Swift_Mime_SimpleMimeEntity  $entity
     * @return string
     */
    protected function getMimeEntityString(Swift_Mime_SimpleMimeEntity $entity)
    {
        $string = (string) $entity->getHeaders().PHP_EOL.$entity->getBody();

        foreach ($entity->getChildren() as $children) {
            $string .= PHP_EOL.PHP_EOL.$this->getMimeEntityString($children);
        }

        return $string;
    }

    /**
     * Get the logger for the LogTransport instance.
	 * 得到LogTransport实例的记录器
     *
     * @return \Psr\Log\LoggerInterface
     */
    public function logger()
    {
        return $this->logger;
    }
}
