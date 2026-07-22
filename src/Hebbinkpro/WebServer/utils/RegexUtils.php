<?php
/*
 * MIT License
 *
 * Copyright (c) 2026 Hebbinkpro
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

declare(strict_types=1);

namespace Hebbinkpro\WebServer\utils;

final class RegexUtils
{
    /**
     * Perform a regular expression match and only return wether a match was found.
     *
     * @param string $pattern
     * @param string $subject
     * @param array<mixed>|null $matches
     * @param int $flags
     * @param int $offset
     * @return bool true if there is a match, false otherwise
     * @param-out array<mixed> $matches
     * @see preg_match for parameter descriptions
     */
    public static function has_preg_match(string $pattern, string $subject, ?array &$matches = null, int $flags = 0, int $offset = 0): bool
    {

        // @phpstan-ignore-next-line
        $match = @preg_match($pattern, $subject, $matches, $flags, $offset);
        return $match !== false && $match > 0;

    }
}