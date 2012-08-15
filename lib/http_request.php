<?php
  namespace SHOUTcastRipper;

  class HttpRequest {
    private $address, $port, $headers;
    const CRLF = "\r\n";

    public function __construct($address, $port=80, $headers=array()) {
      $this->address = $address;
      $this->port    = $port;
      $this->headers = $headers;
    }

    public function __toString() {
      return $this->generate();
    }

    private function generate() {
      $uri = '/';
      $http_request  = "GET $uri HTTP/1.1".self::CRLF;
      $http_request .= "Host: {$this->address}:{$this->port}".self::CRLF;
      $http_request .= "User-Agent: PHP".self::CRLF;
      $http_request .= "Accept: */*".self::CRLF;

      foreach ($this->headers as $k => $v)
        $http_request .= $k .': '. $v . self::CRLF;

      $http_request .= self::CRLF;
      return $http_request;
    }
  }
?>