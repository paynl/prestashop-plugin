<?php

declare(strict_types=1);

namespace PayNL\Sdk\Packages\Laminas\Hydrator\Strategy;

interface StrategyInterface
{
    /**
     * Converts the given value so that it can be extracted by the hydrator.
     *
     * @param  mixed       $value The original value.
     * @param  null|object $object (optional) The original object for context.
     * @return mixed       Returns the value that should be extracted.
     */
    public function extract($value, ?object $object = null);

    /**
     * Converts the given value so that it can be hydrated by the hydrator.
     *
     * @param  mixed      $value The original value.
     * @param  null|array $data The original data for context.
     * @return mixed      Returns the value that should be hydrated.
     */
    public function hydrate($value, ?array $data);
}
