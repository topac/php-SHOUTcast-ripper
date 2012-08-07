<?php
  namespace SHOUTcastRipper;

  class HttpStreaming {
    private $socket;
    private $address;
    private $port;
    const BUFLEN = 30;

    public function __construct($address, $port){
      $this->address = $address;
      $this->port = $port;
    }

    public function __destruct(){
      $this->close();
    }

    public function open(){
      if (($this->socket = fsockopen($this->address, $this->port, $errno, $errstr)) == false)
        throw new Exception("Error opening socket to $address. [$errno] $errstr");
      $this->send_request();
    }

    public function read(){
      return fread($this->socket, self::BUFLEN);
    }

    public function close(){
      if ($this->socket) fclose($this->socket);
    }

    private function send_request(){
      fputs($this->socket, $this->request_header());
    }

    private function request_header(){
      return new RequestHeader($this->address, array(
        'port'           => $this->port,
        'custom_headers' => array('Icy-MetaData' => 1)
      ));
    }
  }
?>