<?php
  namespace SHOUTcastRipper;

  class MetadataBlock {
    private $expected_length, $content;

    public function __construct($expected_length) {
      $this->content = '';
      $this->expected_length = $expected_length;
    }

    public function expected_length() {
      return $this->expected_length;
    }

    public function write($buffer) {
      $this->content .= $buffer;
    }

    public function content() {
      return $this->content;
    }

    public function length() {
      return strlen($this->content);
    }

    public function is_complete() {
      return $this->remaining_length() == 0;
    }

    public function remaining_length() {
      return $this->expected_length - $this->length();
    }

    public function stream_title() {
      if (!$this->is_complete())
        throw new \Exception("The metadata block is not complete yet");
      $start = strlen("StreamTitle=");
      $end = strpos($this->content, ";", $start);
      return substr($this->content, $start, $end-$start);
    }
  }
?>