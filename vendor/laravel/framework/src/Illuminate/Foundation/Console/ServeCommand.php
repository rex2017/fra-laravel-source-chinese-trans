<?php
/**
 * 基础，产生命令
 */

namespace Illuminate\Foundation\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Env;
use Illuminate\Support\ProcessUtils;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Process\PhpExecutableFinder;

class ServeCommand extends Command
{
    /**
     * The console command name.
	 * 控制台命令名
     *
     * @var string
     */
    protected $name = 'serve';

    /**
     * The console command description.
	 * 控制台命令描述
     *
     * @var string
     */
    protected $description = 'Serve the application on the PHP development server';

    /**
     * The current port offset.
	 * 当前端口偏移
     *
     * @var int
     */
    protected $portOffset = 0;

    /**
     * Execute the console command.
	 * 执行控制台命令
     *
     * @return int
     *
     * @throws \Exception
     */
    public function handle()
    {
        chdir(public_path());

        $this->line("<info>Laravel development server started:</info> http://{$this->host()}:{$this->port()}");

        passthru($this->serverCommand(), $status);

        if ($status && $this->canTryAnotherPort()) {
            $this->portOffset += 1;

            return $this->handle();
        }

        return $status;
    }

    /**
     * Get the full server command.
	 * 得到完整服务端命令
     *
     * @return string
     */
    protected function serverCommand()
    {
        return sprintf('%s -S %s:%s %s',
            ProcessUtils::escapeArgument((new PhpExecutableFinder)->find(false)),
            $this->host(),
            $this->port(),
            ProcessUtils::escapeArgument(base_path('server.php'))
        );
    }

    /**
     * Get the host for the command.
	 * 得到主机命令
     *
     * @return string
     */
    protected function host()
    {
        return $this->input->getOption('host');
    }

    /**
     * Get the port for the command.
	 * 得到命令端口
     *
     * @return string
     */
    protected function port()
    {
        $port = $this->input->getOption('port') ?: 8000;

        return $port + $this->portOffset;
    }

    /**
     * Check if command has reached its max amount of port tries.
	 * 检查命令是否已达到端口尝试的最大数量
     *
     * @return bool
     */
    protected function canTryAnotherPort()
    {
        return is_null($this->input->getOption('port')) &&
               ($this->input->getOption('tries') > $this->portOffset);
    }

    /**
     * Get the console command options.
	 * 得到控制台命令选项
     *
     * @return array
     */
    protected function getOptions()
    {
        return [
            ['host', null, InputOption::VALUE_OPTIONAL, 'The host address to serve the application on', '127.0.0.1'],

            ['port', null, InputOption::VALUE_OPTIONAL, 'The port to serve the application on', Env::get('SERVER_PORT')],

            ['tries', null, InputOption::VALUE_OPTIONAL, 'The max number of ports to attempt to serve from', 10],
        ];
    }
}
