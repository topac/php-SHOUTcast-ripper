<?php
  namespace SHOUTcastRipper;

  class HttpStreaming {
    private $socket, $address, $port;
    /**
     * Define the max length of the buffer used by fread.
     */
    const BUFLEN = 2048;
    const CONNECT_TIMEOUT = 10;

    public function __construct($address, $port){
      $this->address = $address;
      $this->port = $port;
    }

    public function __destruct(){
      $this->close();
    }

    /**
     * Opens a socket to the given address:port and sends the http header,
     * the server will reply with an http resp header and a continuous flow of bytes
     * that represent the audio data.
     */
    public function open(){
      $this->close();
      if (!($this->socket = fsockopen($this->address, $this->port, $errno, $errstr, self::CONNECT_TIMEOUT)))
        throw new Exception("fsockopen() return error $errno: $errstr");
      $this->send_request();
    }

    public function read(){
      return fread($this->socket, self::BUFLEN);
    }

    public function close(){
      if (is_resource($this->socket)) {
        fclose($this->socket);
        $this->socket = null;
      }
    }

    private function send_request(){
      fputs($this->socket, $this->request_header());
    }

    /**
     * Return a RequestHeader object. If the http headers contains the custom header "Icy-MetaData"
     * the SHOUTcast server will reply with the audio stream and a metadata block containing the current
     * stream title and other infos.
     *
     * @see "The Shoutcast standard" chapter at http://jicyshout.sourceforge.net/oreilly-article/java-streaming-mp3-pt2/java-streaming-mp3-pt2.html
     */
    private function request_header(){
      return new RequestHeader($this->address, array(
        'port'           => $this->port,
        'custom_headers' => array('Icy-MetaData' => 1)
      ));
    }
  }
?>