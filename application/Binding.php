<?php
/**
 * Binding class for ZenoCRM
 * Handles dependency injection and service binding
 */

namespace ZenoCRM;

class Binding
{
    /**
     * @var array Service container
     */
    protected $container = [];

    /**
     * Bind a service to the container
     *
     * @param string $name Service name
     * @param mixed $value Service instance or callable
     */
    public function bind($name, $value)
    {
        $this->container[$name] = $value;
    }

    /**
     * Resolve a service from the container
     *
     * @param string $name Service name
     * @return mixed
     */
    public function get($name)
    {
        if (!isset($this->container[$name])) {
            throw new \Exception("Service '{$name}' not found in container.");
        }

        $value = $this->container[$name];

        if (is_callable($value)) {
            return $value($this);
        }

        return $value;
    }

    /**
     * Check if a service exists
     *
     * @param string $name Service name
     * @return bool
     */
    public function has($name)
    {
        return isset($this->container[$name]);
    }
}
