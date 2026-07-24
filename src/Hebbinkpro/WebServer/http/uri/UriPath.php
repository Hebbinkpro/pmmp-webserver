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

declare(strict_types=1);

namespace Hebbinkpro\WebServer\http\uri;

use Hebbinkpro\WebServer\utils\ThreadSafeUtils;
use pmmp\thread\ThreadSafe;
use pmmp\thread\ThreadSafeArray;

/**
 * A route path that stores all path parts as string in a ThreadSafe array
 */
class UriPath extends ThreadSafe implements UriElement
{
    /** @var ThreadSafeArray<string> */
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
        if (strlen($value) === 0) return new self([]);

        // split at / and decode each value
        $path = array_map("rawurldecode", explode("/", $value));
        return new self($path);
    }

    /**
     * Append a new path to the existing path
     * @param UriPath $path the path to add
     * @return UriPath the resulting path
     */
    public function appendPath(UriPath $path): UriPath
    {
        $newPath = array_merge($this->asArray(), $path->asArray());
        return new self($newPath);
    }

    /**
     * Get a subpath by removing the parent path from the current path
     * @param UriPath $parent the parent path to remove
     * @param array|null $params if not null, parameters in the parent path will be set
     * @return UriPath|null the resulting subpath, or null when the current path does not start with the parent
     */
    public function getSubPath(UriPath $parent, ?array &$params = null): ?UriPath
    {
        // first check if this path starts with the given path
        if (!$this->startsWith($parent)) return null;

        $path = $this->asArray();
        $parentPath = $parent->asArray();

        if ($params !== null) {
            for ($i = 0; $i < count($path); $i++) {
                if (str_starts_with($parentPath[$i], ":")) {
                    $params[substr($parentPath[$i], 1)] = $path[$i];
                }
            }
        }

        $subPath = array_slice($path, $parent->getLength());
        return new self($subPath);
    }

    /**
     * @return string[]
     */
    public function asArray(): array
    {
        /** @phpstan-ignore-next-line */
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
        if ($this->path->count() === 0) return "/";

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
     * Get if this path matches the given path
     *
     * If the given path contains wildcards, they will be taken into account
     * @param UriPath $matchPath the path to check
     * @param bool $strict if the paths should match exactly, if true wildcards will be ignored.
     * @return bool
     */
    public function matches(UriPath $matchPath, bool $strict = false): bool
    {
        return $this->getMatchingPath($matchPath, $strict) !== null;
    }

    /**
     * Get if this path matches the given path
     *
     * If the given path contains wildcards, they will be taken into account
     * @param UriPath $matchPath the path to check
     * @param bool $strict if the paths should match exactly, if true wildcards will be ignored.
     * @param array<string,string> $params if strict is false, sets url parameters from the matchpath to
     *                                     their coresponding values in the base path.
     * @return UriPath|null
     */
    public function getMatchingPath(UriPath $matchPath, bool $strict = false, ?array &$params = null): ?UriPath
    {
        $matchPattern = $matchPath->asArray();
        $pathLength = $this->getLength();
        $matchLength = $matchPath->getLength();

        // path length can never be shorter then the path it should match
        if ($pathLength < $matchLength) return null;

        $matchingPath = [];
        $matchingParams = [];

        $patternIdx = 0;
        // iterate through thte entire path
        foreach ($this->path as $value) {
            // end of pattern, matching path is valid
            if ($patternIdx >= $matchLength) break;

            // get next value to be matched and increment the pattern index (after assignment)
            $toMatch = $matchPattern[$patternIdx++];
            $isFinalMatch = $patternIdx >= $matchLength;

            // ** is a unique case as we need to recursively check the path until a new value arises
            if (!$strict && str_starts_with($toMatch, "**")) {
                // final match (since pathIdx is already incremented at $toMatch, we dont need a +1)
                if ($isFinalMatch) break;

                // check if current value matches the next one
                // also take care of any other ** parts in the path, since that would be possible
                $nextMatchIdx = $patternIdx;
                do {
                    $nextMatch = $matchPattern[$nextMatchIdx++];
                } while (str_starts_with($nextMatch, "**") && $nextMatchIdx < $matchLength);

                $nextMatchIdx--; // decrement 1, to remove the last increment of the while loop

                // we got multiple ** parts until the end of the path
                if (str_starts_with($nextMatch, "**")) {
                    // it was a final match
                    break;
                }

                # -1 to account for "current" toMatch value
                $nextIdx = $patternIdx - 1;
                while ($nextIdx < $pathLength) {
                    $nextValue = $this->path[$nextIdx];

                    if ($this->pathPartMatches($nextMatch, $nextValue, false)) {
                        break;
                    }

                    $matchingPath[] = $nextValue;
                    $nextIdx++;
                }

                if ($nextIdx >= $pathLength) {
                    return null;
                }

                // get the matching path between our remaining path and matching path
                $subPath = new UriPath(array_slice($this->asArray(), $nextIdx));
                $matchSubPath = new UriPath(array_slice($matchPattern, $nextMatchIdx));

                $matchingSubPath = $subPath->getMatchingPath($matchSubPath, $strict, $matchingParams);
                if ($matchingSubPath === null) return null;

                // append the matching subpath to the already existing matching path
                $matchingPath = array_merge($matchingPath, $matchingSubPath->asArray());
                break;
            }

            if (!$this->pathPartMatches($toMatch, $value, $strict)) {
                return null;
            }

            if (!$strict) {
                // if match ends on a *, do not include the value in the matching path
                if ($isFinalMatch && $toMatch === "*") break;

                // store path parameters when encountered
                if (str_starts_with($toMatch, ":")) {
                    $matchingParams[substr($toMatch, 1)] = $value;
                }
            }

            $matchingPath[] = $value;
        }

        // only on success, add the matched parameters to the provided params array
        if (!$strict && $params !== null) {
            foreach ($matchingParams as $key => $value) {
                $params[$key] = $value;
            }
        }

        // return the matching path
        return new UriPath($matchingPath);
    }

    /**
     * Check if a part of a path matches a value
     * @param string $toMatch the part of the path
     * @param string $value the value to match
     * @param bool $strict wether the path part should match strictly. If false, wildcards are allowed matches.
     * @return bool
     */
    protected function pathPartMatches(string $toMatch, string $value, bool $strict): bool
    {
        if ($strict) return $toMatch === $value;

        return $toMatch === $value || str_starts_with($toMatch, "*") || str_starts_with($toMatch, ":");
    }

    /**
     * Get if this UriPath is the same as the given UriPath
     * @param UriPath $path
     * @return bool
     */
    public function equals(UriPath $path): bool
    {
        return $this->asArray() === $path->asArray();
    }
}