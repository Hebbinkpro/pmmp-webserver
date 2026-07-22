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

use Hebbinkpro\WebServer\exception\HttpProblemException;
use Hebbinkpro\WebServer\http\HttpParsingRules;
use InvalidArgumentException;

class HttpHeaderBuilder implements Header
{

    private array $headerFields;

    public function __construct()
    {
        $this->headerFields = [];
    }

    /**
     * Add a value to a field
     *
     * Note: The header will be normalized (trimmed and made lowercase) as a header is case insensitive.
     * @param string $fieldName
     * @param string $value
     * @return HttpHeaderBuilder
     */
    public function addField(string $fieldName, string $value): self
    {
        // normalize the field name, such that it is case insensitive
        $name = HttpHeader::normalizeFieldName($fieldName);

        // validate name
        if (!@preg_match("/^" . HttpParsingRules::TOKEN . "$/", $name)) {
            throw new InvalidArgumentException("Invalid header name: '$name', does not match RFC 9110 section 5.1");
        }

        // validate value
        if (!@preg_match("/^(" . HttpParsingRules::FIELD_CONTENT . ")*$/", $value)) {
            throw new InvalidArgumentException("Invalid header value: '$value', does not match RFC 9110 section 5.2");
        }

        // duplicate headers are allowed, just stored in an array
        if (!isset($this->headerFields[$name])) $this->headerFields[$name] = [$value];
        else $this->headerFields[$name][] = $value;
        return $this;
    }

    /**
     * Set a header field from an unparsed field line
     *
     * Validates and parses the field line before the field is set.
     * @param string $fieldLine the field line
     * @return HttpHeaderBuilder
     */
    public function setFromFieldLine(string $fieldLine): self
    {
        // invalid field line, does not match RFC 9110 section 5
        if (!@preg_match("/^" . HttpParsingRules::FIELD_LINE . "$/", $fieldLine)) {
            throw HttpProblemException::badRequest(null, "Malformed header");
        }

        $parts = explode(":", $fieldLine, 2);

        // this is actually a redundant check, as the : is definitely inside the string
        if (sizeof($parts) != 2) {
            throw HttpProblemException::badRequest(null, "Malformed header");
        }

        // trim the value to remove optional whitespace
        $this->addField($parts[0], trim($parts[1]));
        return $this;
    }

    /**
     * Set a field only if the field name is not yet set
     *
     * @param string $fieldName
     * @param string $value
     * @return HttpHeaderBuilder
     */
    public function setFieldIfAbsent(string $fieldName, string $value): self
    {
        if (!$this->fieldExists($fieldName)) {
            $this->addField($fieldName, $value);
        }
        return $this;
    }

    /**
     * Set a field and its name.
     *
     * Ensures that all previously set values for the field are removed.
     * @param string $fieldName
     * @param string $value the new value
     * @return $this
     */
    public function setField(string $fieldName, string $value): self
    {
        return $this->unsetField($fieldName)->addField($fieldName, $value);
    }

    /**
     * Remove a value from a field
     * @param string $fieldName
     * @param int $index the array_splice offset of the value to remove, default is the last element (-1)
     * @return HttpHeaderBuilder
     */
    public function unsetFieldValue(string $fieldName, int $index = -1): self
    {
        $name = HttpHeader::normalizeFieldName($fieldName);

        // does not exist
        if (!isset($this->headerFields[$name])) return $this;

        // remove the element at the given index and reindex the array
        $this->headerFields[$name] = array_values(array_splice($this->headerFields[$name], $index, 1));

        // check if the field has values, otherwise remove it
        if (count($this->headerFields[$name]) == 0) unset($this->headerFields[$name]);
        return $this;
    }

    /**
     * Remove an entire field
     * @param string $fieldName
     * @return HttpHeaderBuilder
     */
    public function unsetField(string $fieldName): self
    {
        unset($this->headerFields[HttpHeader::normalizeFieldName($fieldName)]);
        return $this;
    }

    /**
     * Create an HttpHeader object from the builder
     * @return HttpHeader
     */
    public function build(): HttpHeader
    {
        return new HttpHeader($this->headerFields);
    }


    /**
     * Get all set header fields
     * @return array<string, string[]>
     */
    public function getHeaderFields(): array
    {
        return $this->headerFields;
    }

    /**
     * Create a header builder from a list of field lines
     * @param string $header the HTTP header
     * @return HttpHeaderBuilder
     */
    public static function parse(string $header): self
    {
        $fieldLines = explode("\r\n", $header);

        $headerFields = new HttpHeaderBuilder();
        foreach ($fieldLines as $line) {
            $headerFields->setFromFieldLine($line);
        }

        return $headerFields;
    }

    /**
     * Get a specific value of a header field
     * @param string $fieldName the header name
     * @param string|null $default the returned value when the header does not exist
     * @param int $index index of the header value to return, 0 by default
     * @return string|null
     */
    public function getFieldValue(string $fieldName, ?string $default = null, int $index = 0): ?string
    {
        $field = $this->headerFields[HttpHeader::normalizeFieldName($fieldName)] ?? null;
        if ($field === null) return $default;

        return $field[$index] ?? $default;
    }

    /**
     * Get all values of a header field
     * @param string $fieldName
     * @return string[] the field values or an empty array if the field does not exist
     */
    public function getField(string $fieldName): array
    {
        return $this->headerFields[HttpHeader::normalizeFieldName($fieldName)] ?? [];
    }

    /**
     * Check if the field name exists in the header
     * @param string $fieldName
     * @return bool
     */
    public function fieldExists(string $fieldName): bool
    {
        return array_key_exists(HttpHeader::normalizeFieldName($fieldName), $this->headerFields);
    }
}