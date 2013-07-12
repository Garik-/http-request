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
    const CONTENT_TYPE_JSON = "application/json";
    const HEADER_ACCEPT = "Accept";
    const HEADER_CONTENT_TYPE = "Content-Type";
    const HTTP_OK = 200;
    const HTTP_CREATED = 201;
    const HTTP_INTERNAL_ERROR = 500;
    const HTTP_BAD_REQUEST = 400;
    const HTTP_NOT_FOUND = 404;
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

    function __construct($url, $method)
    {
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

    public function created()
    {
	return HttpRequest::HTTP_CREATED == $this->code();
    }

    public function serverError()
    {
	return HttpRequest::HTTP_INTERNAL_ERROR == $this->code();
    }

    public function badRequest()
    {
	return HttpRequest::HTTP_BAD_REQUEST == $this->code();
    }

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
     * @param object/Array $params
     * @return \HttpRequest
     */
    static public function get($url, $params = null)
    {
	if ($params != null)
	{
	    $url.='?'.http_build_query($params);
	}
	return new HttpRequest($url, HttpRequest::METHOD_GET);
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
}

interface HttpConnectionFactory
{

    public static function create($url);
}

class DEFAULT_FACTORY implements HttpConnectionFactory
{

    public static function create($url)
    {
	return new CURLInterface($url);
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
}

class CURLInterface implements HttpURLConnection
{

    private $curl;
    private $method;
    private $headers;
    private $options;
    private $response;
    private $response_headers;
    private $response_message;

    function __construct(Array $url)
    {
	$this->curl = curl_init();
	if (!$this->curl)
	    $this->exeption();

	$this->options = array(CURLOPT_RETURNTRANSFER	 => true,
	    CURLOPT_FOLLOWLOCATION	 => true,
	    CURLOPT_HEADER		 => false,
	    CURLOPT_HEADERFUNCTION	 => array($this, 'setHeaderFields'));

	if (array_key_exists('port', $url))
	{
	    $this->options[CURLOPT_PORT] = $url['port'];
	}

	$this->options[CURLOPT_URL] = $url['scheme'].'://'.$url['host'].(!empty($url['path']) ? $url['path'] : '/').(!empty($url['query']) ? '?'.$url['query'] : '');
    }

    public function getRequestMethod()
    {
	return $this->method;
    }

    public function setRequestMethod($method)
    {
	$this->method = $method;

	switch ($this->method)
	{
	    case HttpRequest::METHOD_POST:
		$this->options[CURLOPT_POST] = true;
		break;
	    default:
		$this->options[CURLOPT_HTTPGET] = true;
	}
    }

    public function getResponse()
    {
	if ($this->response == null)
	{
	    $this->setOptions();
	    $this->response = curl_exec($this->curl);
	}

	if ($this->response === false)
	    $this->exeption();

	return $this->response;
    }

    public function getResponseMessage()
    {
	if ($this->response_message == null)
	    $this->getResponse();
	return $this->response_message;
    }

    public function getResponseCode()
    {
	if ($this->response == null)
	    $this->getResponse();

	return curl_getinfo($this->curl, CURLINFO_HTTP_CODE);
    }

    public function setRequestProperty($name, $value)
    {
	$this->headers[$name] = $value;
    }

    private function setOptions()
    {
	if (!empty($this->headers))
	{
	    $headers = array();
	    foreach ($this->headers as $field => $value)
	    {
		$headers[] = $field.": ".$value;
	    }
	    $this->options[CURLOPT_HTTPHEADER] = $headers;
	}

	return curl_setopt_array($this->curl, $this->options);
    }

    public function getHeaderField($name)
    {
	if (empty($this->response_headers))
	    $this->getResponse();

	if (!array_key_exists($name, $this->response_headers))
	    return null;
	return $this->response_headers[$name];
    }

    public function getHeaderFields()
    {
	if (empty($this->response_headers))
	    $this->getResponse();
	return $this->response_headers;
    }

    public function setHeaderFields($curl, $header)
    {
	$pos = strpos($header, ':');
	if ($pos !== false)
	{
	    $this->response_headers[substr($header, 0, $pos++)] = trim(substr($header, $pos));
	}
	elseif (preg_match("/HTTP\/1\.\d\s\d+\s(.*)/", $header, $matches))
	{
	    $this->response_message = trim($matches[1]);
	}
	return strlen($header);
    }

    private function exeption()
    {
	throw new HttpRequestException(curl_error($this->curl), curl_errno($this->curl));
    }

    function __destruct()
    {
	curl_close($this->curl);
    }

}



// ------------------------------------------------------------------------
ini_set('display_errors', 1);
error_reporting(E_ALL | E_STRICT);
try
{
    $response = HttpRequest::get("http://www.google.com")->message();
    var_dump($response);
} catch (HttpRequestException $e)
{
    exit($e->getMessage());
}