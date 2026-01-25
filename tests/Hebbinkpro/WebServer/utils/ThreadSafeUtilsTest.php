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

namespace Hebbinkpro\WebServer\utils;

use PHPUnit\Framework\TestCase;
use pmmp\thread\ThreadSafe;
use pmmp\thread\ThreadSafeArray;
use pocketmine\thread\NonThreadSafeValue;

class ThreadSafeUtilsTest extends TestCase
{
    public const ALWAYS_SAFE = [true, false, 1, 3.14, "string"];

    public function testIsThreadSafe()
    {
        foreach (self::ALWAYS_SAFE as $value) {
            $this->assertTrue(ThreadSafeUtils::isThreadSafe($value));
        }
        $this->assertTrue(ThreadSafeUtils::isThreadSafe(new ThreadSafe()));

        $this->assertFalse(ThreadSafeUtils::isThreadSafe([1, 2, 3, 4, 5]));
        $this->assertFalse(ThreadSafeUtils::isThreadSafe(new \StdClass()));
    }

    public function testMakeThreadSafe()
    {

        foreach (self::ALWAYS_SAFE as $value) {
            $this->assertSame($value, ThreadSafeUtils::makeThreadSafe($value));
        }

        $threadSafe = new ThreadSafe();
        $this->assertSame($threadSafe, ThreadSafeUtils::makeThreadSafe($threadSafe));

        $array = [1, 2, 3, 4, 5];
        $this->assertEquals(ThreadSafeArray::fromArray($array), ThreadSafeUtils::makeThreadSafe($array));

        $notThreadSafe = new \StdClass();
        $this->assertEquals(new NonThreadSafeValue($notThreadSafe), ThreadSafeUtils::makeThreadSafe($notThreadSafe));
    }

    public function testUnwrapThreadSafe()
    {
        foreach (self::ALWAYS_SAFE as $value) {
            $this->assertSame($value, ThreadSafeUtils::unwrapThreadSafe($value));
        }

        $threadSafe = new ThreadSafe();
        $this->assertSame($threadSafe, ThreadSafeUtils::unwrapThreadSafe($threadSafe));

        $array = [1, 2, 3, 4, 5];
        $this->assertEquals($array, ThreadSafeUtils::unwrapThreadSafe(ThreadSafeArray::fromArray($array)));

        $notThreadSafe = new \StdClass();
        $this->assertEquals($notThreadSafe, ThreadSafeUtils::unwrapThreadSafe(new NonThreadSafeValue($notThreadSafe)));
    }

    public function testMakeThreadSafeArray()
    {
        $array = [1, 2, 3, 4, 5];
        $this->assertEquals(ThreadSafeArray::fromArray($array), ThreadSafeUtils::makeThreadSafeArray($array));

        $array = [new ThreadSafe(), new ThreadSafe()];
        $this->assertEquals(ThreadSafeArray::fromArray($array), ThreadSafeUtils::makeThreadSafeArray($array));

        $array = [new \StdClass(), new \StdClass()];

        $result = ThreadSafeArray::fromArray([new NonThreadSafeValue(new \StdClass()), new NonThreadSafeValue(new \StdClass())]);
        $this->assertEquals($result, ThreadSafeUtils::makeThreadSafeArray($array));

        $array = ["a" => [1, 2, 3], "b" => [4, 5, 6]];
        $result = ThreadSafeArray::fromArray(["a" => ThreadSafeArray::fromArray([1, 2, 3]), "b" => ThreadSafeArray::fromArray([4, 5, 6])]);
        $this->assertEquals($result, ThreadSafeUtils::makeThreadSafeArray($array));
    }

    public function testUnwrapThreadSafeArray()
    {

        $input = [
            [1, 2, 3, 4, 5],
            [new ThreadSafe(), new ThreadSafe()],
            [new \StdClass(), new \StdClass()],
            ["a" => [1, 2, 3], "b" => [4, 5, 6]],
            ["a" => new \StdClass(), "b" => [new ThreadSafe()], "c" => [[0, 1], [1, 0]], "d" => [[new \StdClass()], new ThreadSafe()]]
        ];

        foreach ($input as $array) {
            $this->assertEquals($array, ThreadSafeUtils::unwrapThreadSafeArray(ThreadSafeUtils::makeThreadSafeArray($array)));
        }
    }

}