<?php

require 'HttpRequest.php';

class HttpRequestTest extends PHPUnit_Framework_TestCase
{
    const URL="http://localhost/http/test/test.php";

    public function testGet()
    {
	$http = HttpRequest::get(self::URL);
	$this->assertInstanceOf('HttpRequest', $http);
	$this->assertEquals('GET', $http->method());
    }

    public function testPut()
    {
	$http = HttpRequest::put(self::URL);
	$this->assertInstanceOf('HttpRequest', $http);
	$this->assertEquals('PUT', $http->method());
    }

    public function testDelete()
    {
	$http = HttpRequest::delete(self::URL);
	$this->assertInstanceOf('HttpRequest', $http);
	$this->assertEquals('DELETE', $http->method());
    }

    public function testHead()
    {
	$http = HttpRequest::head(self::URL);
	$this->assertInstanceOf('HttpRequest', $http);
	$this->assertEquals('HEAD', $http->method());
    }

    public function testUrl()
    {
	$http = HttpRequest::get(self::URL."?oleg=2", array("get_var"	 => "23", "pole"		 => "lol"));
	$url = $http->url();
	$this->assertEquals('localhost', $url['host']);
	$this->assertEquals('/http/test.php', $url['path']);
	$this->assertEquals('oleg=2&get_var=23&pole=lol', $url['query']);
    }

    public function testHeader()
    {
	$http = HttpRequest::get(self::URL);
	$this->assertInstanceOf('HttpRequest', $http->header("trololo", "123")); // устанавилваем несуществующий заогловок
	$this->assertEquals(null, $http->header("trololo")); // заголовок ответа не должен возвратится

	unset($http);
	$http = HttpRequest::get(self::URL)->header("Connection", "keep-alive")->readTimeout(1);
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
	$http = HttpRequest::post(self::URL, array("post" => 1))->form(array("param1" => "value", "param2" => "@/var/www/http/img.jpg"));
	$this->assertInstanceOf('HttpRequest', $http);
	$this->assertEquals('POST', $http->method());
	$this->assertEquals('param1=value', $http->body());
	$this->assertFileExists($image);
	$this->assertFileEquals('/var/www/http/img.jpg', $image);

	unset($http);

	// проверяем передачу application/x-www-form-urlencoded
	$http = HttpRequest::post(self::URL."?post=1")->form("key=value&param=test");
	$this->assertEquals('key=value&param=test', $http->body());
    }

    public function testReceive() // тестируем получение данных в файл
    {
	$file = fopen(sys_get_temp_dir().DIRECTORY_SEPARATOR.'test_file.txt', 'wb');
	if ($file)
	{
	    $http = HttpRequest::get(self::URL)->receive($file);
	    $this->assertTrue($http->ok());
	    $this->assertEmpty($http->body()); // тело ответа должно быть пустым
	    fclose($file);
	}
    }

    public function testUpload()
    {
	$http = HttpRequest::put(self::URL."?put=1")->upload('/var/www/http/img.jpg');
	$this->assertTrue($http->ok());
	$this->assertFileEquals('/var/www/http/img.jpg', $http->body()); // в результате вернется путь до файла куда записалось все.
    }

    public function testfollowRedirects()
    {
	$http = HttpRequest::get("http://google.com/?test=get");
	$this->assertEquals(HttpRequest::HTTP_MOVED_PERM, $http->code());

	$http = HttpRequest::get("http://google.com/?test=get")->followRedirects(true);
	$this->assertTrue($http->ok());
    }

}
