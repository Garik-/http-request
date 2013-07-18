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

    /**
     * Проверяем передачу POST переменных, GET переменных в URL и файла.
     */
    public function testPost()
    {
	$image = sys_get_temp_dir().DIRECTORY_SEPARATOR.'test_img.jpg';

	// передача multipart/form-data
	$http = HttpRequest::post("http://localhost/http/test.php", array("post" => 1))->form(array("param1" => "value", "param2" => "@/var/www/http/img.jpg"));
	$this->assertInstanceOf('HttpRequest', $http);
	$this->assertEquals('POST', $http->method());
	$this->assertEquals('param1=value', $http->body());
	$this->assertFileExists($image);
	$this->assertFileEquals('/var/www/http/img.jpg', $image);

	unset($http);

	// проверяем передачу application/x-www-form-urlencoded
	$http = HttpRequest::post("http://localhost/http/test.php?post=1")->form("key=value&param=test");
	$this->assertEquals('key=value&param=test', $http->body());
    }

    public function testReceive() // тестируем получение данных в файл
    {
	$file = fopen(sys_get_temp_dir().DIRECTORY_SEPARATOR.'test_file.txt', 'wb');
	if ($file)
	{
	    $http = HttpRequest::get("http://localhost/http/test.php")->receive($file);
	    $this->assertTrue($http->ok());
	    $this->assertEmpty($http->body()); // тело ответа должно быть пустым
	    fclose($file);
	}
    }

    public function testUpload()
    {
	$http = HttpRequest::put("http://localhost/http/test.php?put=1")->upload('/var/www/http/img.jpg');
	$this->assertTrue($http->ok());
	$this->assertFileEquals('/var/www/http/img.jpg', $http->body()); // в результате вернется путь до файла куда записалось все.
    }

}
