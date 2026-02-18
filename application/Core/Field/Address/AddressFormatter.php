<?php


namespace Zeno\Core\Field\Address;

use Zeno\Core\Field\Address;

/**
 * An address formatter.
 */
interface AddressFormatter
{
    public function format(Address $address): string;
}
