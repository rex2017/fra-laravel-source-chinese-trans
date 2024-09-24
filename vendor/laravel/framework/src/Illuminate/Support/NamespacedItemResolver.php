<?php
/**
 * 支持，命名空间项解析
 */

namespace Illuminate\Support;

class NamespacedItemResolver
{
    /**
     * A cache of the parsed items.
	 * 已解析项的缓存
     *
     * @var array
     */
    protected $parsed = [];

    /**
     * Parse a key into namespace, group, and item.
	 * 将键解析为名称空间、组和项
     *
     * @param  string  $key
     * @return array
     */
    public function parseKey($key)
    {
        // If we've already parsed the given key, we'll return the cached version we
        // already have, as this will save us some processing. We cache off every
        // key we parse so we can quickly return it on all subsequent requests.
		// 如果我们已经解析了给定的密钥，我们将返回已经缓存的版本，因为这将为我们节省一些处理时间。
		// 我们缓存解析的每个键，以便在所有后续请求中快速返回。
        if (isset($this->parsed[$key])) {
            return $this->parsed[$key];
        }

        // If the key does not contain a double colon, it means the key is not in a
        // namespace, and is just a regular configuration item. Namespaces are a
        // tool for organizing configuration items for things such as modules.
		// 如果键不包含双冒号，则表示键不在命名空间中，而只是一个常规配置项。
		// 命名空间是一种用于组织模块等配置项的工具。
        if (strpos($key, '::') === false) {
            $segments = explode('.', $key);

            $parsed = $this->parseBasicSegments($segments);
        } else {
            $parsed = $this->parseNamespacedSegments($key);
        }

        // Once we have the parsed array of this key's elements, such as its groups
        // and namespace, we will cache each array inside a simple list that has
        // the key and the parsed array for quick look-ups for later requests.
		// 一旦我们有了这个键的元素的解析数组，比如它的组和命名空间，
		// 我们就会将每个数组缓存在一个简单的列表中，该列表包含键和解析后的数组，以便快速查找以后的请求。
        return $this->parsed[$key] = $parsed;
    }

    /**
     * Parse an array of basic segments.
	 * 解析基本段数组
     *
     * @param  array  $segments
     * @return array
     */
    protected function parseBasicSegments(array $segments)
    {
        // The first segment in a basic array will always be the group, so we can go
        // ahead and grab that segment. If there is only one total segment we are
        // just pulling an entire group out of the array and not a single item.
		// 基本数组中的第一个段将始终是组，因此我们可以继续抓取该段。
		// 如果只有一个总段，我们只是从数组中提取了一个完整的组，而不是一个项目。
        $group = $segments[0];

        // If there is more than one segment in this group, it means we are pulling
        // a specific item out of a group and will need to return this item name
        // as well as the group so we know which item to pull from the arrays.
		// 如果此组中有多个段，则意味着我们正在从组中提取特定项，
		// 并且需要返回此项名称以及组，以便我们知道从数组中提取哪个项。
        $item = count($segments) === 1
                    ? null
                    : implode('.', array_slice($segments, 1));

        return [null, $group, $item];
    }

    /**
     * Parse an array of namespaced segments.
	 * 解析一个命名空间段数组
     *
     * @param  string  $key
     * @return array
     */
    protected function parseNamespacedSegments($key)
    {
        [$namespace, $item] = explode('::', $key);

        // First we'll just explode the first segment to get the namespace and group
        // since the item should be in the remaining segments. Once we have these
        // two pieces of data we can proceed with parsing out the item's value.
		// 首先，我们将分解第一个段以获取名称空间和组，因为该项应位于其余段中。
		// 一旦我们有了这两条数据，我们就可以继续解析出项目的值。
        $itemSegments = explode('.', $item);

        $groupAndItem = array_slice(
            $this->parseBasicSegments($itemSegments), 1
        );

        return array_merge([$namespace], $groupAndItem);
    }

    /**
     * Set the parsed value of a key.
	 * 设置键的解析值
     *
     * @param  string  $key
     * @param  array  $parsed
     * @return void
     */
    public function setParsedKey($key, $parsed)
    {
        $this->parsed[$key] = $parsed;
    }
}
