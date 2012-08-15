<?php
  namespace SHOUTcastRipper;

  class HttpStreaming {
    private $socket, $address, $port, $response_header;
    const READ_BUFFER_LEN = 2048;
    const CONNECT_TIMEOUT = 10;

    public function __construct($address, $port) {
      $this->address = $address;
      $this->port = $port;
    }

    public function __destruct() {
      $this->close();
    }

    public function response_header() {
      return $this->response_header;
    }

    /**
     * Opens a socket to the given address:port and sends the http header,
     * the server will reply with an http response header and a continuous flow of bytes
     * that represent the audio data.
     */
    public function open() {
      $this->close();
      if (!($this->socket = fsockopen($this->address, $this->port, $errno, $errstr, self::CONNECT_TIMEOUT)))
        throw new \Exception("fsockopen() return error $errno: $errstr");
      $this->response_header = new ResponseHeader();
      $this->send_request_header();
      $this->read_response_header();
    }

    /**
     * Return a buffer of audio data readed from the socket.
     * The audio data can contains metadata.
     */
    public function read_stream() {
      return ($this->response_header->contains_audio_data()) ? $this->response_header->remove_tail_audio_data() : $this->read();
    }


    public function close() {
      if (is_resource($this->socket)) fclose($this->socket);
    }

    private function read() {
      return fread($this->socket, self::READ_BUFFER_LEN);
    }

    /**
     * Recursively reads data from the socket until the http response header is totally received.
     */
    private function read_response_header() {
      $buffer = $this->read();
      if (!$this->response_header->is_complete()) $this->response_header->write_buffer($buffer);
      if ($this->response_header->is_complete()) return;
      $this->read_response_header();
    }

    private function send_request_header() {
      fputs($this->socket, $this->request_header());
    }

    /**
     * Return a RequestHeader object. If the http headers contains the custom header "Icy-MetaData"
     * the SHOUTcast server will reply with the audio stream and a metadata block containing the current
     * stream title and other infos.
     */
    private function request_header() {
      return new RequestHeader($this->address, array(
        'port'           => $this->port,
        'custom_headers' => array('Icy-MetaData' => 1)
      ));
    }
  }
?>