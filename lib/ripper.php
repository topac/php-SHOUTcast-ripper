<?php
  namespace SHOUTcastRipper;

  require 'http_request_headers.php';
  require 'reponse_header.php';
  require 'audio_file.php';
  require 'metadata_block.php';

  class Ripper {
    const BUFLEN = 2048;
    private $address, $port;
    private $recv_bytes_count = 0;
    private $http_response_headers = null;
    private $next_metadata_index = null;
    private $icy_metaint = null;
    private $resp_header;
    private $mp3;
    private $socket;
    private $metadata;

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

    private function metadata_block_completed($metadata){
      echo "\nMETADATA IS: ".$metadata->content()." (".$metadata->length().")";
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
      while ($buffer = fread($this->socket, self::BUFLEN)) {
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
      if ($this->metadata && $this->metadata->remaining_length() > 0){
        $remaining_len = $this->metadata->remaining_length();
        $this->metadata->append_content(substr($buffer, 0, $remaining_len));
        if ($this->metadata->remaining_length() == 0) {
          $this->metadata_block_completed($this->metadata);
          $this->mp3->write_buffer_skipping_metadata($buffer, 0, $remaining_len+1);
        }
      }
      # A new metadata block has begun
      else if ($this->icy_metaint && $this->recv_bytes_count > $this->next_metadata_index) {
        $start = $buffer_len-($this->recv_bytes_count-$this->next_metadata_index);
        $this->metadata = new MetadataBlock(ord($buffer[$start])*16);
        $end = $start+1+$this->metadata->expected_length();

        echo "\n=====================\nstart = $start";
        echo "\nend = $end";
        echo "\nmetadata_len = ".$this->metadata->expected_length();
        echo "\nbuf len = ".$buffer_len;
        echo "\nnext metaint = $this->next_metadata_index";
        echo "\nrecv_bytes_count = $this->recv_bytes_count";

        # Metadata block is present.
        if ($this->metadata->expected_length() > 0){
          $this->metadata->append_content(($start != $buffer_len) ? substr($buffer, $start+1, $this->metadata->expected_length()) : '');
          $this->mp3->write_buffer_skipping_metadata($buffer, $start+1, $this->metadata->expected_length()+1);
          if ($this->metadata->remaining_length() == 0){
            $this->metadata_block_completed($this->metadata);
          }
        } else {
          # Metadata block is not present.
          $this->mp3->write_buffer_skipping_metadata($buffer, $start, 1);
        }

        echo "\nremaining_meta = ".$this->metadata->remaining_length();
        $this->next_metadata_index = $this->next_metadata_index+$this->icy_metaint+$this->metadata->expected_length()+1;
      }
      # Buffers with only audio stream can be dumped directly.
      else{
        $this-> mp3->write_buffer($buffer);
      }
    }
  }
?>