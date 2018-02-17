<?php

declare(strict_types=1);

namespace Rinvex\University;

use Closure;

class UniversityLoader
{
    /**
     * The universities array.
     *
     * @var array
     */
    protected static $universities;

    /**
     * Get the university by slug.
     *
     * @param string $slug
     * @param bool   $hydrate
     *
     * @throws \Rinvex\University\UniversityLoaderException
     *
     * @return \Rinvex\University\University|array
     */
    public static function university($slug, $hydrate = true)
    {
        if (! isset(static::$universities[$slug])) {
            $university = current(glob(__DIR__."/../resources/*/{$slug}.json"));
            static::$universities[$slug] = $university ? json_decode(static::getFile($university), true) : null;
        }

        if (! isset(static::$universities[$slug])) {
            throw UniversityLoaderException::invalidUniversity();
        }

        return $hydrate ? new University(static::$universities[$slug]) : static::$universities[$slug];
    }

    /**
     * Get universities for the given country.
     *
     * @param string|null $countryCode
     *
     * @return array
     */
    public static function universities($countryCode = null)
    {
        if (! isset(static::$universities['names'])) {
            static::$universities['names'] = json_decode(static::getFile(__DIR__.'/../resources/names.json'), true);
        }

        return is_null($countryCode) ? static::$universities['names'] : static::$universities['names'][$countryCode] ?? null;
    }

    /**
     * Get an item from an array or object using "dot" notation.
     *
     * @param mixed        $target
     * @param string|array $key
     * @param mixed        $default
     *
     * @return mixed
     */
    protected static function get($target, $key, $default = null)
    {
        if (is_null($key)) {
            return $target;
        }

        $key = is_array($key) ? $key : explode('.', $key);

        while (($segment = array_shift($key)) !== null) {
            if ($segment === '*') {
                if (! is_array($target)) {
                    return $default instanceof Closure ? $default() : $default;
                }

                $result = static::pluck($target, $key);

                return in_array('*', $key) ? static::collapse($result) : $result;
            }

            if (is_array($target) && array_key_exists($segment, $target)) {
                $target = $target[$segment];
            } elseif (is_object($target) && isset($target->{$segment})) {
                $target = $target->{$segment};
            } else {
                return $default instanceof Closure ? $default() : $default;
            }
        }

        return $target;
    }

    /**
     * Pluck an array of values from an array.
     *
     * @param array             $array
     * @param string|array      $value
     * @param string|array|null $key
     *
     * @return array
     */
    protected static function pluck($array, $value, $key = null)
    {
        $results = [];

        $value = is_string($value) ? explode('.', $value) : $value;

        $key = is_null($key) || is_array($key) ? $key : explode('.', $key);

        foreach ($array as $item) {
            $itemValue = static::get($item, $value);

            // If the key is "null", we will just append the value to the array and keep
            // looping. Otherwise we will key the array using the value of the key we
            // received from the developer. Then we'll return the final array form.
            if (is_null($key)) {
                $results[] = $itemValue;
            } else {
                $itemKey = static::get($item, $key);

                $results[$itemKey] = $itemValue;
            }
        }

        return $results;
    }

    /**
     * Collapse an array of arrays into a single array.
     *
     * @param array $array
     *
     * @return array
     */
    protected static function collapse($array)
    {
        $results = [];

        foreach ($array as $values) {
            if (! is_array($values)) {
                continue;
            }

            $results = array_merge($results, $values);
        }

        return $results;
    }

    /**
     * Get contents of the given file path.
     *
     * @param string $filePath
     *
     * @throws \Rinvex\University\UniversityLoaderException
     *
     * @return string
     */
    public static function getFile($filePath)
    {
        if (! file_exists($filePath)) {
            throw UniversityLoaderException::invalidUniversity();
        }

        return file_get_contents($filePath);
    }
}
