<?php
/**
 * 邮件传输管理
 */

namespace Illuminate\Mail;

use Aws\Ses\SesClient;
use GuzzleHttp\Client as HttpClient;
use Illuminate\Log\LogManager;
use Illuminate\Mail\Transport\ArrayTransport;
use Illuminate\Mail\Transport\LogTransport;
use Illuminate\Mail\Transport\MailgunTransport;
use Illuminate\Mail\Transport\SesTransport;
use Illuminate\Support\Arr;
use Illuminate\Support\Manager;
use Postmark\ThrowExceptionOnFailurePlugin;
use Postmark\Transport as PostmarkTransport;
use Psr\Log\LoggerInterface;
use Swift_SendmailTransport as SendmailTransport;
use Swift_SmtpTransport as SmtpTransport;

class TransportManager extends Manager
{
    /**
     * Create an instance of the SMTP Swift Transport driver.
	 * 创建SMTP Swift传输驱动程序的实例
     *
     * @return \Swift_SmtpTransport
     */
    protected function createSmtpDriver()
    {
        $config = $this->config->get('mail');

        // The Swift SMTP transport instance will allow us to use any SMTP backend
        // for delivering mail such as Sendgrid, Amazon SES, or a custom server
        // a developer has available. We will just pass this configured host.
		// Swift SMTP传输实例将允许我们使用任何SMTP后端来传递邮件，如Sendgrid、AmazonSES或开发人员可用的自定义服务器。
		// 我们将只传递此配置的主机。
        $transport = new SmtpTransport($config['host'], $config['port']);

        if (! empty($config['encryption'])) {
            $transport->setEncryption($config['encryption']);
        }

        // Once we have the transport we will check for the presence of a username
        // and password. If we have it we will set the credentials on the Swift
        // transporter instance so that we'll properly authenticate delivery.
		// 一旦我们有了传输，我们将检查用户名和密码的存在。如果我们有它，
		// 我们将在Swifttransporter实例上设置凭据，以便正确验证交付。
        if (isset($config['username'])) {
            $transport->setUsername($config['username']);

            $transport->setPassword($config['password']);
        }

        return $this->configureSmtpDriver($transport, $config);
    }

    /**
     * Configure the additional SMTP driver options.
	 * 配置其他SMTP驱动程序选项
     *
     * @param  \Swift_SmtpTransport  $transport
     * @param  array  $config
     * @return \Swift_SmtpTransport
     */
    protected function configureSmtpDriver($transport, $config)
    {
        if (isset($config['stream'])) {
            $transport->setStreamOptions($config['stream']);
        }

        if (isset($config['source_ip'])) {
            $transport->setSourceIp($config['source_ip']);
        }

        if (isset($config['local_domain'])) {
            $transport->setLocalDomain($config['local_domain']);
        }

        return $transport;
    }

    /**
     * Create an instance of the Sendmail Swift Transport driver.
	 * 创建Sendmail Swift Transport驱动程序的实例
     *
     * @return \Swift_SendmailTransport
     */
    protected function createSendmailDriver()
    {
        return new SendmailTransport($this->config->get('mail.sendmail'));
    }

    /**
     * Create an instance of the Amazon SES Swift Transport driver.
	 * 创建一个Amazon SES Swift Transport驱动程序的实例
     *
     * @return \Illuminate\Mail\Transport\SesTransport
     */
    protected function createSesDriver()
    {
        $config = array_merge($this->config->get('services.ses', []), [
            'version' => 'latest', 'service' => 'email',
        ]);

        return new SesTransport(
            new SesClient($this->addSesCredentials($config)),
            $config['options'] ?? []
        );
    }

    /**
     * Add the SES credentials to the configuration array.
	 * 添加SES凭据到配置阵列
     *
     * @param  array  $config
     * @return array
     */
    protected function addSesCredentials(array $config)
    {
        if (! empty($config['key']) && ! empty($config['secret'])) {
            $config['credentials'] = Arr::only($config, ['key', 'secret', 'token']);
        }

        return $config;
    }

    /**
     * Create an instance of the Mail Swift Transport driver.
	 * 创建邮件Swift传输驱动程序的实例
     *
     * @return \Swift_SendmailTransport
     */
    protected function createMailDriver()
    {
        return new SendmailTransport;
    }

    /**
     * Create an instance of the Mailgun Swift Transport driver.
	 * 创建Mailgun Swift Transport驱动程序的实例
     *
     * @return \Illuminate\Mail\Transport\MailgunTransport
     */
    protected function createMailgunDriver()
    {
        $config = $this->config->get('services.mailgun', []);

        return new MailgunTransport(
            $this->guzzle($config),
            $config['secret'],
            $config['domain'],
            $config['endpoint'] ?? null
        );
    }

    /**
     * Create an instance of the Postmark Swift Transport driver.
	 * 创建邮戳Swift传输驱动程序的实例
     *
     * @return \Swift_Transport
     */
    protected function createPostmarkDriver()
    {
        return tap(new PostmarkTransport(
            $this->config->get('services.postmark.token')
        ), function ($transport) {
            $transport->registerPlugin(new ThrowExceptionOnFailurePlugin());
        });
    }

    /**
     * Create an instance of the Log Swift Transport driver.
	 * 创建Log Swift Transport驱动程序的实例
     *
     * @return \Illuminate\Mail\Transport\LogTransport
     */
    protected function createLogDriver()
    {
        $logger = $this->container->make(LoggerInterface::class);

        if ($logger instanceof LogManager) {
            $logger = $logger->channel($this->config->get('mail.log_channel'));
        }

        return new LogTransport($logger);
    }

    /**
     * Create an instance of the Array Swift Transport Driver.
	 * 创建Array Swift Transport Driver的实例
     *
     * @return \Illuminate\Mail\Transport\ArrayTransport
     */
    protected function createArrayDriver()
    {
        return new ArrayTransport;
    }

    /**
     * Get a fresh Guzzle HTTP client instance.
	 * 得到新的Guzzle HTTP客户端实例
     *
     * @param  array  $config
     * @return \GuzzleHttp\Client
     */
    protected function guzzle($config)
    {
        return new HttpClient(Arr::add(
            $config['guzzle'] ?? [], 'connect_timeout', 60
        ));
    }

    /**
     * Get the default mail driver name.
	 * 得到默认的邮件驱动程序名称
     *
     * @return string
     */
    public function getDefaultDriver()
    {
        return $this->config->get('mail.driver');
    }

    /**
     * Set the default mail driver name.
	 * 设置默认的邮件驱动程序名称
     *
     * @param  string  $name
     * @return void
     */
    public function setDefaultDriver($name)
    {
        $this->config->set('mail.driver', $name);
    }
}
