<?php

declare(strict_types=1);

namespace PayNL\Sdk\Packages\Laminas\Hydrator\Filter;

use PayNL\Sdk\Packages\Laminas\Hydrator\Exception\InvalidArgumentException;
use ReflectionException;
use ReflectionMethod;
use ReflectionParameter;

use function array_filter;
use function array_key_exists;
use function sprintf;

/**
 * Filter that includes methods which have no parameters or only optional parameters
 */
final class OptionalParametersFilter implements FilterInterface
{
    /**
     * Map of methods already analyzed
     * by {@see OptionalParametersFilter::filter()},
     * cached for performance reasons
     *
     * @var bool[]
     */
    protected static $propertiesCache = [];

    /**
     * {@inheritDoc}
     *
     * @throws InvalidArgumentException If reflection fails due to the method
     *     not existing.
     */
    public function filter(string $property, ?object $instance = null): bool
    {
        $cacheName = $instance !== null
            ? (new ReflectionMethod($instance, $property))->getName()
            : $property;

        if (array_key_exists($cacheName, static::$propertiesCache)) {
            return static::$propertiesCache[$cacheName];
        }

        try {
            $reflectionMethod = $instance !== null
                ? new ReflectionMethod($instance, $property)
                : new ReflectionMethod($property);
        } catch (ReflectionException $exception) {
            throw new InvalidArgumentException(sprintf('Method %s does not exist', $property));
        }

        $mandatoryParameters = array_filter(
            $reflectionMethod->getParameters(),
            function (ReflectionParameter $parameter) {
                return ! $parameter->isOptional();
            }
        );

        return static::$propertiesCache[$cacheName] = empty($mandatoryParameters);
    }
}
