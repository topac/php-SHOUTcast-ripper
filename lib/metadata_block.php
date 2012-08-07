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

    public function remaining_length(){
      return $this->expected_length - $this->length();
    }
  }
?>