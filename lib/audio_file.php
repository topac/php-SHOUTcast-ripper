<?php
  namespace SHOUTcastRipper;

  class AudioFile {
    private $handle, $path, $length, $opened_at;

    public function __construct($path){
      $this->path = $path;
      $this->length = 0;
      $this->opened_at = time();
      $this->open();
    }

    public function __destruct(){
      $this->close();
    }

    public function length(){
      return $this->length();
    }

    public function duration(){
      return time() - $this->opened_at;
    }

    public function write_buffer($buffer, $start=0, $len = null){
      $buffer = $len ? substr($buffer, $start, $len) : substr($buffer, $start);
      fwrite($this->handle, $buffer);
      $this->length += strlen($buffer);
    }

    public function write_buffer_skipping_metadata($buffer, $meta_start, $meta_len){
      if ($meta_start != 0)
        $this->write_buffer(substr($buffer, 0, $meta_start));
      $this->write_buffer(substr($buffer, $meta_start+$meta_len));
    }

    public static function safe_filename($string){
      return trim(preg_replace("/[^a-zA-Z0-9_]+/", "", trim(str_replace(" ", "_", $string))));
    }

    private function open(){
      $this->handle = fopen($this->path, 'wb');
    }

    private function close(){
      if ($this->handle) fclose($this->handle);
    }
  }
?>