<?php
/**
 * 基础，发现事件
 */

namespace Illuminate\Foundation\Events;

use Illuminate\Support\Reflector;
use Illuminate\Support\Str;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;
use SplFileInfo;
use Symfony\Component\Finder\Finder;

class DiscoverEvents
{
    /**
     * Get all of the events and listeners by searching the given listener directory.
	 * 得到所有事件和侦听器
     *
     * @param  string  $listenerPath
     * @param  string  $basePath
     * @return array
     */
    public static function within($listenerPath, $basePath)
    {
        return collect(static::getListenerEvents(
            (new Finder)->files()->in($listenerPath), $basePath
        ))->mapToDictionary(function ($event, $listener) {
            return [$event => $listener];
        })->all();
    }

    /**
     * Get all of the listeners and their corresponding events.
	 * 得到所有的侦听器及其相应的事件
     *
     * @param  iterable  $listeners
     * @param  string  $basePath
     * @return array
     */
    protected static function getListenerEvents($listeners, $basePath)
    {
        $listenerEvents = [];

        foreach ($listeners as $listener) {
            try {
                $listener = new ReflectionClass(
                    static::classFromFile($listener, $basePath)
                );
            } catch (ReflectionException $e) {
                continue;
            }

            if (! $listener->isInstantiable()) {
                continue;
            }

            foreach ($listener->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
                if (! Str::is('handle*', $method->name) ||
                    ! isset($method->getParameters()[0])) {
                    continue;
                }

                $listenerEvents[$listener->name.'@'.$method->name] =
                                Reflector::getParameterClassName($method->getParameters()[0]);
            }
        }

        return array_filter($listenerEvents);
    }

    /**
     * Extract the class name from the given file path.
	 * 提取类名从给定的文件路径中
     *
     * @param  \SplFileInfo  $file
     * @param  string  $basePath
     * @return string
     */
    protected static function classFromFile(SplFileInfo $file, $basePath)
    {
        $class = trim(Str::replaceFirst($basePath, '', $file->getRealPath()), DIRECTORY_SEPARATOR);

        return str_replace(
            [DIRECTORY_SEPARATOR, ucfirst(basename(app()->path())).'\\'],
            ['\\', app()->getNamespace()],
            ucfirst(Str::replaceLast('.php', '', $class))
        );
    }
}
