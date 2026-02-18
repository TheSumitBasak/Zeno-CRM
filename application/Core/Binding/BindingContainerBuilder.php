<?php


namespace Zeno\Core\Binding;

use Closure;
use Zeno\Core\Binding\Key\NamedClassKey;

class BindingContainerBuilder
{
    private BindingData $data;
    private Binder $binder;

    public function __construct()
    {
        $this->data = new BindingData();
        $this->binder = new Binder($this->data);
    }

    /**
     * Bind an interface to an implementation.
     *
     * @template T of object
     * @param class-string<T>|NamedClassKey<T> $key An interface or interface with a parameter name.
     * @param class-string<T> $implementationClassName An implementation class name.
     */
    public function bindImplementation($key, string $implementationClassName): self
    {
        $this->binder->bindImplementation($key, $implementationClassName);

        return $this;
    }

    /**
     * Bind an interface to a specific service.
     *
     * @param class-string<object>|NamedClassKey<object> $key An interface or interface with a parameter name.
     * @param string $serviceName A service name.
     * @noinspection PhpUnused
     */
    public function bindService($key, string $serviceName): self
    {
        $this->binder->bindService($key, $serviceName);

        return $this;
    }

    /**
     * Bind an interface to a callback.
     *
     * @template T of object
     * @param class-string<T>|NamedClassKey<T> $key An interface or interface with a parameter name.
     * @param Closure $callback A callback that will resolve a dependency.
     * @todo Change to Closure(...): T Once https://github.com/phpstan/phpstan/issues/8214 is implemented.
     * @noinspection PhpUnused
     */
    public function bindCallback($key, Closure $callback): self
    {
        $this->binder->bindCallback($key, $callback);

        return $this;
    }

    /**
     * Bind an interface to a specific instance.
     *
     * @template T of object
     * @param class-string<T>|NamedClassKey<T> $key An interface or interface with a parameter name.
     * @param T $instance An instance.
     * @noinspection PhpDocSignatureInspection
     */
    public function bindInstance($key, object $instance): self
    {
        $this->binder->bindInstance($key, $instance);

        return $this;
    }

    /**
     * Bind an interface to a factory.
     *
     * @template T of object
     * @param class-string<T>|NamedClassKey<T> $key An interface or interface with a parameter name.
     * @param class-string<Factory<T>> $factoryClassName A factory class name.
     * @noinspection PhpUnused
     */
    public function bindFactory($key, string $factoryClassName): self
    {
        $this->binder->bindFactory($key, $factoryClassName);

        return $this;
    }

    /**
     * Creates a contextual binder and pass it as an argument of a callback.
     *
     * @param class-string<object> $className A context.
     * @param Closure(ContextualBinder): void $callback A callback with a `ContextualBinder` argument.
     */
    public function inContext(string $className, Closure $callback): self
    {
        $contextualBinder = new ContextualBinder($this->data, $className);

        $callback($contextualBinder);

        return $this;
    }

    /**
     * Build.
     */
    public function build(): BindingContainer
    {
        return new BindingContainer($this->data);
    }

    /**
     * Create an instance.
     */
    public static function create(): self
    {
        return new self();
    }
}
