<?php


namespace Zeno\Core\Field\Address;

use RuntimeException;

use Zeno\Core\InjectableFactory;
use Zeno\Core\Utils\Config;

class AddressFormatterFactory
{
    private AddressFormatterMetadataProvider $metadataProvider;
    private InjectableFactory $injectableFactory;
    private Config $config;

    public function __construct(
        AddressFormatterMetadataProvider $metadataProvider,
        InjectableFactory $injectableFactory,
        Config $config
    ) {
        $this->metadataProvider = $metadataProvider;
        $this->injectableFactory = $injectableFactory;
        $this->config = $config;
    }

    public function create(int $format): AddressFormatter
    {
        /** @var ?class-string<AddressFormatter> $className */
        $className = $this->metadataProvider->getFormatterClassName($format);

        if (!$className) {
            throw new RuntimeException("Unknown address format '{$format}'.");
        }

        return $this->injectableFactory->create($className);
    }

    public function createDefault(): AddressFormatter
    {
        $format = $this->config->get('addressFormat') ?? 1;

        return $this->create($format);
    }
}
