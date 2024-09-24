<?php
/**
 * 基础，供应商发布命令
 */

namespace Illuminate\Foundation\Console;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Arr;
use Illuminate\Support\ServiceProvider;
use League\Flysystem\Adapter\Local as LocalAdapter;
use League\Flysystem\Filesystem as Flysystem;
use League\Flysystem\MountManager;

class VendorPublishCommand extends Command
{
    /**
     * The filesystem instance.
	 * 文件系统实例
     *
     * @var \Illuminate\Filesystem\Filesystem
     */
    protected $files;

    /**
     * The provider to publish.
	 * 要发布提供者
     *
     * @var string
     */
    protected $provider = null;

    /**
     * The tags to publish.
	 * 要发布的标签
     *
     * @var array
     */
    protected $tags = [];

    /**
     * The console command signature.
	 * 控制台命令签名
     *
     * @var string
     */
    protected $signature = 'vendor:publish {--force : Overwrite any existing files}
                    {--all : Publish assets for all service providers without prompt}
                    {--provider= : The service provider that has assets you want to publish}
                    {--tag=* : One or many tags that have assets you want to publish}';

    /**
     * The console command description.
	 * 控制台命令描述
     *
     * @var string
     */
    protected $description = 'Publish any publishable assets from vendor packages';

    /**
     * Create a new command instance.
	 * 创建新的命令实例
     *
     * @param  \Illuminate\Filesystem\Filesystem  $files
     * @return void
     */
    public function __construct(Filesystem $files)
    {
        parent::__construct();

        $this->files = $files;
    }

    /**
     * Execute the console command.
	 * 执行控制台命令
     *
     * @return void
     */
    public function handle()
    {
        $this->determineWhatShouldBePublished();

        foreach ($this->tags ?: [null] as $tag) {
            $this->publishTag($tag);
        }

        $this->info('Publishing complete.');
    }

    /**
     * Determine the provider or tag(s) to publish.
	 * 确定要发布的提供者或标记
     *
     * @return void
     */
    protected function determineWhatShouldBePublished()
    {
        if ($this->option('all')) {
            return;
        }

        [$this->provider, $this->tags] = [
            $this->option('provider'), (array) $this->option('tag'),
        ];

        if (! $this->provider && ! $this->tags) {
            $this->promptForProviderOrTag();
        }
    }

    /**
     * Prompt for which provider or tag to publish.
	 * 提示要发布哪个提供程序或标记
     *
     * @return void
     */
    protected function promptForProviderOrTag()
    {
        $choice = $this->choice(
            "Which provider or tag's files would you like to publish?",
            $choices = $this->publishableChoices()
        );

        if ($choice == $choices[0] || is_null($choice)) {
            return;
        }

        $this->parseChoice($choice);
    }

    /**
     * The choices available via the prompt.
	 * 通过提示符提供的选项
     *
     * @return array
     */
    protected function publishableChoices()
    {
        return array_merge(
            ['<comment>Publish files from all providers and tags listed below</comment>'],
            preg_filter('/^/', '<comment>Provider: </comment>', Arr::sort(ServiceProvider::publishableProviders())),
            preg_filter('/^/', '<comment>Tag: </comment>', Arr::sort(ServiceProvider::publishableGroups()))
        );
    }

    /**
     * Parse the answer that was given via the prompt.
	 * 解析通过提示给出的答案
     *
     * @param  string  $choice
     * @return void
     */
    protected function parseChoice($choice)
    {
        [$type, $value] = explode(': ', strip_tags($choice));

        if ($type === 'Provider') {
            $this->provider = $value;
        } elseif ($type === 'Tag') {
            $this->tags = [$value];
        }
    }

    /**
     * Publishes the assets for a tag.
	 * 发布标记的资产
     *
     * @param  string  $tag
     * @return mixed
     */
    protected function publishTag($tag)
    {
        $published = false;

        foreach ($this->pathsToPublish($tag) as $from => $to) {
            $this->publishItem($from, $to);

            $published = true;
        }

        if ($published === false) {
            $this->error('Unable to locate publishable resources.');
        }
    }

    /**
     * Get all of the paths to publish.
	 * 得到所有要发布的路径
     *
     * @param  string  $tag
     * @return array
     */
    protected function pathsToPublish($tag)
    {
        return ServiceProvider::pathsToPublish(
            $this->provider, $tag
        );
    }

    /**
     * Publish the given item from and to the given location.
	 * 发布给定的项从给定位置到给定位置
     *
     * @param  string  $from
     * @param  string  $to
     * @return void
     */
    protected function publishItem($from, $to)
    {
        if ($this->files->isFile($from)) {
            return $this->publishFile($from, $to);
        } elseif ($this->files->isDirectory($from)) {
            return $this->publishDirectory($from, $to);
        }

        $this->error("Can't locate path: <{$from}>");
    }

    /**
     * Publish the file to the given path.
	 * 发布文件到给定的路径
     *
     * @param  string  $from
     * @param  string  $to
     * @return void
     */
    protected function publishFile($from, $to)
    {
        if (! $this->files->exists($to) || $this->option('force')) {
            $this->createParentDirectory(dirname($to));

            $this->files->copy($from, $to);

            $this->status($from, $to, 'File');
        }
    }

    /**
     * Publish the directory to the given directory.
	 * 将目录发布到给定目录
     *
     * @param  string  $from
     * @param  string  $to
     * @return void
     */
    protected function publishDirectory($from, $to)
    {
        $this->moveManagedFiles(new MountManager([
            'from' => new Flysystem(new LocalAdapter($from)),
            'to' => new Flysystem(new LocalAdapter($to)),
        ]));

        $this->status($from, $to, 'Directory');
    }

    /**
     * Move all the files in the given MountManager.
	 * 移动指定MountManager中的所有文件
     *
     * @param  \League\Flysystem\MountManager  $manager
     * @return void
     */
    protected function moveManagedFiles($manager)
    {
        foreach ($manager->listContents('from://', true) as $file) {
            if ($file['type'] === 'file' && (! $manager->has('to://'.$file['path']) || $this->option('force'))) {
                $manager->put('to://'.$file['path'], $manager->read('from://'.$file['path']));
            }
        }
    }

    /**
     * Create the directory to house the published files if needed.
	 * 如果需要，创建目录来存放发布的文件
     *
     * @param  string  $directory
     * @return void
     */
    protected function createParentDirectory($directory)
    {
        if (! $this->files->isDirectory($directory)) {
            $this->files->makeDirectory($directory, 0755, true);
        }
    }

    /**
     * Write a status message to the console.
	 * 写入状态消息向控制台
     *
     * @param  string  $from
     * @param  string  $to
     * @param  string  $type
     * @return void
     */
    protected function status($from, $to, $type)
    {
        $from = str_replace(base_path(), '', realpath($from));

        $to = str_replace(base_path(), '', realpath($to));

        $this->line('<info>Copied '.$type.'</info> <comment>['.$from.']</comment> <info>To</info> <comment>['.$to.']</comment>');
    }
}
