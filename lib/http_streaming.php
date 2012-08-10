<?php
  namespace SHOUTcastRipper;

  class HttpStreaming {
    private $socket, $address, $port, $response_header, $initial_audio_data;
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

    public function response_header(){
      return $this->response_header;
    }
    /**
     * Opens a socket to the given address:port and sends the http header,
     * the server will reply with an http resp header and a continuous flow of bytes
     * that represent the audio data.
     */
    public function open(){
      $this->close();
      $this->initial_audio_data = null;
      $this->response_header = new ResponseHeader();
      if (!($this->socket = fsockopen($this->address, $this->port, $errno, $errstr, self::CONNECT_TIMEOUT)))
        throw new \Exception("fsockopen() return error $errno: $errstr");
      $this->send_request_header();
      $this->read_response_header();
    }

    public function read_stream(){
      if (($ad = $this->initial_audio_data)) {
        $this->initial_audio_data = null;
        return $ad;
      }
      return $this->read();
    }

    private function read(){
      return fread($this->socket, self::BUFLEN);
    }

    public function close(){
      if (is_resource($this->socket)) {
        fclose($this->socket);
        $this->socket = null;
      }
    }

    private function read_response_header(){
      $buffer = $this->read();
      if (!$this->response_header->is_complete()) $this->response_header->write_buffer($buffer);
      if ($this->response_header->is_complete()){
        $this->initial_audio_data = $this->response_header->remove_tail_stream_data();
        return;
      }
      $this->read_response_header();
    }

    private function send_request_header(){
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