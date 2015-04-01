<?php

/**
 * @author Gar|k <garik.djan@gmail.com>
 * @copyright (c) 2013, http://c0dedgarik.blogspot.ru/
 * @version 0.1
 *
 * Реализация интерфейса библиотеки Http Request на PHP
 * https://github.com/kevinsawicki/http-request
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to
 * deal in the Software without restriction, including without limitation the
 * rights to use, copy, modify, merge, publish, distribute, sublicense, and/or
 * sell copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING
 * FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS
 * IN THE SOFTWARE.
 *
 */

namespace Garik;


class HttpRequest
{
    /**
     * 'GET' request method
     */
    const METHOD_GET = "GET";

    /**
     * 'POST' request method
     */
    const METHOD_POST = "POST";

    /**
     * 'PUT' request method
     */
    const METHOD_PUT = "PUT";

    /**
     * 'DELETE' request method
     */
    const METHOD_DELETE = "DELETE";

    /**
     * 'HEAD' request method
     */
    const METHOD_HEAD = "HEAD";

    /**
     * 'application/json' content type header value
     */
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
     * 'Connection' header name
     */
    const HEADER_CONNECTION = "Connection";

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

    /**
     * Numeric status code, 301 Moved permanently
     */
    const HTTP_MOVED_PERM = 301;

    /**
     * Numeric status code, 302 Moved Temporarily
     */
    const HTTP_MOVED_TEMP = 302;

    private $url;
    private $requestMethod;

    /**
     * @var \Garik\HttpConnectionFactory
     */
    private $connectionFactory;

    /**
     * @var \Garik\HttpURLConnection
     */
    private $connection;

    /**
     * @param string $url
     * @param string $method
     * @param array|object $params
     */
    function __construct($url, $method, $params = null)
    {
        if ($params)
            $url = $this->append($url, $params);

        $this->url = parse_url($url);
        $this->requestMethod = (string) $method;
        $this->setConnectionFactory();
    }

    /**
     * Specify the {@link ConnectionFactory} used to create new requests.
     *
     * @param HttpConnectionFactory $connectionFactory
     * @return HttpRequest
     */
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

        return $this;
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
     * Receive data in a file
     *
     * @param resource $file stream or file
     * @return HttpRequest
     * @throws HttpRequestException
     */
    public function receive($file)
    {
        if (!is_resource($file))
            throw new HttpRequestException('Это не ссылка на файл');
        $this->getConnection()->setReceiveFile($file);
        return $this;
    }

    /**
     * Upload a file to the server using PUT
     *
     * @param string $fileName
     * @return HttpRequest
     * @throws HttpRequestException
     */
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
     * Appends the parameters of the object/array $params to the main URL
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
     * Start a 'PUT' request to the given URL
     *
     * @param string $url
     * @param array|object $params
     * @return HttpRequest
     */
    static public function put($url, $params = null)
    {
        return new HttpRequest($url, HttpRequest::METHOD_PUT, $params);
    }

    /**
     * Start a 'DELETE' request to the given URL
     *
     * @param string $url
     * @param array|object $params
     * @return HttpRequest
     */
    static public function delete($url, $params = null)
    {
        return new HttpRequest($url, HttpRequest::METHOD_DELETE, $params);
    }

    /**
     * Start a 'HEAD' request to the given URL
     *
     * @param string $url
     * @param array|object $params
     * @return HttpRequest
     */
    static public function head($url, $params = null)
    {
        return new HttpRequest($url, HttpRequest::METHOD_HEAD, $params);
    }

    /**
     * Start a 'GET' request to the given URL
     *
     * @param string $url
     * @param object/Array $params
     * @return HttpRequest
     */
    static public function get($url, $params = null)
    {
        return new HttpRequest($url, HttpRequest::METHOD_GET, $params);
    }

    /**
     * Start a 'POST' request to the given URL
     *
     * @param string $url
     * @param object/Array $params
     * @return HttpRequest
     */
    static public function post($url, $params = null)
    {
        return new HttpRequest($url, HttpRequest::METHOD_POST, $params);
    }

    /**
     * Get the {@link URL} of this request's connection
     *
     * @return array request URL
     */
    public function url()
    {
        return $this->url;
    }

    /**
     * Set connect timeout on connection to given value
     *
     * @param float $timeout
     * @return HttpRequest
     */
    public function connectTimeout($timeout)
    {
        $this->getConnection()->setConnectTimeout($timeout);
        return $this;
    }

    /**
     * Set read timeout on connection to given value
     *
     * @param timeout
     * @return HttpRequest
     */
    public function readTimeout($timeout)
    {
        $this->getConnection()->setReadTimeout($timeout);
        return $this;
    }

    /**
     * If you want to transfer a file,
     * you need to specify the "file_field" => "@/path/to/file/img.png"
     *
     * @param Array|String $fields
     * @return HttpRequest
     */
    public function form($fields)
    {
        $this->getConnection()->setPostFields($fields);
        return $this;
    }

    /**
     * Устанавилвает заголовки запроса к серверу, либо возвращает заголовок ответа сервера по имени.
     *
     * @param string $name имя поля заголовка
     * @param string $value новое значение
     * @return HttpRequest если $value было передано или string значение заголовка ответа сервера
     */
    public function header($name, $value = null)
    {
        if ($value != null)
        {
            $this->getConnection()->setRequestProperty((string) $name, (string) $value);
            return $this;
        }

        return $this->getConnection()->getHeaderField($name);
    }

    /**
     * Устанавливает заголовки запроса к серверу, либо возвращает заголовки ответа сервера
     *
     * @param array $headers
     * @return HttpRequest  либо array
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

    /**
     * Set the 'Accept' header to given value
     * or get header from the response
     *
     * @param string $accept
     * @return HttpRequest
     */
    public function accept($accept = null)
    {
        return $this->header(HttpRequest::HEADER_ACCEPT, $accept);
    }

    /**
     * Set the 'Content-Type' request header to the given value
     * or get header from the response
     *
     * @param string $contentType
     * @return HttpRequest
     */
    public function contentType($contentType = null)
    {
        return $this->header(HttpRequest::HEADER_CONTENT_TYPE, $contentType);
    }

    /**
     * Set the 'Accept' header to 'application/json'
     *
     * @return HttpRequest
     */
    public function acceptJson()
    {
        return $this->accept(HttpRequest::CONTENT_TYPE_JSON);
    }

    /**
     * Get the status code of the response
     *
     * @return integer
     */
    public function code()
    {
        return (integer) $this->getConnection()->getResponseCode();
    }

    /**
     * Get response as {@link String}
     *
     * @return string response, null on empty
     */
    public function body()
    {
        return $this->getConnection()->getResponse();
    }

    /**
     * Set the 'Content-Length' request header to the given value
     * or get header from the response
     *
     * @param integer $contentLength
     * @return HttpRequest
     */
    public function contentLength($contentLength = null)
    {
        return $this->header(HttpRequest::HEADER_CONTENT_LENGTH, (integer) $contentLength);
    }

    /**
     * Set the 'User-Agent' header to given value
     * or get header from the response
     *
     * @param string $userAgent
     * @return HttpRequest
     */
    public function userAgent($userAgent = null)
    {
        return $this->header(HttpRequest::HEADER_USER_AGENT, $userAgent);
    }

    /**
     * Set the 'Referer' header to given value
     * or get header from the response
     *
     * @param string $referrer
     * @return HttpRequest
     */
    public function referer($referrer = null)
    {
        return $this->header(HttpRequest::HEADER_REFERER, $referrer);
    }

    /**
     * Set the 'Accept-Encoding' header to given value
     * or get header from the response
     *
     * @param string|null $acceptEncoding
     * @return HttpRequest
     * @internal param string $referer
     */
    public function acceptEncoding($acceptEncoding = null)
    {
        return $this->header(HttpRequest::HEADER_ACCEPT_ENCODING, $acceptEncoding);
    }

    /**
     * Set the 'Accept-Charset' header to given value
     * or get header from the response
     *
     * @param string|null $acceptCharset
     * @return HttpRequest
     */
    public function acceptCharset($acceptCharset = null)
    {
        return $this->header(HttpRequest::HEADER_ACCEPT_CHARSET, $acceptCharset);
    }

    /**
     * Get the 'Content-Encoding' header from the response
     *
     * @return HttpRequest
     */
    public function contentEncoding()
    {
        return $this->header(HttpRequest::HEADER_CONTENT_ENCODING);
    }

    /**
     * Get the 'Server' header from the response
     *
     * @return string server
     */
    public function server()
    {
        return $this->header(HttpRequest::HEADER_SERVER);
    }

    /**
     * Get the 'Date' header from the response
     *
     * @return string date value
     */
    public function date()
    {
        return $this->header(HttpRequest::HEADER_DATE);
    }

    /**
     * Get the 'Cache-Control' header from the response
     *
     * @return string cache control
     */
    public function cacheControl()
    {
        return $this->header(HttpRequest::HEADER_CACHE_CONTROL);
    }

    /**
     * Get the 'Expires' header from the response
     *
     * @return string expires value
     */
    public function expires()
    {
        return $this->header(HttpRequest::HEADER_EXPIRES);
    }

    /**
     * Get the 'Last-Modified' header from the response
     *
     * @return string last modified value
     */
    public function lastModified()
    {
        return $this->header(HttpRequest::HEADER_LAST_MODIFIED);
    }

    /**
     * Get underlying connection
     *
     * @return HttpURLConnection
     */
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
     * Set whether or not the underlying connection should follow redirects in
     * the response.
     *
     * @param boolean $followRedirects
     * @return HttpRequest
     */
    public function followRedirects($followRedirects)
    {
        $this->getConnection()->setFollowRedirects((boolean) $followRedirects);
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

        // предпочтение отдается библиотеке cURL
        if (extension_loaded('curl') && file_exists($basepath.'CURL.php'))
        {
            require_once $basepath.'CURL.php';
            return new CURLInterface($url);
        }

        if (function_exists('fsockopen') && file_exists($basepath.'Socket.php'))
        {
            require_once $basepath.'Socket.php';
            return new SocketInterface($url);
        }

        throw new HttpRequestException('Подключите PHP-библиотеку cURL или sockets.');
    }

}

class HttpRequestException extends \Exception
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

    /**
     * Все данные, передаваемые в HTTP POST-запросе.
     * Для передачи файла, укажите перед именем файла @, а также используйте полный путь к файлу.
     * При передаче файлов с префиксом @, $data должен быть массивом
     * Если $data является массивом, заголовок Content-Type будет установлен в значение multipart/form-data
     * @param Array|String $data
     */
    public function setPostFields($data);

    /**
     * Записать ответ сервера в файл
     * @param resource $file stream or file
     */
    public function setReceiveFile($file);

    /**
     * Установить файл для загрузки методом PUT
     * @param string $fileName
     */
    public function setUploadFile($fileName);

    /**
     *
     * @param boolean $followRedirects
     */
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

    /**
     * Returns the read timeout in seconds, or 0 if reads never timeout.
     * @return int
     */
    public function getReadTimeout();

    /**
     * Sets the maximum time to wait for an input stream read to complete before
     * giving up.
     *
     * @param int $timeout in seconds
     */
    public function setReadTimeout($timeout);
}
