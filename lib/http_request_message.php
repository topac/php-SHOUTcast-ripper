<?php
  namespace SHOUTcastRipper;

  class HttpRequestMessage {
    private $content='';
    const CRLF = "\r\n";

    public function __construct($address, $port=80, $headers=array()) {
      $path = '/';
      $this->add_line("GET $path HTTP/1.1");
      $this->add_header("Host", "$address:$port");
      $this->add_header("User-Agent", "PHP");
      $this->add_header("Accept", "*/*");
      foreach ($headers as $key => $value)
        $this->add_header($key, $value);
      $this->add_line("");
    }

    public function content() {
      return $this->content;
    }

    private function add_header($key, $value) {
      $this->add_line("$key: $value");
    }

    private function add_line($text){
      $this->content .= $text.self::CRLF;
    }
  }
?>