<?php
  namespace SHOUTcastRipper;

  require 'request_header.php';
  require 'reponse_header.php';
  require 'audio_file.php';
  require 'metadata_block.php';
  require 'http_streaming.php';

  class Ripper {
    private $recv_bytes_count = 0;
    private $metadata, $next_metadata_index, $icy_metaint, $resp_header;
    private $mp3;
    private $options, $default_options = array(
      'path'               => '.',
      'split_tracks'       => false,
      'max_track_duration' => 3600, #sec (1 h)
      'max_track_length'   => 102400000 #bytes (100 mb)
    );

    public function __construct($options=array()) {
      $this->options = array_merge($this->default_options, $options);
    }

    public function start($address, $port){
      $http_streaming = new HttpStreaming($address, $port);
      $http_streaming->open();
      $this->open_mp3file($this->default_mp3file_name($address));
      $this->resp_header = new ResponseHeader();
      while ($buffer = $http_streaming->read())
        if (!$this->process_received_data($buffer) || $this->are_limits_reached()) break;
    }

    private function are_limits_reached(){
      $this->mp3->length() >= $this->options['max_track_length'] || $this->mp3->duration() >= $this->options['max_track_duration'];
    }

    private function default_mp3file_name($stream_address=''){
      return AudioFile::safe_filename("$stream_address ".date("Ymd_his"));
    }

    private function open_mp3file($filename){
      $path = realpath($this->options['path'])."/$filename.mp3";
      $this->mp3 = new AudioFile($path);
    }

    private function metadata_block_completed(){
      echo "\nMETADATA IS: ".$this->metadata->content()." (".$this->metadata->length().")";
      if ($this->options['split_tracks'])
        $this->open_mp3file($this->metadata->stream_title());
    }

    private function process_received_data($buffer){
      # Read headers and the icy-metaint value.
      if (!$this->resp_header->is_complete()){
        $this->resp_header->write_buffer($buffer);
      }

      if ($this->resp_header->is_complete() && $this->next_metadata_index == null){
        $this->icy_metaint = $this->resp_header->icy_metaint();
        $this->next_metadata_index = $this->resp_header->icy_metaint();
        $buffer = $this->resp_header->remove_tail_stream_data();
      }

      if ($this->next_metadata_index == null)
        return true;

      # At this point the respose headers has been stored and removed from the audio stream.
      $buffer_len = strlen($buffer);
      $this->recv_bytes_count += $buffer_len;

      # There is still some metadata in the new buffer.
      if ($this->metadata && !$this->metadata->is_complete()){
        $remaining_len = $this->metadata->remaining_length();
        $this->metadata->write_buffer(substr($buffer, 0, $remaining_len));
        if ($this->metadata->is_complete()) {
          $this->metadata_block_completed();
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
          $this->metadata->write_buffer(($start != $buffer_len) ? substr($buffer, $start+1, $this->metadata->expected_length()) : '');
          $this->mp3->write_buffer_skipping_metadata($buffer, $start+1, $this->metadata->expected_length()+1);
          if ($this->metadata->is_complete()){
            $this->metadata_block_completed();
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
      return true;
    }
  }
?>