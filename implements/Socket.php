<?php

/**
 * @author Gar|k <garik.djan@gmail.com>
 * @copyright (c) 2013, http://c0dedgarik.blogspot.ru/
 * @version 0.1
 */

namespace Garik;

class SocketInterface implements HttpURLConnection
{

    const MIN_RESPONSE_SIZE = 0x200;
    const BOUNDARY = "00content0boundary00";
    const CONTENT_TYPE_MULTIPART = "multipart/form-data; boundary=00content0boundary00";
    const CONTENT_TYPE_FORM = "application/x-www-form-urlencoded";
    const CRLF = "\r\n";
    const ERROR_FILE_READ = "Невозможно прочитать файл %s";
    const ERROR_FILE_EXIST = "Файла %s не существует";
    const ERROR_SEND = "Невозможно передать данные";

    private $response;
    private $response_code;
    private $response_message;
    private $response_headers;
    private $connect_timeout;
    private $read_timeout;
    private $method;
    private $url;
    private $socket;
    private $headers;
    private $post_fields;
    private $receive_file;
    private $follow_redirects;

    public function __construct(array $url)
    {
        $this->url = $url;
        $this->headers = array();
        $this->connect_timeout = ini_get("default_socket_timeout");
        $this->setRequestProperty(HttpRequest::HEADER_CONNECTION, "close");
    }

    public function __destruct()
    {
        if (is_resource($this->post_fields))
            fclose($this->post_fields);

        if (is_resource($this->socket))
            fclose($this->socket);
    }

    public function getConnectTimeout()
    {
        return $this->connect_timeout;
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

    public function getReadTimeout()
    {
        return $this->read_timeout;
    }

    public function getRequestMethod()
    {
        return $this->method;
    }

    public function getResponse()
    {
        if ($this->response != null)
            return $this->response;

        if (is_resource($this->socket)) // при повторном обращении если follow_redirects
            fclose($this->socket);

        $this->socket = fsockopen($this->url['host'], !empty($this->url['port']) ? $this->url['port'] : 80, $errno, $errstr, $this->connect_timeout);

        if (!$this->socket)
            throw new HttpRequestException($errstr, $errno);

        if ($this->read_timeout)
            stream_set_timeout($this->socket, $this->read_timeout);

        $this->send($this->getRequest());

        if (!empty($this->post_fields))
            $this->send($this->post_fields);

        $this->readResponse();

        return $this->response;
    }

    private function readResponse()
    { //TODO: возможно стоит использовать fread + проверять на ошибку
        $offset = 0;
        $this->response_headers = array();
        $this->response = null;

        $headers = explode(self::CRLF, stream_get_contents($this->socket, self::MIN_RESPONSE_SIZE));
        foreach ($headers as $header)
        {
            $offset++;
            if (empty($header))
                break;

            if (preg_match("/^HTTP\/1\.\d\s(\d+)\s(.*)$/", $header, $matches))
            {
                $this->response_code = $matches[1];
                $this->response_message = $matches[2];

                continue;
            }

            if (preg_match("/^(.*?):(.*?)$/", $header, $matches))
                $this->response_headers[trim($matches[1])] = trim($matches[2]);
        }

        if ($this->follow_redirects == true && array_key_exists(HttpRequest::HEADER_LOCATION, $this->response_headers))
        {
            $this->url = parse_url($this->response_headers[HttpRequest::HEADER_LOCATION]);
            $this->response = $this->getResponse();
            return; // остальная часть ответа сервера нам не нужна.
        }

        if (is_resource($this->receive_file))
        {
            $this->fwrite_string($this->receive_file, implode(self::CRLF, array_slice($headers, $offset)));
            $this->fwrite_stream($this->receive_file, $this->socket);
        }
        else
        {
            $this->response = implode(self::CRLF, array_slice($headers, $offset));
            $this->response.= stream_get_contents($this->socket);
        }
    }

    private function fwrite_string($fp, $string)
    {
        for ($written = 0; $written < strlen($string); $written += $fwrite)
        {
            $fwrite = fwrite($fp, substr($string, $written));
            if ($fwrite === false)
            {
                return $written;
            }
        }
        return $written;
    }

    private function fwrite_stream($fp, $stream)
    {
        // TODO: не факт что так лучше...
        return stream_copy_to_stream($stream, $fp);
    }

    private function getRequest()
    {
        $request = $this->method.' '.(!empty($this->url['path']) ? $this->url['path'] : '/').(!empty($this->url['query']) ? '?'.$this->url['query'] : '')." HTTP/1.1".self::CRLF;
        $this->headers['Host'] = $this->url['host'];
        foreach ($this->headers as $name => $value)
        {
            $request .= $name.': '.$value.self::CRLF;
        }

        $request .= self::CRLF;

        return $request;
    }

    public function getResponseCode()
    {
        if (!$this->response_code)
            $this->getResponse();
        return $this->response_code;
    }

    public function getResponseMessage()
    {
        if (!$this->response_message)
            $this->getResponse();
        return $this->response_message;
    }

    public function setConnectTimeout($timeout)
    {
        $this->connect_timeout = $timeout;
    }

    public function setFollowRedirects($followRedirects)
    {
        $this->follow_redirects = $followRedirects;
    }

    public function setPostFields($data)
    {
        if (is_string($data))
        {
            $this->post_fields = $data;
            $this->setRequestProperty(HttpRequest::HEADER_CONTENT_LENGTH, strlen($this->post_fields));
            $this->setRequestProperty(HttpRequest::HEADER_CONTENT_TYPE, self::CONTENT_TYPE_FORM);
        }

        if (is_array($data))
        {
            $this->post_fields = tmpfile();

            $lenght = 0;
            foreach ($data as $name => $value)
            {
                if ($value[0] == '@')
                {
                    $filepath = mb_substr($value, 1);
                    if (!file_exists($filepath))
                        throw new HttpRequestException(sprintf(self::ERROR_FILE_EXIST, $filepath));

                    $input = fopen($filepath, 'rb');
                    if (!$input)
                        throw new HttpRequestException(sprintf(self::ERROR_FILE_READ, $filepath));

                    $lenght+=$this->fwrite_string($this->post_fields, $this->partHeader($name, $filepath, $this->getContentType($filepath)));
                    $lenght+=$this->fwrite_stream($this->post_fields, $input);
                    $lenght+=$this->fwrite_string($this->post_fields, self::CRLF);

                    fclose($input);
                }
                else
                {
                    $lenght+=$this->fwrite_string($this->post_fields, $this->partHeader($name));
                    $lenght+=$this->fwrite_string($this->post_fields, $value.self::CRLF);
                }
            }

            $lenght+=$this->fwrite_string($this->post_fields, '--'.self::BOUNDARY.'--'.self::CRLF);
            rewind($this->post_fields);

            $this->setRequestProperty(HttpRequest::HEADER_CONTENT_LENGTH, $lenght);
            $this->setRequestProperty(HttpRequest::HEADER_CONTENT_TYPE, self::CONTENT_TYPE_MULTIPART);
        }
    }

    private function getContentType($filepath)
    {
        $type = false;

        if (function_exists('finfo_open') && ($info = finfo_open(FILEINFO_MIME)) !== false)
        {
            $type = finfo_file($info, $filepath);
            finfo_close($info);
        }

        return $type === false ? "application/octet-stream" : $type;
    }

    private function partHeader($name, $filename = null, $contentType = null)
    {
        $headers['Content-Disposition'] = 'form-data; name="'.$name.'"'.($filename ? '; filename="'.$filename.'"' : '');

        if ($contentType)
        {
            $headers[HttpRequest::HEADER_CONTENT_TYPE] = $contentType;
            $headers['Content-Transfer-Encoding'] = 'binary'; // TODO: а может и не надо
        }

        $part = '--'.self::BOUNDARY.self::CRLF;
        foreach ($headers as $name => $value)
            $part.=$name.': '.$value.self::CRLF;

        $part.=self::CRLF;

        return $part;
    }

    public function setReceiveFile($file)
    {
        $this->receive_file = $file;
    }

    public function setRequestMethod($method)
    {
        $this->method = $method;
    }

    public function setRequestProperty($name, $value)
    {
        $this->headers[$name] = $value;
    }

    public function setUploadFile($fileName)
    {
        $this->setRequestMethod(HttpRequest::METHOD_PUT);
        $this->setRequestProperty(HttpRequest::HEADER_CONTENT_LENGTH, filesize($fileName));

        $this->post_fields = fopen($fileName, 'r');
        if (!$this->post_fields)
            throw new HttpRequestException(sprintf(self::ERROR_FILE_READ, $fileName));
    }

    public function setReadTimeout($timeout)
    {
        $this->read_timeout = $timeout;
    }

    /**
     * Послать данные в сокет
     *
     * @param string|resource $input string or stream
     * @return int количество передааных байт
     * @throws HttpRequestException
     */
    private function send($input)
    {
        $length = 0;

        if (is_string($input))
            $length = $this->fwrite_string($this->socket, $input);
        else if (is_resource($input))
            $length = $this->fwrite_stream($this->socket, $input);

        if (!$length)
            throw new HttpRequestException(self::ERROR_SEND);

        return $length;
    }

}
