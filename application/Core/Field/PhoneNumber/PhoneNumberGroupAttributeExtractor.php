<?php


namespace Zeno\Core\Field\PhoneNumber;

use Zeno\ORM\Value\AttributeExtractor;

use Zeno\Core\Field\PhoneNumberGroup;

use stdClass;
use InvalidArgumentException;

/**
 * @implements AttributeExtractor<PhoneNumberGroup>
 */
class PhoneNumberGroupAttributeExtractor implements AttributeExtractor
{
    /**
     * @param PhoneNumberGroup $group
     */
    public function extract(object $group, string $field): stdClass
    {
        if (!$group instanceof PhoneNumberGroup) {
            throw new InvalidArgumentException();
        }

        $primaryNumber = $group->getPrimary() ? $group->getPrimary()->getNumber() : null;

        $dataList = [];

        foreach ($group->getList() as $phoneNumber) {
            $dataList[] = (object) [
                'phoneNumber' => $phoneNumber->getNumber(),
                'type' => $phoneNumber->getType(),
                'primary' => $primaryNumber && $phoneNumber->getNumber() === $primaryNumber,
                'optOut' => $phoneNumber->isOptedOut(),
                'invalid' => $phoneNumber->isInvalid(),
            ];
        }

        return (object) [
            $field => $primaryNumber,
            $field . 'Data' => $dataList,
        ];
    }

    public function extractFromNull(string $field): stdClass
    {
        return (object) [
            $field => null,
            $field . 'Data' => [],
        ];
    }
}
