<?php

/**
 * Попытка сделать клон библиотеки Http Request https://github.com/kevinsawicki/http-request
 *
 * @author Gar|k
 * @version 0.1
 */
class HttpRequest
{

    const METHOD_GET = "GET";
    const METHOD_POST = "POST";
    const METHOD_PUT = "PUT";
    const METHOD_DELETE = "DELETE";
    const METHOD_HEAD = "HEAD";
    const CONTENT_TYPE_JSON = "application/json";

    /**
     * 'Accept' header name
     */
    const HEADER_ACCEPT = "Accept";

    /**
     * 'Accept-Charset' header name
     */
    const HEADER_ACCEPT_CHARSET = "Accept-Charset";

    /**
     * 'Accept-Encoding' header name
     */
    const HEADER_ACCEPT_ENCODING = "Accept-Encoding";

    /**
     * 'Cache-Control' header name
     */
    const HEADER_CACHE_CONTROL = "Cache-Control";

    /**
     * 'Content-Encoding' header name
     */
    const HEADER_CONTENT_ENCODING = "Content-Encoding";

    /**
     * 'Content-Length' header name
     */
    const HEADER_CONTENT_LENGTH = "Content-Length";

    /**
     * 'Content-Type' header name
     */
    const HEADER_CONTENT_TYPE = "Content-Type";

    /**
     * 'Date' header name
     */
    const HEADER_DATE = "Date";

    /**
     * 'Expires' header name
     */
    const HEADER_EXPIRES = "Expires";

    /**
     * 'Last-Modified' header name
     */
    const HEADER_LAST_MODIFIED = "Last-Modified";

    /**
     * 'Location' header name
     */
    const HEADER_LOCATION = "Location";

    /**
     * 'Referer' header name
     */
    const HEADER_REFERER = "Referer";

    /**
     * 'Server' header name
     */
    const HEADER_SERVER = "Server";

    /**
     * 'User-Agent' header name
     */
    const HEADER_USER_AGENT = "User-Agent";

    /**
     * Numeric status code, 200: OK
     */
    const HTTP_OK = 200;

    /**
     * Numeric status code, 201: Created
     */
    const HTTP_CREATED = 201;

    /**
     * Numeric status code, 500: Internal error
     */
    const HTTP_INTERNAL_ERROR = 500;

    /**
     * Numeric status code, 400: Bad Request
     */
    const HTTP_BAD_REQUEST = 400;

    /**
     * Numeric status code, 404: Not found
     */
    const HTTP_NOT_FOUND = 404;

    /**
     * Numeric status code, 304: Not modified
     */
    const HTTP_NOT_MODIFIED = 304;

    private $url;
    private $requestMethod;

    /**
     * @var \HttpConnectionFactory
     */
    private $connectionFactory;

    /**
     * @var \HttpURLConnection
     */
    private $connection;

    function __construct($url, $method, $params = null)
    {

	if ($params)
	    $url = $this->append($url, $params);

	$this->url = parse_url($url);
	$this->requestMethod = $method;

	$this->setConnectionFactory();
    }

    public function setConnectionFactory(HttpConnectionFactory $connectionFactory = null)
    {
	if ($connectionFactory == null)
	{
	    $this->connectionFactory = new DEFAULT_FACTORY();
	}
	else
	{
	    $this->connectionFactory = $connectionFactory;
	}
    }

    /**
     * Is the response code a 200 OK?
     *
     * @return true if 200, false otherwise
     */
    public function ok()
    {
	return HttpRequest::HTTP_OK == $this->code();
    }

    /**
     * Is the response code a 201 Created?
     *
     * @return true if 201, false otherwise
     */
    public function created()
    {
	return HttpRequest::HTTP_CREATED == $this->code();
    }

    /**
     * Is the response code a 500 Internal Server Error?
     *
     * @return true if 500, false otherwise
     */
    public function serverError()
    {
	return HttpRequest::HTTP_INTERNAL_ERROR == $this->code();
    }

    /**
     * Is the response code a 400 Bad Request?
     *
     * @return true if 400, false otherwise
     */
    public function badRequest()
    {
	return HttpRequest::HTTP_BAD_REQUEST == $this->code();
    }

    /**
     * Is the response code a 404 Not Found?
     *
     * @return true if 404, false otherwise
     */
    public function notFound()
    {
	return HttpRequest::HTTP_NOT_FOUND == $this->code();
    }

    /**
     * Is the response code a 304 Not Modified?
     *
     * @return true if 304, false otherwise
     */
    public function notModified()
    {
	return HttpRequest::HTTP_NOT_MODIFIED == $this->code();
    }

    /**
     * Принять данные в файл
     *
     * @param stream|file $file
     * @return \HttpRequest
     */
    public function receive($file)
    {
	if (!is_resource($file))
	    throw new HttpRequestException('Это не ссылка на файл');
	$this->getConnection()->setReceiveFile($file);
	return $this;
    }

    public function upload($fileName)
    {
	if (!file_exists($fileName))
	    throw new HttpRequestException('Файла не существует');
	$this->getConnection()->setUploadFile($fileName);

	return $this;
    }

    /**
     * Get status message of the response
     *
     * @return string message
     */
    public function message()
    {
	return $this->getConnection()->getResponseMessage();
    }

    /**
     *
     * @param string $url
     * @param array|object $params
     * @return string
     */
    private function append($url, $params)
    {
	return $url.(strpos($url, '?') === false ? '?' : '&').http_build_query($params);
    }

    /**
     *
     * @param string $url
     * @param array|object $params
     * @return \HttpRequest
     */
    static public function put($url, $params = null)
    {
	return new HttpRequest($url, HttpRequest::METHOD_PUT, $params);
    }

    /**
     *
     * @param string $url
     * @param array|object $params
     * @return \HttpRequest
     */
    static public function delete($url, $params = null)
    {
	return new HttpRequest($url, HttpRequest::METHOD_DELETE, $params);
    }

    /**
     *
     * @param string $url
     * @param array|object $params
     * @return \HttpRequest
     */
    static public function head($url, $params = null)
    {
	return new HttpRequest($url, HttpRequest::METHOD_HEAD, $params);
    }

    /**
     *
     * @param string $url
     * @param object/Array $params
     * @return \HttpRequest
     */
    static public function get($url, $params = null)
    {
	return new HttpRequest($url, HttpRequest::METHOD_GET, $params);
    }

    static public function post($url, $params = null)
    {
	return new HttpRequest($url, HttpRequest::METHOD_POST, $params);
    }

    public function url()
    {
	return $this->url;
    }

    public function connectTimeout($timeout)
    {
	$this->getConnection()->setConnectTimeout($timeout);
	return $this;
    }

    /**
     * Set read timeout on connection to given value
     *
     * @param timeout
     * @return this request
     */
    public function readTimeout($timeout)
    {
	$this->getConnection()->setReadTimeout($timeout);
	return $this;
    }

    /**
     * Eсли нужно передать файл, достаточно указать file_field => @/path/to/file/img.png
     *
     * @param Array|String $fields
     * @return \HttpRequest
     */
    public function form($fields)
    {
	$this->getConnection()->setPostFields($fields);
	return $this;
    }

    /**
     * Устанавилвает заголовки запроса к серверу, либо возвращает заголовок ответа сервера по имени.
     *
     * @param type $name имя поля заголовка
     * @param type $value новое значение
     * @return \HttpRequest если $value было передано или string значение заголовка ответа сервера
     */
    public function header($name, $value = null)
    {
	if ($value != null)
	{
	    $this->getConnection()->setRequestProperty($name, $value);
	    return $this;
	}

	return $this->getConnection()->getHeaderField($name);
    }

    /**
     * Устанавливает заголовки запроса к серверу, либо возвращает заголовки ответа сервера
     *
     * @param array $headers
     * @return \HttpRequest  либо array
     */
    public function headers(Array $headers = null)
    {
	if ($headers == null)
	{
	    return $this->getConnection()->getHeaderFields();
	}

	foreach ($headers as $name => $value)
	{
	    $this->header($name, $value);
	}
	return $this;
    }

    public function accept($accept)
    {
	return $this->header(HttpRequest::HEADER_ACCEPT, $accept);
    }

    public function contentType($contentType = null)
    {
	return $this->header(HttpRequest::HEADER_CONTENT_TYPE, $contentType);
    }

    public function acceptJson()
    {
	return $this->accept(HttpRequest::CONTENT_TYPE_JSON);
    }

    public function code()
    {
	return $this->getConnection()->getResponseCode();
    }

    public function body()
    {
	return $this->getConnection()->getResponse();
    }

    public function contentLength($contentLength = null)
    {
	return $this->header(HttpRequest::HEADER_CONTENT_LENGTH, $contentLength);
    }

    public function userAgent($userAgent = null)
    {
	return $this->header(HttpRequest::HEADER_USER_AGENT, $userAgent);
    }

    public function referer($referer = null)
    {
	return $this->header(HttpRequest::HEADER_REFERER, $referer);
    }

    public function acceptEncoding($acceptEncoding = null)
    {
	return $this->header(HttpRequest::HEADER_ACCEPT_ENCODING, $acceptEncoding);
    }

    public function acceptCharset($acceptCharset = null)
    {
	return $this->header(HttpRequest::HEADER_ACCEPT_CHARSET, $acceptCharset);
    }

    public function contentEncoding()
    {
	return $this->header(HttpRequest::HEADER_CONTENT_ENCODING);
    }

    /**
     * Get the 'Server' header from the response
     *
     * @return server
     */
    public function server()
    {
	return $this->header(HttpRequest::HEADER_SERVER);
    }

    /**
     * Get the 'Date' header from the response
     *
     * @return date value, -1 on failures
     */
    public function date()
    {
	return $this->header(HttpRequest::HEADER_DATE);
    }

    /**
     * Get the 'Cache-Control' header from the response
     *
     * @return cache control
     */
    public function cacheControl()
    {
	return $this->header(HttpRequest::HEADER_CACHE_CONTROL);
    }

    public function expires()
    {
	return $this->header(HttpRequest::HEADER_EXPIRES);
    }

    /**
     * Get the 'Last-Modified' header from the response
     *
     * @return last modified value, -1 on failures
     */
    public function lastModified()
    {
	return $this->header(HttpRequest::HEADER_LAST_MODIFIED);
    }

    public function getConnection()
    {
	if ($this->connection == null)
	    $this->connection = $this->createConnection();
	return $this->connection;
    }

    private function createConnection()
    {
	$this->connection = call_user_func(array($this->connectionFactory, 'create'), $this->url);
	$this->connection->setRequestMethod($this->requestMethod);

	return $this->connection;
    }

    /**
     *
     * @param boolean $followRedirects
     * @return \HttpRequest
     */
    public function followRedirects($followRedirects)
    {
	$this->getConnection()->setFollowRedirects($followRedirects);
	return $this;
    }

    /**
     * Get the HTTP method of this request
     *
     * @return string
     */
    public function method()
    {
	return $this->getConnection()->getRequestMethod();
    }

}

interface HttpConnectionFactory
{

    public static function create($url);
}

class DEFAULT_FACTORY implements HttpConnectionFactory
{

    public static function create($url)
    {
	$basepath = dirname(__FILE__).DIRECTORY_SEPARATOR.'implements'.DIRECTORY_SEPARATOR;

	if (function_exists('fsockopen') && file_exists($basepath.'Socket.php'))
	{
	    require_once $basepath.'Socket.php';
	    return new SocketInterface($url);
	}
	if (extension_loaded('curl') && file_exists($basepath.'CURL.php'))
	{
	    require_once $basepath.'CURL.php';
	    return new CURLInterface($url);
	}
    }

}

class HttpRequestException extends Exception
{

}

/**
 * http://developer.android.com/reference/java/net/HttpURLConnection.html
 */
interface HttpURLConnection
{

    function __construct(Array $url);

    /**
     * Returns the request method which will be used to make the request to the remote HTTP server.
     * @return string the request method string.
     */
    public function getRequestMethod();

    /**
     * Sets the request command which will be sent to the remote HTTP server.
     *
     * @param string $method the string representing the method to be used.
     */
    public function setRequestMethod($method);

    /**
     * Sets the value of the specified request header field.
     *
     * @param string $name the request header field to be set.
     * @param string $value the new value of the specified property.
     */
    public function setRequestProperty($name, $value);

    /**
     * Returns the response code returned by the remote HTTP server.
     * @return int the response code.
     */
    public function getResponseCode();

    /**
     * Returns the response body returned by the remote HTTP server.
     * @return int the response body.
     */
    public function getResponse();

    /**
     * Returns the value of the header field specified by name or null if there is no field with this name.
     *
     * @param string $name the name of the header field.
     * @return string the value of the header field.
     */
    public function getHeaderField($name);

    /**
     * Returns response-header fields and values.
     * @return Array
     */
    public function getHeaderFields();

    /**
     * Returns the response message returned by the remote HTTP server.
     * @return string the response message.
     */
    public function getResponseMessage();

    public function setPostFields($data);

    public function setReceiveFile($file);

    public function setUploadFile($fileName);

    public function setFollowRedirects($followRedirects);

    /**
     * Sets the maximum time in seconds to wait while connecting.
     *
     * @param int $timeout
     */
    public function setConnectTimeout($timeout);

    /**
     * Returns the connect timeout in seconds. (See {@link #setConnectTimeout})
     * @return int
     */
    public function getConnectTimeout();

    public function getReadTimeout();

    public function setReadTimeout($timeout);
}

/*ini_set('display_errors', 1);
error_reporting(E_ALL | E_STRICT);
try {
    $get=HttpRequest::get("http://localhost/http/test.php");
    print_r($get->headers());
} catch (HttpRequestException $e) {
    exit($e->getMessage());
}*/