<?php
/**
 * 序列化模型
 */

namespace Illuminate\Queue;

use ReflectionClass;
use ReflectionProperty;

trait SerializesModels
{
    use SerializesAndRestoresModelIdentifiers;

    /**
     * Prepare the instance for serialization.
	 * 准备实例为序列化
     *
     * @return array
     */
    public function __sleep()
    {
        $properties = (new ReflectionClass($this))->getProperties();

        foreach ($properties as $property) {
            $property->setValue($this, $this->getSerializedPropertyValue(
                $this->getPropertyValue($property)
            ));
        }

        return array_values(array_filter(array_map(function ($p) {
            return $p->isStatic() ? null : $p->getName();
        }, $properties)));
    }

    /**
     * Restore the model after serialization.
	 * 恢复模型序列化后
     *
     * @return void
     */
    public function __wakeup()
    {
        foreach ((new ReflectionClass($this))->getProperties() as $property) {
            if ($property->isStatic()) {
                continue;
            }

            $property->setValue($this, $this->getRestoredPropertyValue(
                $this->getPropertyValue($property)
            ));
        }
    }

    /**
     * Prepare the instance values for serialization.
	 * 准备实例值为序列化
     *
     * @return array
     */
    public function __serialize()
    {
        $values = [];

        $properties = (new ReflectionClass($this))->getProperties();

        $class = get_class($this);

        foreach ($properties as $property) {
            if ($property->isStatic()) {
                continue;
            }

            $property->setAccessible(true);

            if (! $property->isInitialized($this)) {
                continue;
            }

            $name = $property->getName();

            if ($property->isPrivate()) {
                $name = "\0{$class}\0{$name}";
            } elseif ($property->isProtected()) {
                $name = "\0*\0{$name}";
            }

            $values[$name] = $this->getSerializedPropertyValue(
                $this->getPropertyValue($property)
            );
        }

        return $values;
    }

    /**
     * Restore the model after serialization.
	 * 恢复模型在序列化后
     *
     * @param  array  $values
     * @return array
     */
    public function __unserialize(array $values)
    {
        $properties = (new ReflectionClass($this))->getProperties();

        $class = get_class($this);

        foreach ($properties as $property) {
            if ($property->isStatic()) {
                continue;
            }

            $name = $property->getName();

            if ($property->isPrivate()) {
                $name = "\0{$class}\0{$name}";
            } elseif ($property->isProtected()) {
                $name = "\0*\0{$name}";
            }

            if (! array_key_exists($name, $values)) {
                continue;
            }

            $property->setAccessible(true);

            $property->setValue(
                $this, $this->getRestoredPropertyValue($values[$name])
            );
        }

        return $values;
    }

    /**
     * Get the property value for the given property.
	 * 得到给定属性的属性值
     *
     * @param  \ReflectionProperty  $property
     * @return mixed
     */
    protected function getPropertyValue(ReflectionProperty $property)
    {
        $property->setAccessible(true);

        return $property->getValue($this);
    }
}
