<?php
  namespace SHOUTcastRipper;

  class MetadataBlock {
    private $expected_length;
    private $content;

    public function __construct($expected_length){
      $this->content = '';
      $this->expected_length = $expected_length;
    }

    public function expected_length(){
      return $this->expected_length;
    }

    public function write_buffer($data) {
      $this->content .= $data;
    }

    public function content(){
      return $this->content;
    }

    public function length(){
      return strlen($this->content);
    }

    public function is_complete(){
      return $this->remaining_length() == 0;
    }

    public function remaining_length(){
      return $this->expected_length - $this->length();
    }

    private function safe_filename($string){
      return trim(preg_replace("/[^a-zA-Z0-9_]+/", "", str_replace(" ", "_", $string)));
    }

    public function stream_title(){
      if (!$this->is_complete()) return null;
      $start = strlen("StreamTitle=");
      $end = strpos($this->content, ";", $start);
      return $this->safe_filename(substr($this->content, $start, $end-$start));
    }
  }
?>