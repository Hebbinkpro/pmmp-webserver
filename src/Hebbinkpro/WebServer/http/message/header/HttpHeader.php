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

namespace Hebbinkpro\WebServer\http\message\header;

use Hebbinkpro\WebServer\exception\HttpHeaderException;
use Hebbinkpro\WebServer\exception\HttpProblemException;
use Hebbinkpro\WebServer\http\HttpParsingRules;
use Hebbinkpro\WebServer\utils\RegexUtils;

/**
 * Class for managing HTTP headers inside an HTTP request or response message
 */
readonly class HttpHeader implements Header
{
	/**
	 * Normalize header field names.
	 *
	 * Normalization is done by removing illegal field name characters such that the returned field name
	 * only contains valid characters where its words start with an uppercase with lowercase continuation.
	 * Examples:
	 *  - "Content-Length" -> "Content-Length"
	 *  - "content-length" -> "Content-Lenght" | Capitalize the first letter of a word
	 *  - "cOnTeNt-lEnGtH" -> "Content-Lenght" | Capitalize the first and lower the other letters of a word
	 *  - "  Content-Length  " -> "Content-Length" | Remove whitespaces from the start end end
	 *  - "content length" -> "Content-Length" | Replaces whitespaces within the name with a "-"
	 *  - "@Content-Length@" -> "Content-Length"  | Removes the illegal character "@" from start and end
	 *  - "Content@Length" -> "Content-Length" | Replaces the illegal character with a "-"
	 *  - "Content@~@Length" -> "Content-Length" | Replace a sequence of illegal characters with a single "-"
	 *
	 * Is this method unnecesarraly complex? Yes it is, but it returns nice header field names.
	 * @param string $fieldName the field name to normalize
	 * @return string the normalized field name
	 * @see HttpParsingRules::TCHAR for the allowed field name characters
	 */
    public static function normalizeFieldName(string $fieldName): string
    {
	    $normalizedName = trim($fieldName);

	    if (!RegexUtils::has_preg_match("/^" . HttpParsingRules::TOKEN . "$/", $normalizedName)) {

		    $illegalChars = "[^" . HttpParsingRules::TCHAR . "]";

		    // Strip illegal characters from the beginning/end.
		    $normalizedName = @preg_replace(
			    "/^$illegalChars+|$illegalChars+$/",
			    '',
			    $normalizedName
		    );

		    if (!is_string($normalizedName)) {
			    throw HttpProblemException::internalServerError(detail: "Could not parse header field name: '$normalizedName'");
		    }

		    // Replace illegal characters in the middle.
		    $normalizedName = preg_replace(
			    "/$illegalChars+/",
			    '-',
			    $normalizedName
		    );

		    if (!is_string($normalizedName)) {
			    throw HttpProblemException::internalServerError(detail: "Could not parse header field name: '$normalizedName'");
		    }
	    }

	    // format the words like headers
	    return ucwords(strtolower($normalizedName), '-');
    }

	/**
	 * @var array<string, string[]>
	 */
	private array $headerFields;

    /**
     * @param array<string, string[]> $headerFields
     */
	public function __construct(array $headerFields)
    {
	    // ensure that all header fields are normalized
	    $normalizedHeaderFields = [];
	    foreach ($headerFields as $name => $value) {
		    $normalizedHeaderFields[self::normalizeFieldName($name)] = $value;
	    }

	    $this->headerFields = $normalizedHeaderFields;
    }

    /**
     * Get the value of a header field
     * @param string $fieldName the header name
     * @param string|null $default the default value when the header is not available
     * @param int $index index of the header value to return in case of multiple header entries, 0 by default
     * @return string|null
     */
    public function getFieldValue(string $fieldName, ?string $default = null, int $index = 0): ?string
    {
        $field = $this->headerFields[self::normalizeFieldName($fieldName)] ?? null;
        if ($field === null) return $default;

        return $field[$index] ?? $default;
    }

    /**
     * Get all values of a header field
     * @param string $fieldName
     * @return string[] the values or an empty array if the field does not exist
     */
    public function getField(string $fieldName): array
    {
        return $this->headerFields[self::normalizeFieldName($fieldName)] ?? [];
    }

    /**
     * Check if the field name exists in the header
     * @param string $fieldName
     * @return bool
     */
    public function fieldExists(string $fieldName): bool
    {
        return array_key_exists(self::normalizeFieldName($fieldName), $this->headerFields);
    }

    /**
     * Get the header fields
     * @return array<string, string[]>
     */
    public function getHeaderFields(): array
    {
        return $this->headerFields;
    }

    /**
     * Encode the HTTP header.
     *
     * Each field line will end with a new-line (`\r\n`) character
     *
     * Example:
     * ```
     * """
     * Content-Length: 123\r\n
     * Content-Type: text/html\r\n
     * """
     * ```
     * @return string
     */
    public function toString(): string
    {
        $res = "";
        foreach ($this->headerFields as $name => $values) {
            // apply all values in-order
            foreach ($values as $value) {
	            $res .= self::normalizeFieldName($name) . ": " . $value . "\r\n";
            }
        }

        return $res;
    }
}