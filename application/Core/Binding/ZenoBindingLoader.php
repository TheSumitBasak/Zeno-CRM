<?php


namespace Zeno\Core\Binding;

use Zeno\Core\Utils\Module;
use Zeno\Binding;
use Zeno\Core\Binding\BindingProcessor;

class ZenoBindingLoader implements BindingLoader
{
    /** @var string[] */
    private array $moduleNameList;

    private ?BindingProcessor $binding = null;

    public function __construct(
        Module $module,
        ?BindingProcessor $binding = null
    ) {
        $this->moduleNameList = $module->getOrderedList();
        $this->binding = $binding;
    }

    public function load(): BindingData
    {
        $data = new BindingData();
        $binder = new Binder($data);

        (new Binding())->process($binder);

        foreach ($this->moduleNameList as $moduleName) {
            $this->loadModule($binder, $moduleName);
        }

        $this->loadCustom($binder);

        $this->binding?->process($binder);

        return $data;
    }

    private function loadModule(Binder $binder, string $moduleName): void
    {
        $className = 'Zeno\\Modules\\' . $moduleName . '\\Binding';

        if (!class_exists($className)) {
            return;
        }

        /** @var class-string<BindingProcessor> $className */

        (new $className())->process($binder);
    }

    private function loadCustom(Binder $binder): void
    {
        /** @var class-string<BindingProcessor>|string $className */
        $className = 'Zeno\\Custom\\Binding';

        if (!class_exists($className)) {
            return;
        }

        /** @var class-string<BindingProcessor> $className */

        (new $className())->process($binder);
    }
}
