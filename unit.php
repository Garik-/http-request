<?php

//require_once 'PHPUnit/Framework.php';
require 'HttpRequest.php';

class HttpRequestTest extends PHPUnit_Framework_TestCase
{

    public function testGet()
    {
	$http = HttpRequest::get("http://localhost/http/test.php");
	$this->assertInstanceOf('HttpRequest', $http);
	$this->assertEquals('GET', $http->method());
    }

    public function testPost()
    {
	$http = HttpRequest::post("http://localhost/http/test.php");
	$this->assertInstanceOf('HttpRequest', $http);
	$this->assertEquals('POST', $http->method());
    }

    public function testPut()
    {
	$http = HttpRequest::put("http://localhost/http/test.php");
	$this->assertInstanceOf('HttpRequest', $http);
	$this->assertEquals('PUT', $http->method());
    }

    public function testDelete()
    {
	$http = HttpRequest::delete("http://localhost/http/test.php");
	$this->assertInstanceOf('HttpRequest', $http);
	$this->assertEquals('DELETE', $http->method());
    }

    public function testHead()
    {
	$http = HttpRequest::head("http://localhost/http/test.php");
	$this->assertInstanceOf('HttpRequest', $http);
	$this->assertEquals('HEAD', $http->method());
    }

    public function testUrl()
    {
	$http = HttpRequest::get("http://localhost/http/test.php?oleg=2", array("get_var"	 => "23", "pole"		 => "lol"));
	$url = $http->url();
	$this->assertEquals('localhost', $url['host']);
	$this->assertEquals('/http/test.php', $url['path']);
	$this->assertEquals('oleg=2&get_var=23&pole=lol', $url['query']);
    }

    public function testHeader()
    {
	$http = HttpRequest::get("http://localhost/http/test.php");
	$this->assertInstanceOf('HttpRequest', $http->header("trololo", "123")); // устанавилваем несуществующий заогловок
	$this->assertEquals(null, $http->header("trololo")); // заголовок ответа не должен возвратится

	unset($http);
	$http = HttpRequest::get("http://localhost/http/test.php")->header("Connection", "keep-alive")->readTimeout(1);
	$this->assertEquals("keep-alive", strtolower($http->header("Connection")));
	unset($http);
    }

}
