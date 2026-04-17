<?php
/************************************************************************
 * This file is part of ZenoCRM.
 *
 * ZenoCRM – Open Source CRM application.
 * Copyright (C) 2014-2026 ZenoCRM, Inc.
 * Website: https://www.zenocrm.com
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 *
 * The interactive user interfaces in modified source and object code versions
 * of this program must display Appropriate Legal Notices, as required under
 * Section 5 of the GNU Affero General Public License version 3.
 *
 * In accordance with Section 7(b) of the GNU Affero General Public License version 3,
 * these Appropriate Legal Notices must retain the display of the "ZenoCRM" word.
 ************************************************************************/

namespace Zeno\Core\Entities;

use Zeno\Core\Field\Address;
use Zeno\Core\Field\EmailAddressGroup;
use Zeno\Core\Field\PhoneNumberGroup;
use Zeno\Core\Name\Field;


class Person extends Entity
{
    private Helper $helper;

    public function __construct(
        string $entityType,
        array $defs,
        EntityManager $entityManager,
        Helper $helper,
        ?ValueAccessorFactory $valueAccessorFactory = null,
        ?Relations $relations = null
    ) {
        parent::__construct(
            $entityType,
            $defs,
            $entityManager,
            $valueAccessorFactory,
            $relations
        );

        $this->helper = $helper;
    }

    /**
     * @param string $value
     * @return void
     */
    protected function _setLastName($value)
    {
        $this->setInContainer('lastName', $value);

        if (!$this->helper->hasAllPersonNameAttributes($this, 'name')) {
            return;
        }

        $name = $this->helper->formatPersonName($this, 'name');

        $this->setInContainer(Field::NAME, $name);
    }

    /**
     * @param string $value
     * @return void
     */
    protected function _setFirstName($value)
    {
        $this->setInContainer('firstName', $value);

        if (!$this->helper->hasAllPersonNameAttributes($this, 'name')) {
            return;
        }

        $name = $this->helper->formatPersonName($this, 'name');

        $this->setInContainer(Field::NAME, $name);
    }

    /**
     * @param string $value
     * @return void
     */
    protected function _setMiddleName($value)
    {
        $this->setInContainer('middleName', $value);

        if (!$this->helper->hasAllPersonNameAttributes($this, 'name')) {
            return;
        }

        $name = $this->helper->formatPersonName($this, 'name');

        $this->setInContainer(Field::NAME, $name);
    }

    /**
     * Get a primary email address.
     */
    public function getEmailAddress(): ?string
    {
        return $this->get('emailAddress');
    }

    /**
     * Get a primary phone number.
     */
    public function getPhoneNumber(): ?string
    {
        return $this->get('phoneNumber');
    }

    public function getEmailAddressGroup(): EmailAddressGroup
    {
        /** @var EmailAddressGroup */
        return $this->getValueObject('emailAddress');
    }

    public function getPhoneNumberGroup(): PhoneNumberGroup
    {
        /** @var PhoneNumberGroup */
        return $this->getValueObject('phoneNumber');
    }

    public function getName(): ?string
    {
        return $this->get(Field::NAME);
    }

    public function getFirstName(): ?string
    {
        return $this->get('firstName');
    }

    public function getLastName(): ?string
    {
        return $this->get('lastName');
    }

    public function getMiddleName(): ?string
    {
        return $this->get('middleName');
    }

    public function setFirstName(?string $firstName): self
    {
        return $this->set('firstName', $firstName);
    }

    public function setLastName(?string $lastName): self
    {
        return $this->set('lastName', $lastName);
    }

    public function setMiddleName(?string $middleName): self
    {
        return $this->set('middleName', $middleName);
    }

    public function setEmailAddressGroup(EmailAddressGroup $group): self
    {
        return $this->setValueObject('emailAddress', $group);
    }

    public function setPhoneNumberGroup(PhoneNumberGroup $group): self
    {
        return $this->setValueObject('phoneNumber', $group);
    }

    public function getAddress(): Address
    {
        /** @var Address */
        return $this->getValueObject('address');
    }

    public function setAddress(Address $address): self
    {
        return $this->setValueObject('address', $address);
    }
}
