<?php

class SocketInterface implements HttpURLConnection {

    const MIN_RESPONSE_SIZE = 0x200;

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

    public function __construct(array $url) {
        $this->url = $url;
        $this->response_headers = array();
        $this->headers = array("Connection" => "close");
    }

    public function __destruct() {
        if (is_resource($this->socket)) {
            fclose($this->socket);
        }
    }

    public function getConnectTimeout() {
        return $this->connect_timeout;
    }

    public function getHeaderField($name) {
        if (empty($this->response_headers))
            $this->getResponse();

        if (!array_key_exists($name, $this->response_headers))
            return null;
        return $this->response_headers[$name];
    }

    public function getHeaderFields() {
        if (empty($this->response_headers))
            $this->getResponse();
        return $this->response_headers;
    }

    public function getReadTimeout() {
        return $this->read_timeout;
    }

    public function getRequestMethod() {
        return $this->method;
    }

    public function getResponse() {
        if ($this->response != null)
            return $this->response;

        $this->socket = fsockopen($this->url['host'], !empty($this->url['port']) ? $this->url['port'] : 80, $errno, $errstr, $this->connect_timeout);
        if (!$this->socket)
            throw new HttpRequestException($errstr, $errno);

        if ($this->read_timeout)
            stream_set_timeout($this->socket, $this->read_timeout);

        if (!$this->fwrite_stream($this->socket, $this->getRequest()))
            throw new HttpRequestException("Невозможно передать данные");

        $this->readResponse();
    }

    private function readResponse() { //TODO: возможно стоит использовать fread + проверять на ошибку 
        $offset = 0;
        $headers = explode("\r\n", stream_get_contents($this->socket, $this::MIN_RESPONSE_SIZE));
        foreach ($headers as $header) {
            $offset++;
            if (empty($header))
                break;

            if (preg_match("/^HTTP\/1\.\d\s(\d+)\s(.*)$/", $header, $matches)) {
                $this->response_code = $matches[1];
                $this->response_message = $matches[2];

                continue;
            }

            $pos = strpos($header, ':');
            if ($pos !== false)
                $this->response_headers[substr($header, 0, $pos++)] = trim(substr($header, $pos));
        }

        $this->response = implode('', array_slice($headers, $offset));
        $this->response.= stream_get_contents($this->socket);
    }

    private function fwrite_stream($fp, $string) {
        for ($written = 0; $written < strlen($string); $written += $fwrite) {
            $fwrite = fwrite($fp, substr($string, $written));
            if ($fwrite === false) {
                return $written;
            }
        }
        return $written;
    }

    private function getRequest() {
        $request = $this->method . ' ' . $this->url['path'] . " HTTP/1.1\r\n";
        $this->headers['Host'] = $this->url['host'];
        foreach ($this->headers as $name => $value) {
            $request.=$name . ': ' . $value . "\r\n";
        }

        $request.="\r\n";

        return $request;
    }

    public function getResponseCode() {
        if (!$this->response_code)
            $this->getResponse();
        return $this->response_code;
    }

    public function getResponseMessage() {
        if (!$this->response_message)
            $this->getResponse();
        return $this->response_message;
    }

    public function setConnectTimeout($timeout) {
        $this->connect_timeout = $timeout;
    }

    public function setFollowRedirects($followRedirects) {
        
    }

    public function setPostFields($data) {
        
    }

    public function setReceiveFile($file) {
        
    }

    public function setRequestMethod($method) {
        $this->method = $method;
    }

    public function setRequestProperty($name, $value) {
        $this->headers[$name] = $value;
    }

    public function setUploadFile($fileName) {
        
    }

    public function setReadTimeout($timeout) {
        $this->read_timeout = $timeout;
    }

}