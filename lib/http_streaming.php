<?php
  namespace SHOUTcastRipper;

  class HttpStreaming {
    private $socket;
    private $address;
    private $port;
    const BUFLEN = 30;

    public function stream($address, $port, $callback){
      $this->address = $address;
      $this->port = $port;
      $this->open();
      $this->send_request();
      $this->read($callback);
      $this->close();
    }

    private function close(){
      if ($this->socket) fclose($this->socket);
    }

    private function read($callback){
      while ($buffer = fread($this->socket, self::BUFLEN)) {
        if (!call_user_func($callback, $buffer)) break;
      }
    }

    private function open(){
      if (($this->socket = fsockopen($this->address, $this->port, $errno, $errstr)) == false)
        throw new Exception("Error opening socket to $address. [$errno] $errstr");
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