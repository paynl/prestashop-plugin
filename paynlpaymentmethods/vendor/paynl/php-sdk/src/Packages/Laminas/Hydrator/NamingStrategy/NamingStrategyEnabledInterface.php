<?php

declare(strict_types=1);

namespace PayNL\Sdk\Packages\Laminas\Hydrator\NamingStrategy;

interface NamingStrategyEnabledInterface
{
    /**
     * Adds the given naming strategy
     */
    public function setNamingStrategy(NamingStrategyInterface $strategy): void;

    /**
     * Gets the naming strategy.
     */
    public function getNamingStrategy(): NamingStrategyInterface;

    /**
     * Checks if a naming strategy exists.
     */
    public function hasNamingStrategy(): bool;

    /**
     * Removes the naming with the given name.
     */
    public function removeNamingStrategy(): void;
}
