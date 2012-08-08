<?php
  namespace SHOUTcastRipper;

  class ResponseHeader {
    private $content = '';
    private $empty_line_index = null;

    public function write_buffer($buffer){
      $this->content .= $buffer;
    }

    private function empty_line_index(){
      if ($this->empty_line_index != null)
        return $this->empty_line_index;
      $index = strpos($this->content, "\r\n\r\n");
      return $index ? $index+4 : null;
    }

    public function is_complete(){
      return !!$this->empty_line_index();
    }

    public function remove_tail_stream_data() {
      if (!$this->is_complete()) throw new Exception("headers not completed");
      $headers = substr($this->content, 0, $this->empty_line_index());
      $stream = substr($this->content, strlen($headers));
      $this->content = $headers;
      return $stream;
    }

    public function icy_metaint(){
      if (!$this->is_complete()) throw new Exception("headers not completed");
      $header_name = "icy-metaint";
      if (($end_of_header_name = stripos($this->content, "$header_name:")) === false) {
        return null;
      }
      $end_of_header_name += strlen($header_name)+1;
      $end_of_header_value = strpos($this->content, "\r\n", $end_of_header_name);
      return substr($this->content, $end_of_header_name, $end_of_header_value-$end_of_header_name)*1;
    }
  }
?>