<?php

declare(strict_types=1);

namespace PayNL\Sdk\Packages\Laminas\Hydrator\Aggregate;

use PayNL\Sdk\Packages\Laminas\EventManager\EventManager;
use PayNL\Sdk\Packages\Laminas\EventManager\EventManagerAwareInterface;
use PayNL\Sdk\Packages\Laminas\EventManager\EventManagerInterface;
use PayNL\Sdk\Packages\Laminas\Hydrator\HydratorInterface;

/**
 * Aggregate hydrator that composes multiple hydrators via events
 */
class AggregateHydrator implements HydratorInterface, EventManagerAwareInterface
{
    public const DEFAULT_PRIORITY = 1;

    /** @var EventManagerInterface */
    protected $eventManager;

    /**
     * Attaches the provided hydrator to the list of hydrators to be used while hydrating/extracting data
     */
    public function add(HydratorInterface $hydrator, int $priority = self::DEFAULT_PRIORITY): void
    {
        $listener = new HydratorListener($hydrator);
        $listener->attach($this->getEventManager(), $priority);
    }

    /**
     * {@inheritDoc}
     */
    public function extract(object $object): array
    {
        $event = new ExtractEvent($this, $object);
        $this->getEventManager()->triggerEvent($event);
        return $event->getExtractedData();
    }

    /**
     * {@inheritDoc}
     */
    public function hydrate(array $data, object $object)
    {
        $event = new HydrateEvent($this, $object, $data);
        $this->getEventManager()->triggerEvent($event);
        return $event->getHydratedObject();
    }

    /**
     * {@inheritDoc}
     */
    public function setEventManager(EventManagerInterface $eventManager): void
    {
        $eventManager->setIdentifiers([self::class, static::class]);
        $this->eventManager = $eventManager;
    }

    /**
     * {@inheritDoc}
     */
    public function getEventManager(): EventManagerInterface
    {
        if (null === $this->eventManager) {
            $this->setEventManager(new EventManager());
        }

        return $this->eventManager;
    }
}
