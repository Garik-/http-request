<?php
/**
 * @author Gar|k <garik.djan@gmail.com>
 * @copyright (c) 2013, http://c0dedgarik.blogspot.ru/
 * @version 0.1
 */

// TODO: нужно проверять версию CURL не все поля поддерживаются.
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
	    CURLOPT_HEADER		 => false,
	    CURLOPT_HEADERFUNCTION	 => array($this, 'setHeaderFields'),
	    CURLOPT_ENCODING	 => "",
	    CURLOPT_NOPROGRESS	 => true);

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

    public function setUploadFile($fileName)
    {
	$this->options[CURLOPT_UPLOAD] = true;
	$this->options[CURLOPT_INFILE] = fopen($fileName, "rb"); // TODO: проверку сделать
	$this->options[CURLOPT_INFILESIZE] = filesize($fileName);
    }

    public function setPostFields($data)
    {
	$this->options[CURLOPT_POSTFIELDS] = $data;
    }

    public function setRequestMethod($method)
    {
	$this->method = $method;

	switch ($this->method)
	{
	    case HttpRequest::METHOD_POST:
		$this->options[CURLOPT_POST] = true;
		$this->setPostFields(null);
		break;
	    case HttpRequest::METHOD_PUT:
		$this->options[CURLOPT_PUT] = true;
		break;
	    case HttpRequest::METHOD_HEAD:
		$this->options[CURLOPT_NOBODY] = true;
		break;

	    case HttpRequest::METHOD_DELETE:
		$this->options[CURLOPT_CUSTOMREQUEST] = HttpRequest::METHOD_DELETE;
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

	if ($this->response === true) // пустой ответ сервера
	    $this->response = null;

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
	$this->headers[(string) $name] = (string) $value;
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

    public function setReceiveFile($file)
    {
	$this->options[CURLOPT_FILE] = $file;

	if ($this->response == null) //TODO: сомневаюсь как то.
	    $this->getResponse();
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

    public function setFollowRedirects($followRedirects)
    {
	$this->options[CURLOPT_FOLLOWLOCATION] = $followRedirects;
    }

    public function getConnectTimeout()
    {
	return array_key_exists(CURLOPT_CONNECTTIMEOUT, $this->options) ? $this->options[CURLOPT_CONNECTTIMEOUT] : 0;
    }

    public function setConnectTimeout($timeout)
    {
	$this->options[CURLOPT_CONNECTTIMEOUT] = $timeout;
    }

    public function setReadTimeout($timeout)
    {
	$this->options[CURLOPT_TIMEOUT] = $timeout;
    }

    public function getReadTimeout()
    {
	return array_key_exists(CURLOPT_TIMEOUT, $this->options) ? $this->options[CURLOPT_TIMEOUT] : 0;
    }

    function __destruct()
    {
	if (!empty($this->options[CURLOPT_INFILE]))
	    fclose($this->options[CURLOPT_INFILE]);

	curl_close($this->curl);
    }

}