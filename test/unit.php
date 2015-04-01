<?php

namespace Garik;

define('BASEPATH', dirname(__FILE__).DIRECTORY_SEPARATOR);
define('PATH_TMP', sys_get_temp_dir().DIRECTORY_SEPARATOR);
define('PATH_IMPL', BASEPATH.'..'.DIRECTORY_SEPARATOR.'implements'.DIRECTORY_SEPARATOR);

require_once BASEPATH.'..'.DIRECTORY_SEPARATOR.'HttpRequest.php';

class Sockets implements HttpConnectionFactory
{

    public static function create($url)
    {
	require_once PATH_IMPL.'Socket.php';
	return new SocketInterface($url);
    }

}

class CURL implements HttpConnectionFactory
{

    public static function create($url)
    {
	require_once PATH_IMPL.'CURL.php';
	return new CURLInterface($url);
    }

}

class HttpRequestTest extends \PHPUnit_Framework_TestCase
{

    const URL = "http://localhost/xakep/http/test/test.php";

    private $lastMethod;
    private static $factory;
    private $index;

    public static function setUpBeforeClass()
    {
	// добавляем возможные интерфейсы для тестирования
	self::$factory = array();
	self::$factory[] = new Sockets();
	self::$factory[] = new CURL();
    }

    protected function setUp()
    {
	$this->index = 0;
    }

    protected function tearDown()
    {
	while(++$this->index < count(self::$factory)) // тестируем все сразу со всеми возможными интерфейсами.
	{
	    call_user_func(array($this, $this->lastMethod));
	}
    }

    public function testGet()
    {
	$http = HttpRequest::get(self::URL)->setConnectionFactory(self::$factory[$this->index]);
	$this->assertInstanceOf('Garik\HttpRequest', $http);
	$this->assertEquals(HttpRequest::METHOD_GET, $http->method());

	$this->lastMethod=__METHOD__;
    }

    public function testPut()
    {
	$http = HttpRequest::put(self::URL)->setConnectionFactory(self::$factory[$this->index]);
	$this->assertInstanceOf('Garik\HttpRequest', $http);
	$this->assertEquals(HttpRequest::METHOD_PUT, $http->method());

	$this->lastMethod=__METHOD__;
    }

    public function testDelete()
    {
	$http = HttpRequest::delete(self::URL)->setConnectionFactory(self::$factory[$this->index]);
	$this->assertInstanceOf('Garik\HttpRequest', $http);
	$this->assertEquals(HttpRequest::METHOD_DELETE, $http->method());

	$this->lastMethod=__METHOD__;
    }

    public function testHead()
    {
	$http = HttpRequest::head(self::URL)->setConnectionFactory(self::$factory[$this->index]);
	$this->assertInstanceOf('Garik\HttpRequest', $http);
	$this->assertEquals(HttpRequest::METHOD_HEAD, $http->method());

	$this->lastMethod=__METHOD__;
    }

    public function testUrl()
    {
	$http = HttpRequest::get(self::URL."?oleg=2", array("get_var"	 => "23", "pole"		 => "lol"))->setConnectionFactory(self::$factory[$this->index]);
	$url = $http->url();
	$this->assertEquals('oleg=2&get_var=23&pole=lol', $url['query']);

	$this->lastMethod=__METHOD__;
    }

    public function testHeader()
    {
	$http = HttpRequest::get(self::URL)->setConnectionFactory(self::$factory[$this->index]);
	$this->assertInstanceOf('Garik\HttpRequest', $http->header("trololo", "123")); // устанавилваем несуществующий заогловок
	$this->assertEquals(null, $http->header("trololo")); // заголовок ответа не должен возвратится

	unset($http);
	$http = HttpRequest::get(self::URL)->header("Connection", "keep-alive")->readTimeout(1)->setConnectionFactory(self::$factory[$this->index]);
	$this->assertEquals("keep-alive", strtolower($http->header("Connection")));
	unset($http);

	$this->lastMethod=__METHOD__;
    }

    /**
     * Проверяем передачу POST переменных, GET переменных в URL и файла.
     */
    public function testPost()
    {
	$image = PATH_TMP.'test_img.jpg';
	$image_upload = BASEPATH."img.jpg";

	// передача multipart/form-data
	$http = HttpRequest::post(self::URL, array("post" => 1))->form(array("param1" => "value", "param2" => "@".$image_upload))->setConnectionFactory(self::$factory[$this->index]);
	$this->assertInstanceOf('Garik\HttpRequest', $http);
	$this->assertEquals('POST', $http->method());
	$this->assertEquals('param1=value', $http->body());
	$this->assertFileExists($image);
	$this->assertFileEquals($image_upload, $image);

	unset($http);

	// проверяем передачу application/x-www-form-urlencoded
	$http = HttpRequest::post(self::URL."?post=1")->form("key=value&param=test")->setConnectionFactory(self::$factory[$this->index]);
	$this->assertEquals('key=value&param=test', $http->body());

	$this->lastMethod=__METHOD__;
    }

    public function testReceive() // тестируем получение данных в файл
    {
	$file = fopen(PATH_TMP.'test_file.txt', 'wb');
	if ($file)
	{
	    $http = HttpRequest::get(self::URL)->receive($file)->setConnectionFactory(self::$factory[$this->index]);
	    $this->assertTrue($http->ok());
	    $this->assertEmpty($http->body()); // тело ответа должно быть пустым
	    fclose($file);
	}

	$this->lastMethod=__METHOD__;
    }

    public function testReceive2()
    {
	static $j = 0;
	$count = 20;

	$file_original = BASEPATH."file.txt";
	for($i=0;$i<$count;$i++)
	{
	    $file_downloaded = PATH_TMP.'test_file_'.$i.'.txt';
	    $file = fopen($file_downloaded, 'wb');
	    if($file)
	    {
		$http = HttpRequest::get("http://localhost/xakep/http/test/file.txt")->receive($file)->setConnectionFactory(self::$factory[$this->index]);
		$this->assertTrue($http->ok());
		$this->assertFileEquals($file_original, $file_downloaded);
		fclose($file);
		unlink($file_downloaded);
		//usleep(1500);
	    }
	    echo $j++.PHP_EOL;

	}

	$this->lastMethod=__METHOD__;
    }

    public function testUpload()
    {
	$image_upload = BASEPATH."img.jpg";
	$http = HttpRequest::put(self::URL."?put=1")->upload($image_upload)->setConnectionFactory(self::$factory[$this->index]);
	$this->assertTrue($http->ok());
	$this->assertFileEquals($image_upload, $http->body()); // в результате вернется путь до файла куда записалось все.

	$this->lastMethod=__METHOD__;
    }

    public function testfollowRedirects()
    {
	$http = HttpRequest::get("http://google.com/?test=get")->setConnectionFactory(self::$factory[$this->index]);
	if(HttpRequest::HTTP_MOVED_PERM == $http->code() || HttpRequest::HTTP_MOVED_TEMP == $http->code())
	{
	    $http = HttpRequest::get("http://google.com/?test=get")->followRedirects(true)->setConnectionFactory(self::$factory[$this->index]);
	    $this->assertTrue($http->ok());
	}

	$this->lastMethod=__METHOD__;
    }

}
