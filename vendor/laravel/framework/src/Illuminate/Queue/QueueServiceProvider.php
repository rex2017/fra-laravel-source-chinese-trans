<?php
/**
 * 队列服务提供者
 */

namespace Illuminate\Queue;

use Aws\DynamoDb\DynamoDbClient;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Queue\Connectors\BeanstalkdConnector;
use Illuminate\Queue\Connectors\DatabaseConnector;
use Illuminate\Queue\Connectors\NullConnector;
use Illuminate\Queue\Connectors\RedisConnector;
use Illuminate\Queue\Connectors\SqsConnector;
use Illuminate\Queue\Connectors\SyncConnector;
use Illuminate\Queue\Failed\DatabaseFailedJobProvider;
use Illuminate\Queue\Failed\DynamoDbFailedJobProvider;
use Illuminate\Queue\Failed\NullFailedJobProvider;
use Illuminate\Support\Arr;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Opis\Closure\SerializableClosure;

class QueueServiceProvider extends ServiceProvider implements DeferrableProvider
{
    /**
     * Register the service provider.
	 * 注册服务提供者
     *
     * @return void
     */
    public function register()
    {
        $this->registerManager();
        $this->registerConnection();
        $this->registerWorker();
        $this->registerListener();
        $this->registerFailedJobServices();
        $this->registerOpisSecurityKey();
    }

    /**
     * Register the queue manager.
	 * 注册队列管理
     *
     * @return void
     */
    protected function registerManager()
    {
        $this->app->singleton('queue', function ($app) {
            // Once we have an instance of the queue manager, we will register the various
            // resolvers for the queue connectors. These connectors are responsible for
            // creating the classes that accept queue configs and instantiate queues.
			// 一旦我们有了队列管理器的实例，我们将为队列连接器注册各种解析器。
			// 这些连接器负责创建接受队列配置和实例化队列的类。
            return tap(new QueueManager($app), function ($manager) {
                $this->registerConnectors($manager);
            });
        });
    }

    /**
     * Register the default queue connection binding.
	 * 注册默认队列连接绑定
     *
     * @return void
     */
    protected function registerConnection()
    {
        $this->app->singleton('queue.connection', function ($app) {
            return $app['queue']->connection();
        });
    }

    /**
     * Register the connectors on the queue manager.
	 * 注册连接器在队列管理器上
     *
     * @param  \Illuminate\Queue\QueueManager  $manager
     * @return void
     */
    public function registerConnectors($manager)
    {
        foreach (['Null', 'Sync', 'Database', 'Redis', 'Beanstalkd', 'Sqs'] as $connector) {
            $this->{"register{$connector}Connector"}($manager);
        }
    }

    /**
     * Register the Null queue connector.
	 * 注册空队列连接器
     *
     * @param  \Illuminate\Queue\QueueManager  $manager
     * @return void
     */
    protected function registerNullConnector($manager)
    {
        $manager->addConnector('null', function () {
            return new NullConnector;
        });
    }

    /**
     * Register the Sync queue connector.
	 * 注册同步队列连接器
     *
     * @param  \Illuminate\Queue\QueueManager  $manager
     * @return void
     */
    protected function registerSyncConnector($manager)
    {
        $manager->addConnector('sync', function () {
            return new SyncConnector;
        });
    }

    /**
     * Register the database queue connector.
	 * 注册数据库队列连接器
     *
     * @param  \Illuminate\Queue\QueueManager  $manager
     * @return void
     */
    protected function registerDatabaseConnector($manager)
    {
        $manager->addConnector('database', function () {
            return new DatabaseConnector($this->app['db']);
        });
    }

    /**
     * Register the Redis queue connector.
	 * 注册Redis队列连接器
     *
     * @param  \Illuminate\Queue\QueueManager  $manager
     * @return void
     */
    protected function registerRedisConnector($manager)
    {
        $manager->addConnector('redis', function () {
            return new RedisConnector($this->app['redis']);
        });
    }

    /**
     * Register the Beanstalkd queue connector.
	 * 注册beanstald队列连接器
     *
     * @param  \Illuminate\Queue\QueueManager  $manager
     * @return void
     */
    protected function registerBeanstalkdConnector($manager)
    {
        $manager->addConnector('beanstalkd', function () {
            return new BeanstalkdConnector;
        });
    }

    /**
     * Register the Amazon SQS queue connector.
	 * 注册Amazon SQS队列连接器
     *
     * @param  \Illuminate\Queue\QueueManager  $manager
     * @return void
     */
    protected function registerSqsConnector($manager)
    {
        $manager->addConnector('sqs', function () {
            return new SqsConnector;
        });
    }

    /**
     * Register the queue worker.
	 * 注册队列执行者
     *
     * @return void
     */
    protected function registerWorker()
    {
        $this->app->singleton('queue.worker', function () {
            $isDownForMaintenance = function () {
                return $this->app->isDownForMaintenance();
            };

            return new Worker(
                $this->app['queue'],
                $this->app['events'],
                $this->app[ExceptionHandler::class],
                $isDownForMaintenance
            );
        });
    }

    /**
     * Register the queue listener.
	 * 注册队列监听者
     *
     * @return void
     */
    protected function registerListener()
    {
        $this->app->singleton('queue.listener', function () {
            return new Listener($this->app->basePath());
        });
    }

    /**
     * Register the failed job services.
	 * 注册失败作业服务
     *
     * @return void
     */
    protected function registerFailedJobServices()
    {
        $this->app->singleton('queue.failer', function () {
            $config = $this->app['config']['queue.failed'];

            if (isset($config['driver']) && $config['driver'] === 'dynamodb') {
                return $this->dynamoFailedJobProvider($config);
            } elseif (isset($config['table'])) {
                return $this->databaseFailedJobProvider($config);
            } else {
                return new NullFailedJobProvider;
            }
        });
    }

    /**
     * Create a new database failed job provider.
	 * 创建新的数据库失败作业提供程序
     *
     * @param  array  $config
     * @return \Illuminate\Queue\Failed\DatabaseFailedJobProvider
     */
    protected function databaseFailedJobProvider($config)
    {
        return new DatabaseFailedJobProvider(
            $this->app['db'], $config['database'], $config['table']
        );
    }

    /**
     * Create a new DynamoDb failed job provider.
	 * 创建新的DynamoDb失败作业提供程序
     *
     * @param  array  $config
     * @return \Illuminate\Queue\Failed\DynamoDbFailedJobProvider
     */
    protected function dynamoFailedJobProvider($config)
    {
        $dynamoConfig = [
            'region' => $config['region'],
            'version' => 'latest',
            'endpoint' => $config['endpoint'] ?? null,
        ];

        if (! empty($config['key']) && ! empty($config['secret'])) {
            $dynamoConfig['credentials'] = Arr::only(
                $config, ['key', 'secret', 'token']
            );
        }

        return new DynamoDbFailedJobProvider(
            new DynamoDbClient($dynamoConfig),
            $this->app['config']['app.name'],
            $config['table']
        );
    }

    /**
     * Configure Opis Closure signing for security.
	 * 配置Opis闭包签名为安全性
     *
     * @return void
     */
    protected function registerOpisSecurityKey()
    {
        if (Str::startsWith($key = $this->app['config']->get('app.key'), 'base64:')) {
            $key = base64_decode(substr($key, 7));
        }

        SerializableClosure::setSecretKey($key);
    }

    /**
     * Get the services provided by the provider.
	 * 得到提供者提供的服务
     *
     * @return array
     */
    public function provides()
    {
        return [
            'queue', 'queue.worker', 'queue.listener',
            'queue.failer', 'queue.connection',
        ];
    }
}
