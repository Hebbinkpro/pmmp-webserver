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

namespace Hebbinkpro\WebServer\http;

/**
 * HTTP Parsing Rules based on RFC 5234, 7230 and 9110
 */
final class HttpParsingRules
{
    // --- definitions of RFC 5234 ---

    /**
     * A-Z / a-z
     */
    public const ALPHA = "[A-Za-z]";

    public const BIT = "[01]";

    /**
     * any 7-bit US-ASCII character excluding NUL
     */
    public const CHAR = "[\x01-\x7F]";


    /**
     * carriage return
     */
    public const CR = "\r";

    /**
     * Internet standard newline
     */
    public const CRLF = "\r\n";

    /**
     * controls
     */
    public const CTL = "\x00-\x1F\x7F";

    /**
     * 0-9
     */
    public const DIGIT = "[0-9]";

    /**
     * " (Double Quote)
     */
    public const DQUOTE = "\"";

    public const HEXDIG = "[0-9A-Fa-f]";

    /**
     * horizontal tab
     */
    public const HTAB = "\t";

    /**
     * linefeed
     */
    public const LF = "\n";

    /**
     * linear white space
     */
    public const LWSP = "([ \t] | (\r\n [ \t]))*";

    /**
     * 8 bits of data
     */
    public const OCTET = "[\x00-\xFF]";

    /**
     * space
     */
    public const SP = " ";

    /**
     * visible (printing) characters
     */
    public const VCHAR = "[\x21-\x7E]";

    /**
     * white space
     */
    public const WSP = "[ \t]";


    // --- definitions of RFC 7230 ---

    /**
     * "bad" whitespace
     *
     * @deprecated
     * The BWS rule is used where the grammar allows optional whitespace
     * only for historical reasons.  A sender MUST NOT generate BWS in
     * messages.  A recipient MUST parse for such bad whitespace and remove
     * it before interpreting the protocol element.
     */
    public const BWS = self::OWS;

    /**
     * optional whitespace
     */
    public const OWS = "[ \t]*";

    /**
     * required whitespace
     */
    public const RWS = "[ \t]+";

    public const TCHAR = "[!#$%&'*+\-.^_`|~0-9A-Za-z]";

    public const TOKEN = self::TCHAR . "+";

    public const OBS_TEXT = "[\x80-\xFF]";


    public const FIELD_VCHAR = "(" . self::VCHAR . "|" . self::OBS_TEXT . ")";
    public const FIELD_CONTENT = self::FIELD_VCHAR . "(" . self::RWS . self::FIELD_VCHAR . ")?";
    /** @var string Field line interpretation according to RFC 9110 */
    public const FIELD_LINE = self::TOKEN . ":" . self::OWS . "(" . self::FIELD_CONTENT . ")*" . self::OWS;
}
