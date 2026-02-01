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

/**
 * Class for managing HTTP headers inside an HTTP request or response message
 */
readonly class HttpHeader implements Header
{
    public static function normalizeFieldName(string $fieldName): string
    {
        return strtolower(trim($fieldName));
    }

    /**
     * @param array<string, string[]> $headerFields
     */
    public function __construct(private array $headerFields)
    {
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
     * Encode the request headers
     * @return string
     */
    public function toString(): string
    {
        $res = "";
        foreach ($this->headerFields as $name => $values) {
            // apply all values in-order
            foreach ($values as $value) {
                $res .= $name . ": " . $value . "\r\n";
            }
        }

        return $res;
    }
}