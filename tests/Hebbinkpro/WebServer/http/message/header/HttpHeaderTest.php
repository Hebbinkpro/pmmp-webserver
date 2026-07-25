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

namespace Hebbinkpro\WebServer\http\message\header;

use Hebbinkpro\WebServer\exception\HttpProblemException;
use PHPUnit\Framework\TestCase;

class HttpHeaderTest extends TestCase
{
    public function testNormalizeFieldName()
    {

	    /**
	     *
	     *   - "Content-Length" -> "Content-Length"
	     *   - "content-length" -> "Content-Lenght" | Capitalize the first letter of a word
	     *   - "cOnTeNt-lEnGtH" -> "Content-Lenght" | Capitalize the first and lower the other letters of a word
	     *   - "  Content-Length  " -> "Content-Length" | Remove whitespaces from the start end end
	     *   - "content length" -> "Content-Length" | Replaces whitespaces within the name with a "-"
	     *   - "@Content-Length@" -> "Content-Length"  | Removes the illegal character "@" from start and end
	     *   - "Content@Length" -> "Content-Length" | Replaces the illegal character with a "-"
	     *   - "Content@~@Length" -> "Content-Length" | Replace a sequence of illegal characters with a single "-"
	     */

	    $tests = [
		    "Content-Length" => [
			    "Content-Length",
			    "content-length",
			    "cOnTeNt-lEnGtH",
			    "content length",
			    "  Content-Length  ",
			    "@Content-Length@",
			    "Content@Length",
			    "Content@@@Length",
			    "  @Content@         @Length@ ",
		    ],
		    "!content-Type#" => [
			    "!Content@@@Type#"
		    ],
	    ];

	    foreach ($tests as $result => $values) {
		    foreach ($values as $value) {
			    $this->assertEquals($result, HttpHeader::normalizeFieldName($value));
		    }
	    }
    }

    public function testBuilder()
    {
        $builder = new HttpHeaderBuilder();
        $this->assertEquals([], $builder->getHeaderFields());
        $this->assertEquals([], $builder->build()->getHeaderFields());

        // test set
        $builder->setField("Content-Type", "text/html");
        $builder->setField("Content-Length", "123");
        $builder->setField("Server", "Hebbinkpro/WebServer");

	    $this->assertEquals(["Content-Type" => ["text/html"], "Content-Length" => ["123"], "Server" => ["Hebbinkpro/WebServer"]], $builder->getHeaderFields());
        $this->assertEquals($builder->getHeaderFields(), $builder->build()->getHeaderFields());
        $this->assertEquals("text/html", $builder->build()->getFieldValue("Content-Type"));

        $builder->setFromFieldLine("Content-Type: text/plain");
        $this->assertEquals(["text/html", "text/plain"], $builder->build()->getField("Content-Type"));

	    $header = "Content-Type: text/html\r\nContent-Type: text/plain\r\nContent-Length: 123\r\nServer: Hebbinkpro/WebServer\r\n";
        $this->assertEquals($header, $builder->build()->toString());

        // test add to existing field
        $builder->addField("Content-Length", "456");
        $this->assertEquals(["123", "456"], $builder->build()->getField("Content-Length"));

        // test get field value at other index
        $this->assertEquals("456", $builder->build()->getFieldValue("Content-Length", index: 1));


        // test get default value for unknown field
        $this->assertEquals(404, $builder->build()->getFieldValue("ABC", default: 404));
        // test get default value for unknown index
        $this->assertEquals(404, $builder->build()->getFieldValue("Content-Length", default: 404, index: 50));

        // test set overwriting field
        $builder->setField("Content-Length", "789");
        $this->assertEquals(["789"], $builder->build()->getField("Content-Length"));


        $this->expectException(HttpProblemException::class);
        $builder->setFromFieldLine("Content-Type : text/plain");
    }
}