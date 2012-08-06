<?php
  namespace SHOUTcastRipper;

  require 'http_request_headers.php';
  require 'reponse_header.php';
  require 'audio_file.php';

  class Ripper {
    private $address, $port;
    private $recv_bytes_count = 0;
    private $http_response_headers = null;
    private $next_metadata_index = null;
    private $icy_metaint = null;
    private $metadata = '';
    private $remaining_meta = 0;
    private $metadata_len = 0;
    private $resp_header;
    private $mp3;
    private $socket;

    public function __construct($address, $port){
      $this->address = $address;
      $this->port = $port;
    }

    public function _destruct(){
      if ($this->socket) fclose($this->socket);
    }

    public function start_ripping(){
      $this->send_http_request();
      echo "[receiving]";
      $this->resp_header = new ResponseHeader();
      $this->mp3 = new AudioFile('/Users/topac/dev/php-SHOUTcast-ripper/tmp.mp3');
      $this->start_recv_loop();
    }

    private function handle_metadata($metadata){
      echo "\nMETADATA IS: $metadata (".strlen($metadata).")";
    }

    private function request_header(){
      return new Http\RequestHeaders($this->address, array(
        'port'           => $this->port,
        'custom_headers' => array('Icy-MetaData' => 1)
      ));
    }

    private function send_http_request(){
      if (($this->socket = fsockopen($this->address, $this->port, $errno, $errstr)) == false)
        throw new Exception("Error $errno: $errstr\n");
      fputs($this->socket, $this->request_header());
    }

    private function start_recv_loop(){
      while ($buffer = fread($this->socket, 38)) {
        $this->handle_recv_data($buffer);
      }
    }


    private function handle_recv_data($buffer){
      # Read headers and the icy-metaint value.
      if (!$this->resp_header->is_complete()){
        $this->resp_header->append_content($buffer);
      }

      if ($this->resp_header->is_complete() && $this->next_metadata_index == null){
        $this->icy_metaint = $this->resp_header->icy_metaint();
        $this->next_metadata_index = $this->resp_header->icy_metaint();
        $buffer = $this->resp_header->remove_tail_stream_data();
      }

      if ($this->next_metadata_index == null)
        return;

      # At this point the respose headers has been stored and removed from the audio stream.
      $buffer_len = strlen($buffer);
      $this->recv_bytes_count += $buffer_len;

      # There is still some metadata in the new buffer.
      if ($this->remaining_meta > 0){
        $remaining_meta_copy = $this->remaining_meta;
        $this->metadata .= substr($buffer, 0, $this->remaining_meta);
        $this->remaining_meta = $this->metadata_len - strlen($this->metadata);
        if ($this->remaining_meta == 0) {
          $this->handle_metadata($this->metadata);
          $this->mp3->write_buffer_skipping_metadata($buffer, 0, $remaining_meta_copy+1);
        }
      }
      # A new metadata block has begun
      else if ($this->icy_metaint && $this->recv_bytes_count > $this->next_metadata_index) {
        $start = $buffer_len-($this->recv_bytes_count-$this->next_metadata_index);
        $this->metadata_len = ord($buffer[$start])*16;
        $end = $start+1+$this->metadata_len;

        echo "\n=====================\nstart = $start";
        echo "\nend = $end";
        echo "\nmetadata_len = $this->metadata_len";
        echo "\nbuf len = ".$buffer_len;
        echo "\nnext metaint = $this->next_metadata_index";
        echo "\nrecv_bytes_count = $this->recv_bytes_count";

        # Metadata block is present.
        if ($this->metadata_len > 0){
          $this->metadata = ($start != $buffer_len) ? substr($buffer, $start+1, $this->metadata_len) : '';
          $this->remaining_meta = $this->metadata_len - strlen($this->metadata);
          $this->mp3->write_buffer_skipping_metadata($buffer, $start+1, $this->metadata_len+1);
          if ($this->remaining_meta == 0){
            $this->handle_metadata($this->metadata);
          }
        } else {
          # Metadata block is not present.
          $this->mp3->write_buffer_skipping_metadata($buffer, $start, 1);
          $this->metadata = '';
          $this->remaining_meta = 0;
        }

        echo "\nremaining_meta = $this->remaining_meta";
        $this->next_metadata_index = $this->next_metadata_index+$this->icy_metaint+$this->metadata_len+1;
      }
      # Buffers with only audio stream can be dumped directly.
      else{
        $this-> mp3->write_buffer($buffer);
      }
    }
  }
?>