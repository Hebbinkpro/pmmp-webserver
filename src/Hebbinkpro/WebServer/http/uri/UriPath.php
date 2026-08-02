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
     * Get the file path of a matched URI
     *
     * This method returns the opposite of getMatchPath, as it returns the remaining part of the URI.
     * Since this is always an empty array when `hasFilePathWildcard()` is true,
     * @param UriPath $matchPath the path to check
     * @param bool $strict if the paths should match exactly, if true wildcards will be ignored.
     * @param array<string,string> $params if strict is false, sets url parameters from the matchpath to
     *                                      their coresponding values in the base path.
     * @returns UriPath|null the sub path or null if the path did not match
     * @see getMatchingPath()
     */
	public function getFilePath(UriPath $matchPath, bool $strict = false, array &$params = []): ?UriPath
    {
        // get the base path
        $matchingPath = $this->getMatchingPath($matchPath, $strict, $params);
        if ($matchingPath === null) return null;

        $basePath = $matchingPath->asArray();
        $subPath = array_slice($this->asArray(), count($basePath));
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
     * @return UriPath a new UriPath with the appended value
     */
	public function append(string $value): UriPath
    {
	    $newPath = $this->asArray();
	    $newPath[] = $value;

	    return new UriPath($newPath);
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
     * @param bool $strict if the paths should match exactly, if false only the first part has to match
     * @return bool
     */
	public function matches(UriPath $matchPath, bool $strict = true): bool
    {
        return $this->getMatchingPath($matchPath, $strict) !== null;
    }

    /**
     * Get if this path matches the given path
     *
     * If the given path contains wildcards, they will be taken into account
     * @param UriPath $matchPath the path to check
     * @param bool $strict if the paths should match exactly, if false only the first part has to match
     * @param array<string,string> $params sets url parameters from the matchpath to their values in the path.
     * @return UriPath|null
     */
	public function getMatchingPath(UriPath $matchPath, bool $strict = true, array &$params = []): ?UriPath
    {
        $matchPattern = $matchPath->asArray();
        $pathLength = $this->getLength();
        $matchLength = $matchPath->getLength();

	    // path length can never be shorter then the path it should match, but folder wildcards may be empty
	    if ($pathLength < $matchLength && ($matchPath->hasFilePathWildcard() && $pathLength < $matchLength - 1)) {
		    return null;
	    }

	    // if strict, the path can only match if the match path is of equal length, or is a file path
	    if ($strict && $pathLength > $matchLength && !$matchPath->hasFilePathWildcard()) {
		    return null;
	    }

	    /** @var array<string,string> $matchingPath */
        $matchingPath = [];
	    /** @var array<string,string> $matchingParams */
        $matchingParams = [];

        $patternIdx = 0;
        // iterate through thte entire path
        foreach ($this->path as $value) {
            // end of pattern, matching path is valid
            if ($patternIdx >= $matchLength) break;

            // get next value to be matched and increment the pattern index (after assignment)
            $toMatch = $matchPattern[$patternIdx++];
            $isFinalMatch = $patternIdx >= $matchLength;

	        if (!$this->pathPartMatches($toMatch, $value)) {
                return null;
            }

	        // if match ends on a *, do not include the value in the matching path
	        if ($isFinalMatch && str_starts_with($toMatch, "*")) break;

	        // store path parameters when encountered
	        if (str_starts_with($toMatch, ":")) {
		        $matchingParams[substr($toMatch, 1)] = $value;
            }

            $matchingPath[] = $value;
        }

        // only on success, add the matched parameters to the provided params array
	    foreach ($matchingParams as $key => $value) {
		    $params[$key] = $value;
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
	private function pathPartMatches(string $toMatch, string $value, bool $strict = false): bool
    {
        if ($strict) return $toMatch === $value;

	    $first = substr($toMatch, 0, 1);
	    return $toMatch === $value
		    || $first === "*"
		    || $first === ":";
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

	/**
	 * If the path ends with the filepath wildcard (*)
	 * @return bool
	 */
	public function hasFilePathWildcard(): bool
	{
		$count = $this->path->count();
		if ($this->path->count() === 0) return false;

		$last = $this->path->offsetGet($count - 1);
		return str_starts_with($last, "*");
	}

	/**
	 * Check if the first path part matches the given value
	 * @param string $value
	 * @param bool $strict if the value should match exactly
	 * @return bool
	 */
	public function startsWith(string $value, bool $strict = false): bool
	{
		if ($this->path->count() === 0) return false;
		return $this->pathPartMatches($this->path->offsetGet(0), $value, $strict);
	}

	public function slice(int $offset, int $length = null): UriPath
	{
		return new UriPath(array_slice($this->asArray(), $offset, $length));
	}
}