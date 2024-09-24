<?php
/**
 * 邮件，Mailgun传输
 */

namespace Illuminate\Mail\Transport;

use GuzzleHttp\ClientInterface;
use Swift_Mime_SimpleMessage;

class MailgunTransport extends Transport
{
    /**
     * Guzzle client instance.
	 * 客户端实例
     *
     * @var \GuzzleHttp\ClientInterface
     */
    protected $client;

    /**
     * The Mailgun API key.
	 * key
     *
     * @var string
     */
    protected $key;

    /**
     * The Mailgun email domain.
	 * 域名
     *
     * @var string
     */
    protected $domain;

    /**
     * The Mailgun API endpoint.
	 * 端口
     *
     * @var string
     */
    protected $endpoint;

    /**
     * Create a new Mailgun transport instance.
	 * 创建新的Mailgun传输实例
     *
     * @param  \GuzzleHttp\ClientInterface  $client
     * @param  string  $key
     * @param  string  $domain
     * @param  string|null  $endpoint
     * @return void
     */
    public function __construct(ClientInterface $client, $key, $domain, $endpoint = null)
    {
        $this->key = $key;
        $this->client = $client;
        $this->endpoint = $endpoint ?? 'api.mailgun.net';

        $this->setDomain($domain);
    }

    /**
     * {@inheritdoc}
     */
    public function send(Swift_Mime_SimpleMessage $message, &$failedRecipients = null)
    {
        $this->beforeSendPerformed($message);

        $to = $this->getTo($message);

        $bcc = $message->getBcc();

        $message->setBcc([]);

        $response = $this->client->request(
            'POST',
            "https://{$this->endpoint}/v3/{$this->domain}/messages.mime",
            $this->payload($message, $to)
        );

        $message->getHeaders()->addTextHeader(
            'X-Mailgun-Message-ID', $this->getMessageId($response)
        );

        $message->setBcc($bcc);

        $this->sendPerformed($message);

        return $this->numberOfRecipients($message);
    }

    /**
     * Get the HTTP payload for sending the Mailgun message.
	 * 得到用于发送Mailgun消息的HTTP有效负载
     *
     * @param  \Swift_Mime_SimpleMessage  $message
     * @param  string  $to
     * @return array
     */
    protected function payload(Swift_Mime_SimpleMessage $message, $to)
    {
        return [
            'auth' => [
                'api',
                $this->key,
            ],
            'multipart' => [
                [
                    'name' => 'to',
                    'contents' => $to,
                ],
                [
                    'name' => 'message',
                    'contents' => $message->toString(),
                    'filename' => 'message.mime',
                ],
            ],
        ];
    }

    /**
     * Get the "to" payload field for the API request.
	 * 得到API请求的"to"有效负载字段
     *
     * @param  \Swift_Mime_SimpleMessage  $message
     * @return string
     */
    protected function getTo(Swift_Mime_SimpleMessage $message)
    {
        return collect($this->allContacts($message))->map(function ($display, $address) {
            return $display ? $display." <{$address}>" : $address;
        })->values()->implode(',');
    }

    /**
     * Get all of the contacts for the message.
	 * 得到该消息的所有联系人
     *
     * @param  \Swift_Mime_SimpleMessage  $message
     * @return array
     */
    protected function allContacts(Swift_Mime_SimpleMessage $message)
    {
        return array_merge(
            (array) $message->getTo(), (array) $message->getCc(), (array) $message->getBcc()
        );
    }

    /**
     * Get the message ID from the response.
	 * 得到响应的消息ID
     *
     * @param  \Psr\Http\Message\ResponseInterface  $response
     * @return string
     */
    protected function getMessageId($response)
    {
        return object_get(
            json_decode($response->getBody()->getContents()), 'id'
        );
    }

    /**
     * Get the API key being used by the transport.
	 * 得到传输所使用的API密钥
     *
     * @return string
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * Set the API key being used by the transport.
	 * 设置传输所使用的API密钥
     *
     * @param  string  $key
     * @return string
     */
    public function setKey($key)
    {
        return $this->key = $key;
    }

    /**
     * Get the domain being used by the transport.
	 * 得到传输所使用的域
     *
     * @return string
     */
    public function getDomain()
    {
        return $this->domain;
    }

    /**
     * Set the domain being used by the transport.
	 * 设置传输所使用的域
     *
     * @param  string  $domain
     * @return string
     */
    public function setDomain($domain)
    {
        return $this->domain = $domain;
    }

    /**
     * Get the API endpoint being used by the transport.
	 * 得到传输所使用的API端点
     *
     * @return string
     */
    public function getEndpoint()
    {
        return $this->endpoint;
    }

    /**
     * Set the API endpoint being used by the transport.
	 * 设置传输所使用的API端点
     *
     * @param  string  $endpoint
     * @return string
     */
    public function setEndpoint($endpoint)
    {
        return $this->endpoint = $endpoint;
    }
}
