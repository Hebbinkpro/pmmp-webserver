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

namespace Hebbinkpro\WebServer\http\message\header;

use Hebbinkpro\WebServer\exception\HttpProblemException;
use Hebbinkpro\WebServer\http\HttpParsingRules;
use InvalidArgumentException;

class HttpHeaderBuilder extends BaseHttpHeader
{

    /**
     * Set a header field and its value
     *
     * Note: The header will be normalized (trimmed and made lowercase) as a header is case insensitive.
     * @param string $fieldName
     * @param string $value
     * @return void
     */
    public function setField(string $fieldName, string $value): void
    {
        // normalize the field name, such that it is case insensitive
        $name = self::normalizeFieldName($fieldName);

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
    }

    /**
     * Set a header field from an unparsed field line
     *
     * Validates and parses the field line before the field is set.
     * @param string $fieldLine the field line
     * @return void
     * @throws HttpProblemException if the field line is malformed
     */
    public function setFromFieldLine(string $fieldLine): void
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
        $this->setField($parts[0], trim($parts[1]));
    }

    /**
     * Set a field only if the field name is not yet set
     * @param string $fieldName
     * @param string $value
     * @return void
     */
    public function setFieldIfAbsent(string $fieldName, string $value): void
    {
        $name = self::normalizeFieldName($fieldName);
        $this->headerFields[$name] ??= [$value];
    }

    /**
     * Remove a value from a field
     * @param string $fieldName
     * @param int $index the array_splice offset of the value to remove, default is the last element (-1)
     * @return void
     */
    public function unsetFieldValue(string $fieldName, int $index = -1): void
    {
        $name = self::normalizeFieldName($fieldName);

        // does not exist
        if (!isset($this->headerFields[$name])) return;

        // remove the element at the given index, and reindex the array
        $this->headerFields[$name] = array_values(array_splice($this->headerFields[$name], $index, 1));

        // check if the field has still values, otherwise remove it
        if (count($this->headerFields[$name]) == 0) unset($this->headerFields[$name]);
    }

    /**
     * Remove an entire field
     * @param string $fieldName
     * @return void
     */
    public function unsetField(string $fieldName): void
    {
        unset($this->headerFields[self::normalizeFieldName($fieldName)]);
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
     * Create a header builder from a list of field lines
     * @param string[] $fieldLines encoded header field lines
     * @return HttpHeaderBuilder
     * @throws HttpProblemException if a header is malformed
     */
    public static function parse(array $fieldLines): self
    {
        $headerFields = new HttpHeaderBuilder();
        foreach ($fieldLines as $line) {
            $headerFields->setFromFieldLine($line);
        }

        return $headerFields;
    }

}