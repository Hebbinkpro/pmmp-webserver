<?php
/*
 * MIT License
 *
 * Copyright (c) 2025 Hebbinkpro
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 */

namespace Hebbinkpro\WebServer\utils;

use Exception;
use pmmp\thread\NonThreadSafeValueError;
use pmmp\thread\ThreadSafe;
use pmmp\thread\ThreadSafeArray;
use pocketmine\thread\NonThreadSafeValue;

/**
 * Utility functions for working with thread-safe values and arrays.
 *
 * @since 0.4.3
 */
final class ThreadSafeUtils
{
    /**
     * Convert the given value into a thread-safe representation.
     *
     * Scalars and ThreadSafe objects are returned as-is.
     * Arrays are converted into ThreadSafeArray objects.
     * All other values are wrapped in NonThreadSafeValue.
     *
     * @param mixed $value The value to convert.
     * @return int|float|string|bool|ThreadSafe The thread-safe representation of the value.
     */
    public static function makeThreadSafe(mixed $value): int|float|string|bool|ThreadSafe
    {
        if (self::isThreadSafe($value)) return $value;

        if (is_array($value)) return self::makeThreadSafeArray($value);

        return new NonThreadSafeValue($value);
    }

    /**
     * Check whether the given value is thread-safe.
     *
     * A value is considered thread-safe if it is a scalar type
     * (int, float, string, bool) or an instance of ThreadSafe.
     *
     * @param mixed $value The value to check.
     * @return bool True if the value is thread-safe, false otherwise.
     * @phpstan-assert-if-true int|float|string|bool|ThreadSafe $value
     */
    public static function isThreadSafe(mixed $value): bool
    {
        return is_scalar($value) || $value instanceof ThreadSafe;
    }

    /**
     * Convert a regular array into a ThreadSafeArray.
     *
     * This method attempts to convert the array using ThreadSafeArray::fromArray().
     * If any non-thread-safe values are detected, it recursively converts them
     * into thread-safe equivalents.
     *
     * @param array<mixed, mixed> $array The array to convert.
     * @return ThreadSafeArray The converted ThreadSafeArray.
     */
    public static function makeThreadSafeArray(array $array): ThreadSafeArray
    {
        try {
            // first attempt using the build-in fromArray method
            return ThreadSafeArray::fromArray($array);
        } catch (NonThreadSafeValueError) {
            // if it contains a non-ThreadSafe value, make all values inside the array thread safe
            $tsa = new ThreadSafeArray();
            foreach ($array as $k => $v) {
                $tsa->offsetSet($k, self::makeThreadSafe($v));
            }
            return $tsa;
        }
    }

    /**
     * Unwrap a ThreadSafe object or scalar to a native PHP value.
     *
     * @param int|float|string|bool|ThreadSafe $value A scalar, or ThreadSafe value to unwrap.
     * @return mixed Native PHP value unwrapped from ThreadSafe objects.
     * @throws Exception
     */
    public static function unwrapThreadSafe(int|float|string|bool|ThreadSafe $value): mixed
    {
        if (is_scalar($value)) return $value;
        if ($value instanceof ThreadSafeArray) return self::unwrapThreadSafeArray($value);
        if ($value instanceof NonThreadSafeValue) return $value->deserialize();
        return $value;
    }

    /**
     * Recursively unwrap a ThreadSafeArray to a native PHP array.
     *
     * @param ThreadSafeArray $tsa
     * @return array<mixed,mixed>
     * @throws Exception
     */
    public static function unwrapThreadSafeArray(ThreadSafeArray $tsa): array
    {
        $array = [];

        foreach ($tsa->getIterator() as $k => $v) {
            /** @var bool|float|int|ThreadSafe|string $v */
            $array[$k] = self::unwrapThreadSafe($v);
        }

        return $array;
    }
}