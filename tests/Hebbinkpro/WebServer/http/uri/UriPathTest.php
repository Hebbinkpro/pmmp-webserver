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

namespace Hebbinkpro\WebServer\http\uri;

use PHPUnit\Framework\TestCase;

class UriPathTest extends TestCase
{

    public function testUriMatches()
    {

        $pathParts = ["foo", "bar", "baz"];
        $path = new UriPath($pathParts);

	    for ($i = 0; $i < count($pathParts); $i++) {
		    $slice = array_slice($pathParts, 0, $i);
		    $this->assertFalse($path->matches(new UriPath($slice), true));
	    }
	    $this->assertTrue($path->matches($path, true));

        for ($i = 0; $i <= count($pathParts); $i++) {
            $slice = array_slice($pathParts, 0, $i);
	        $this->assertTrue($path->matches(new UriPath($slice), false));
        }

        $this->assertFalse($path->matches(new UriPath(["bar"])));
        $this->assertFalse($path->matches(new UriPath(["bar", "baz"])));

        $this->assertTrue($path->matches(new UriPath(["foo", ":a", "baz"])));
        $this->assertTrue($path->matches(new UriPath(["foo", "bar", "*"])));
	    $this->assertTrue($path->matches(new UriPath(["foo", "*", "baz"])));

	    $this->assertTrue((new UriPath([]))->matches(new UriPath(["*"])));
    }

    public function testUriGetMatchingPath()
    {

        $pathParts = ["foo", "bar", "baz"];
        $path = new UriPath($pathParts);

        for ($i = 0; $i <= count($pathParts); $i++) {
            $slice = array_slice($pathParts, 0, $i);
	        $this->assertTrue($path->getMatchingPath(new UriPath($slice), false)?->equals(new UriPath($slice)));
        }
	    $this->assertTrue($path->getMatchingPath($path, false)?->equals($path));


        $this->assertNull($path->getMatchingPath(new UriPath(["bar"])));
        $this->assertNull($path->getMatchingPath(new UriPath(["bar", "baz"])));

        $this->assertTrue($path->getMatchingPath(new UriPath(["foo", ":a", "baz"]))?->equals($path));
        $this->assertTrue($path->getMatchingPath(new UriPath(["foo", "bar", "*"]))?->equals(new UriPath(["foo", "bar"])));
	    $this->assertTrue($path->getMatchingPath(new UriPath(["*"]))?->equals(new UriPath([])));
	    $this->assertTrue($path->getMatchingPath(new UriPath(["foo", "*", "baz"]))?->equals($path));
	    $this->assertTrue($path->getMatchingPath(new UriPath(["foo", "bar", "*"]))?->equals(new UriPath(["foo", "bar"])));

	    $this->assertTrue($path->getMatchingPath(new UriPath(["*", "bar", "baz"]))?->equals($path));
	    $this->assertNull($path->getMatchingPath(new UriPath(["*", "baz"])));
    }

    public function testUriGetMatchingPathWithParams()
    {
        $uri = new UriPath(["foo", "bar", "baz"]);
        $match = new UriPath([":a", ":b", ":c"]);

        $matchParams = [];
        $res = $uri->getMatchingPath($match, false, $matchParams);
        $this->assertNotNull($res);
        $this->assertEquals([
            "a" => "foo",
            "b" => "bar",
            "c" => "baz"
        ], $matchParams);

	    $match = new UriPath(["*", "bar", ":c"]);
        $matchParams = [];
        $res = $uri->getMatchingPath($match, false, $matchParams);

        $this->assertNotNull($res);
        $this->assertEquals([
            "c" => "baz"
        ], $matchParams);
    }


    public function testEquals(): void
    {
        $this->assertTrue((new UriPath(["foo", "bar", "baz"]))->equals(new UriPath(["foo", "bar", "baz"])));
        $this->assertFalse((new UriPath(["foo", "bar", "baz"]))->equals(new UriPath(["foo", "bar"])));
        $this->assertFalse((new UriPath(["foo", "bar", "baz"]))->equals(new UriPath([])));
        $this->assertFalse((new UriPath(["foo", "bar", "baz"]))->equals(new UriPath(["foo", "bar", "baz", "abc"])));
    }

    public function testGetSubPath()
    {
        $pathParts = ["foo", "bar", "baz"];
        $path = new UriPath($pathParts);

	    $this->assertTrue($path->getFilePath(new UriPath(["foo", ":a", "baz"]))?->equals(new UriPath([])));
	    $this->assertTrue($path->getFilePath(new UriPath(["foo", "bar", "*"]))?->equals(new UriPath(["baz"])));
	    $this->assertTrue($path->getFilePath(new UriPath(["*"]))?->equals($path));
	    $this->assertTrue($path->getFilePath(new UriPath(["*", "bar", "baz"]))?->equals(new UriPath([])));
	    $this->assertTrue($path->getFilePath(new UriPath(["foo", "*", "baz"]))?->equals(new UriPath([])));
	    $this->assertTrue($path->getFilePath(new UriPath(["foo", "bar", "*"]))?->equals(new UriPath(["baz"])));
	    $this->assertTrue($path->getFilePath(new UriPath(["*", "bar"]))?->equals(new UriPath(["baz"])));

	    $this->assertNull($path->getFilePath(new UriPath(["*", "foo"])));
    }

	public function testHasFilePathWildcard()
	{
		$this->assertTrue((new UriPath(["*"]))->hasFilePathWildcard());
		$this->assertTrue((new UriPath(["foo", "*"]))->hasFilePathWildcard());
		$this->assertTrue((new UriPath(["foo", "bar", "*"]))->hasFilePathWildcard());

		$this->assertFalse((new UriPath())->hasFilePathWildcard());
		$this->assertFalse((new UriPath(["foo"]))->hasFilePathWildcard());
		$this->assertFalse((new UriPath(["foo", "bar"]))->hasFilePathWildcard());
	}
}