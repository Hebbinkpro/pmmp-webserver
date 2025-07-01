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

namespace Hebbinkpro\WebServer\http;

/**
 * List of common content types
 */
class HttpContentType
{
    public const APPLICATION_JAVA = "application/java-archive";
    public const APPLICATION_EDI_X12 = "application/EDI-X12";
    public const APPLICATION_EDIFACT = "application/EDIFACT";
    public const APPLICATION_JAVASCRIPT = "application/javascript";
    public const APPLICATION_OCTET_STREAM = "application/octet-stream";
    public const APPLICATION_OGG = "application/ogg";
    public const APPLICATION_PDF = "application/pdf";
    public const APPLICATION_XHTML_XML = "application/xhtml+xml";
    public const APPLICATION_X_SHOCKWAVE_FLASH = "application/x-shockwave-flash";
    public const APPLICATION_JSON = "application/json";
    public const APPLICATION_LD_JSON = "application/ld+json";
    public const APPLICATION_XML = "application/xml";
    public const APPLICATION_ZIP = "application/zip";
    public const APPLICATION_X_WWW_FORM_URLENCODED = "application/x-www-form-urlencoded";

    public const AUDIO_MPEG = "audio/mpeg";
    public const AUDIO_X_MS_MWA = "audio/x-ms-wma";
    public const AUDIO_VND_RN_REALAUDIO = "audio/vnd.rn-realaudio";
    public const AUDIO_X_WAV = "audio/x-wav";

    public const IMAGE_GIF = "image/gif";
    public const IMAGE_JPEG = "image/jpeg";
    public const IMAGE_PNG = "image/png";
    public const IMAGE_TIFF = "image/tiff";
    public const IMAGE_VND_MICROSOFT_ICON = "image/vnd.microsoft.icon";
    public const IMAGE_X_ICON = "image/x-icon";
    public const IMAGE_VND_DJVU = "image/vnd.djvu";
    public const IMAGE_SVG_XML = "image/svg+xml";

    public const MULTIPART_MIXED = "multipart/mixed";
    public const MULTIPART_ALTERNATIVE = "multipart/alternative";
    public const MULTIPART_RELATED = "multipart/related";
    public const MULTIPART_FORM_DATA = "multipart/form-data";

    public const TEXT_CSS = "text/css";
    public const TEXT_CSV = "text/csv";
    public const TEXT_HTML = "text/html";
    public const TEXT_JAVASCRIPT = "text/javascript";
    public const TEXT_PLAIN = "text/plain";
    public const TEXT_XML = "text/xml";

    public const VIDEO_MPEG = "video/mpeg";
    public const VIDEO_MP4 = "video/mp4";
    public const VIDEO_QUICKTIME = "video/quicktime";
    public const VIDEO_X_MS_WMV = "video/x-ms-wmv";
    public const VIDEO_X_MSVIDEO = "video/x-msvideo";
    public const VIDEO_X_FLV = "video/x-flv";
    public const VIDEO_WEBM = "video/webm";
}