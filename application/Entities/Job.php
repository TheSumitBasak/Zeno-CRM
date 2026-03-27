<?php

namespace Zeno\Entities;

use Zeno\Core\Field\DateTime;
use Zeno\Core\Job\Job as JobJob;
use Zeno\Core\Job\Job\Status;
use Zeno\Core\Job\JobDataLess;
use Zeno\Core\ORM\Entity;
use Zeno\Core\Utils\DateTime as DateTimeUtil;

use stdClass;

class Job extends Entity
{
    public const ENTITY_TYPE = 'Job';

    /**
     * Get a status.
     */
    public function getStatus(): string
    {
        return $this->get('status');
    }

    /**
     * Get a job name.
     */
    public function getJob(): ?string
    {
        return $this->get('job');
    }

    /**
     * Get a scheduled job name.
     */
    public function getScheduledJobJob(): ?string
    {
        return $this->get('scheduledJobJob');
    }

    /**
     * Get a target type.
     */
    public function getTargetType(): ?string
    {
        return $this->get('targetType');
    }

    /**
     * Get a target ID.
     */
    public function getTargetId(): ?string
    {
        return $this->get('targetId');
    }

    /**
     * Get a target group.
     */
    public function getTargetGroup(): ?string
    {
        return $this->get('targetGroup');
    }

    /**
     * Get a group.
     */
    public function getGroup(): ?string
    {
        return $this->get('group');
    }

    /**
     * Get a queue.
     */
    public function getQueue(): ?string
    {
        return $this->get('queue');
    }

    /**
     * Get data.
     */
    public function getData(): stdClass
    {
        return $this->get('data') ?? (object) [];
    }

    /**
     * Get a class name.
     *
     * @return ?class-string<JobJob|JobDataLess>
     */
    public function getClassName(): ?string
    {
        return $this->get('className');
    }

    /**
     * Get a service name.
     */
    public function getServiceName(): ?string
    {
        return $this->get('serviceName');
    }

    /**
     * Get a method name.
     */
    public function getMethodName(): ?string
    {
        return $this->get('methodName');
    }

    /**
     * Get a scheduled job ID.
     */
    public function getScheduledJobId(): ?string
    {
        return $this->get('scheduledJobId');
    }

    /**
     * Get a started date-time.
     */
    public function getStartedAt(): ?string
    {
        return $this->get('startedAt');
    }

    /**
     * Get a PID.
     */
    public function getPid(): ?int
    {
        return $this->get('pid');
    }

    /**
     * Get a number of attempts left.
     */
    public function getAttempts(): int
    {
        return $this->get('attempts') ?? 0;
    }

    /**
     * Get a number of failed attempts.
     */
    public function getFailedAttempts(): int
    {
        return $this->get('failedAttempts') ?? 0;
    }

    /**
     * Set status.
     *
     * @param Status::* $status
     */
    public function setStatus(string $status): self
    {
        return $this->set('status', $status);
    }

    /**
     * Set PID.
     */
    public function setPid(?int $pid): self
    {
        return $this->set('pid', $pid);
    }

    /**
     * Set started-at to now.
     */
    public function setStartedAtNow(): self
    {
        return $this->set('startedAt', DateTimeUtil::getSystemNowString());
    }

    /**
     * Set executed-at to now.
     */
    public function setExecutedAtNow(): self
    {
        return $this->set('executedAt', DateTimeUtil::getSystemNowString());
    }

    public function setName(?string $name): self
    {
        return $this->set('name', $name);
    }

    public function setClassName(?string $className): self
    {
        return $this->set('className', $className);
    }

    public function setQueue(?string $queue): self
    {
        return $this->set('queue', $queue);
    }
 
    public function setGroup(?string $group): self
    {
        return $this->set('group', $group);
    }

    public function setTargetId(?string $targetId): self
    {
        return $this->set('targetId', $targetId);
    }

    public function setTargetType(?string $targetType): self
    {
        return $this->set('targetType', $targetType);
    }

    public function setData(?JobJob\Data $data): self
    {
        if (!$data) {
            return $this->set('data', $data);
        }

        return $this->set('data', $data->getRaw());
    }

    public function setExecuteTime(?DateTime $executeTime): self
    {
        return $this->setValueObject('executeTime', $executeTime);
    }
}
