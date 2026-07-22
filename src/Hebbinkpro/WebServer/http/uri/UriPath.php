<?php
/*
 * MIT License
 *
 * Copyright (c) 2025-2026 Hebbinkpro
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

namespace Hebbinkpro\WebServer\http\uri;

use Hebbinkpro\WebServer\utils\ThreadSafeUtils;
use pmmp\thread\ThreadSafe;
use pmmp\thread\ThreadSafeArray;

/**
 * A route path that stores all path parts as string in a ThreadSafe array
 */
class UriPath extends ThreadSafe implements UriElement
{
    /** @var ThreadSafeArray<string[]> */
    private ThreadSafeArray $path;

    /**
     * @param string[] $path
     */
    public function __construct(array $path = [])
    {
        $this->path = ThreadSafeArray::fromArray($path);
    }

    /**
     * Parse a route path from the given string
     * @param string $path The route path
     * @return UriPath
     * @deprecated since v1.0.0 - Use UriPath::parse() instead
     */
    public static function fromString(string $path): UriPath
    {
        return self::parse($path);
    }

    public static function parse(string $value): self
    {
        $value = trim($value, "/");
        if (strlen($value) == 0) return new self([]);

        // split at / and decode each value
        $path = array_map("rawurldecode", explode("/", $value));
        return new self($path);
    }

    /**
     * Append a new path to the existing path
     * @param UriPath $path the path to add
     * @return void
     */
    public function appendPath(UriPath $path): void
    {
        $this->path->merge($path->asArray());
    }

    /**
     * @return string[]
     */
    public function asArray(): array
    {
        return ThreadSafeUtils::unwrapThreadSafeArray($this->path);
    }

    /**
     * Append a new value to the path
     * @param string $value
     * @return void
     */
    public function append(string $value): void
    {
        $this->path[] = $value;
    }

    public function toString(): string
    {
        // root path
        if ($this->path->count() == 0) return "/";

        // encode each value and create a path
        $path = "";
        foreach ($this->path as $part) {
            $path .= "/" . rawurlencode($part);
        }

        return $path;
    }

    /**
     * Get the length of the path
     * @return int
     */
    public function getLength(): int
    {
        return $this->path->count();
    }

    /**
     * Get if this path starts with the given path
     *
     * If the given path contains wildcards, they will be taken into account
     * @param UriPath $path the path to check
     * @param bool $strict if the paths should match exactly, if true wildcards will be ignored.
     * @return bool
     */
    public function startsWith(UriPath $path, bool $strict = false): bool
    {
        $pathArray = $path->asArray();
        $pathSize = $path->getLength();

        if ($pathSize == 0) return true;
        else if ($this->getLength() < $pathSize) return false;

        $pathIdx = 0;
        foreach ($this->path as $value) {
            if ($pathIdx >= $pathSize) return true;

            $toMatch = $pathArray[$pathIdx++];

            if (!$strict && str_starts_with($toMatch, "**")) {
                // final match (since pathIdx is already incremented at $toMatch, we dont need a +1)
                if ($pathIdx >= count($pathArray)) return true;

                // check if current value matches the next one
                // also take care of any other ** parts in the path, since that would be possible
                $nextMatchIdx = $pathIdx;
                do {
                    $nextMatch = $pathArray[$nextMatchIdx++];
                } while (str_starts_with($nextMatch, "**") && $nextMatchIdx < count($pathArray));

                // we got multiple ** parts until the end of the path
                if (str_starts_with($nextMatch, "**")) return true;

                $nextIdx = $pathIdx;
                do {
                    $nextValue = $this->path[$nextIdx++];
                } while ($nextIdx < $this->getLength() && !$this->pathPartMatches($nextMatch, $nextValue, false));

                if ($nextIdx >= $this->getLength()) {
                    return $this->pathPartMatches($nextMatch, $nextValue, false);
                }

                $subPath = new UriPath(array_slice($this->asArray(), $nextIdx));
                $matchPath = new UriPath(array_slice($pathArray, $nextMatchIdx));
                return $subPath->startsWith($matchPath, $strict);
            }

            if (!$this->pathPartMatches($toMatch, $value, $strict)) return false;
        }

        return true;
    }

    protected function pathPartMatches(string $toMatch, string $value, bool $strict): bool
    {
        if ($strict) return $toMatch === $value;

        return $toMatch === $value || str_starts_with($toMatch, "*") || str_starts_with($toMatch, ":");
    }
}