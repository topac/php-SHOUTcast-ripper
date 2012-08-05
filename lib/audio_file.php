<?php
  namespace SHOUTcastRipper;

  class AudioFile {
    private $handle = null;

    public function __construct($path){
      $this->handle = fopen($path, 'wb');
    }

    public function __destruct(){
      if ($this->handle) fclose($this->handle);
    }

    public function write_buffer($buffer, $start=0, $len = null){
      $buffer = $len ? substr($buffer, $start, $len) : substr($buffer, $start);
      fwrite($this->handle, $buffer);
    }

    public function write_buffer_skipping_metadata($buffer, $meta_start, $meta_len){
      if ($meta_start != 0)
        fwrite($this->handle, substr($buffer, 0, $meta_start));
      fwrite($this->handle, substr($buffer, $meta_start+$meta_len));
    }
  }
?>