<?php


namespace Zeno\Core\Field\Address;

use Zeno\ORM\Entity;
use Zeno\ORM\Value\ValueFactory;

use Zeno\Core\Field\Address;

class AddressFactory implements ValueFactory
{
    public function isCreatableFromEntity(Entity $entity, string $field): bool
    {
        return true;
    }

    public function createFromEntity(Entity $entity, string $field): Address
    {
        return (new AddressBuilder())
            ->setStreet($entity->get($field . 'Street'))
            ->setCity($entity->get($field . 'City'))
            ->setCountry($entity->get($field . 'Country'))
            ->setState($entity->get($field . 'State'))
            ->setPostalCode($entity->get($field . 'PostalCode'))
            ->build();
    }
}
