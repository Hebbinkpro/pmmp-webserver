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

namespace Hebbinkpro\WebServer\router;

use Hebbinkpro\WebServer\http\HttpMethod;
use Hebbinkpro\WebServer\http\uri\UriPath;
use Hebbinkpro\WebServer\route\BaseRoute;
use PHPUnit\Framework\TestCase;

class RoutingNodeTest extends TestCase
{

	public function testRoutingNode(): void
	{

		$node = new RoutingNode();
		$emptyPath = new UriPath();
		$route0 = new BaseRoute(HttpMethod::GET, null);

		$node->addRoute($emptyPath, $route0);
		$this->assertEquals($route0, $node->getRoute($emptyPath, HttpMethod::GET));

		$path = UriPath::parse("/test/example/route");
		$route1 = new BaseRoute(HttpMethod::POST, null);
		$node->addRoute($path, $route1);
		$this->assertEquals($route1, $node->getRoute($path, HttpMethod::POST));

		$route2 = new BaseRoute(HttpMethod::ALL, null);
		$node->addRoute($emptyPath, $route2);
		$this->assertEquals($route0, $node->getRoute($emptyPath, HttpMethod::GET));
		$this->assertEquals($route2, $node->getRoute($emptyPath, HttpMethod::POST));
		$this->assertEquals($route2, $node->getRoute($emptyPath, HttpMethod::PUT));
		$this->assertEquals($route2, $node->getRoute($emptyPath, HttpMethod::DELETE));
	}

}