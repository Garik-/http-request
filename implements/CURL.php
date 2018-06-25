<?php

/**
 * @author Gar|k <garik.djan@gmail.com>
 * @copyright (c) 2013, http://c0dedgarik.blogspot.ru/
 * @version 0.1
 */
// TODO: нужно проверять версию CURL не все поля поддерживаются.

namespace Garik;

class CURLInterface implements HttpURLConnection
{

    private $curl;
    private $method;
    private $headers;
    private $options;
    private $response;
    private $response_headers;
    private $response_message;
    private $response_code;

    function __construct(Array $url)
    {
        $this->curl = curl_init();

        if (!$this->curl)
            $this->exception();

        $this->options = array(
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER         => true,
            CURLOPT_ENCODING       => '',
            CURLOPT_NOPROGRESS     => true,
            CURLOPT_VERBOSE        => false,
            CURLOPT_SSL_VERIFYPEER => false
        );

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
        if ($this->response === null)
        {
            $this->setOptions();
            $this->response = curl_exec($this->curl);
            $this->setHeaderFields();
        }

        if ($this->response === false)
            $this->exception();

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
        if ($this->response_code)
            return $this->response_code;

        $this->getResponse();
        $this->response_code = curl_getinfo($this->curl, CURLINFO_HTTP_CODE);

        if (false === $this->response_code)
            $this->exception();

        return $this->response_code;
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
        /**
         * Из-за проблем с CURLOPT_HEADERFUNCTION (долго обрабатывается запрос, не рабоатет в цикле)
         * заголовки пришлось обрабатывать вручную, а так как CURLOPT_FILE, перенаправляет
         * весь вывод в файл, пришлось отказаться от разбора заголовков.
         */
        $this->options[CURLOPT_HEADER] = false;
        $this->options[CURLOPT_BINARYTRANSFER] = true;
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

    private function setHeaderFields()
    {
        $header_size = curl_getinfo($this->curl, CURLINFO_HEADER_SIZE);
        if (empty($header_size))
            return;

        $header = substr($this->response, 0, $header_size);

        if (preg_match_all("/(.*?):(.*?)\n/m", $header, $matches))
        {
            $count_matches = count($matches[0]);
            for ($i = 0; $i < $count_matches; $i++)
            {
                $this->response_headers[trim($matches[1][$i])] = trim($matches[2][$i]);
            }
        }

        if (preg_match("/^HTTP\/1\.\d\s\d+\s(.*)\n/", $header, $matches))
            $this->response_message = trim($matches[1]);

        $this->response = substr($this->response, $header_size);

        if (false === $this->response)
            $this->response = null;
    }

    private function exception()
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
