<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2026 EspoCRM, Inc.
 * Website: https://www.espocrm.com
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
 * these Appropriate Legal Notices must retain the display of the "EspoCRM" word.
 ************************************************************************/

namespace Zeno\Entities;

use Zeno\Core\Entities\Person;
use Zeno\Core\Field\DateTime;
use Zeno\Core\Field\Link;
use Zeno\Core\Field\LinkMultiple;
use Zeno\Core\Name\Field;

class Lead extends Person
{
    public const ENTITY_TYPE = 'Lead';

    public const STATUS_NEW = 'New';
    public const STATUS_ASSIGNED = 'Assigned';
    public const STATUS_IN_PROCESS = 'In Process';
    public const STATUS_CONVERTED = 'Converted';
    public const STATUS_RECYCLED = 'Recycled';
    public const STATUS_DEAD = 'Dead';

    public function get(string $attribute): mixed
    {
        if ($attribute === Field::NAME) {
            return $this->getNameInternal();
        }

        return parent::get($attribute);
    }

    public function has(string $attribute): bool
    {
        if ($attribute === Field::NAME) {
            return $this->hasNameInternal();
        }

        return parent::has($attribute);
    }

    public function setDescription(?string $description): self
    {
        return $this->set('description', $description);
    }

    public function getDescription(): ?string
    {
        return is_string($this->get('description')) ? $this->get('description') : null;
    }

    public function getStatus(): ?string
    {
        return is_string($this->get('status')) ? $this->get('status') : null;
    }

    private function getNameInternal(): ?string
    {
        if (!$this->hasInContainer(Field::NAME) || !$this->getFromContainer(Field::NAME)) {
            if ($this->get('accountName')) {
                return is_string($this->get('accountName')) ? $this->get('accountName') : null;
            }

            if ($this->get('emailAddress')) {
                return is_string($this->get('emailAddress')) ? $this->get('emailAddress') : null;
            }

            if ($this->get('phoneNumber')) {
                return is_string($this->get('phoneNumber')) ? $this->get('phoneNumber') : null;
            }
        }

        $name = $this->getFromContainer(Field::NAME);

        return is_string($name) ? $name : null;
    }

    private function hasNameInternal(): bool
    {
        if ($this->hasInContainer(Field::NAME)) {
            return true;
        }

        if ($this->has('accountName')) {
            return true;
        }

        if ($this->has('emailAddress')) {
            return true;
        }

        if ($this->has('phoneNumber')) {
            return true;
        }

        return false;
    }

    public function getCampaign()
    {
        return $this->getRelation('campaign');
    }

    public function getAssignedUser(): ?Link
    {
        /** @var ?Link */
        return $this->getValueObject(Field::ASSIGNED_USER);
    }

    public function getTeams(): LinkMultiple
    {
        /** @var LinkMultiple */
        return $this->getValueObject(Field::TEAMS);
    }

    public function getCreatedAccount()
    {
        return $this->getRelation('createdAccount');
    }

    public function getCreatedContact()
    {
        return $this->getRelation('createdContact');
    }

    public function getCreatedOpportunity()
    {
        return $this->getRelation('createdOpportunity');
    }

    public function getConvertedAt(): ?DateTime
    {
        /** @var ?DateTime */
        return $this->getValueObject('convertedAt');
    }

    public function setStatus(string $status): self
    {
        $this->set('status', $status);

        return $this;
    }

    public function setCreatedAccount($createdAccount): self
    {
        $this->relations->set('createdAccount', $createdAccount);

        return $this;
    }

    public function setAssignedUser($assignedUser): self
    {
        return $this->setRelatedLinkOrEntity(Field::ASSIGNED_USER, $assignedUser);
    }

    public function setTeams(LinkMultiple $teams): self
    {
        $this->setValueObject(Field::TEAMS, $teams);

        return $this;
    }

    public function setSource(?string $source): self
    {
        return $this->set('source', $source);
    }

    public function setCampaign($campaign): self
    {
        return $this->setRelatedLinkOrEntity('campaign', $campaign);
    }

    /**
     * @since 9.3.0
     */
    public function getTitle(): ?string
    {
        return is_string($this->get('title')) ? $this->get('title') : null;
    }
}
