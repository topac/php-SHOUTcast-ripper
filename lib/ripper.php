<?php
  namespace SHOUTcastRipper;

  require 'request_header.php';
  require 'reponse_header.php';
  require 'audio_file.php';
  require 'metadata_block.php';
  require 'http_streaming.php';

  class Ripper {
    private $recv_bytes_count = 0;
    private $http_response_headers = null;
    private $next_metadata_index = null;
    private $icy_metaint = null;
    private $resp_header;
    private $mp3;
    private $socket;
    private $metadata;
    private $http_streaming;

    public function start($address, $port){
      $this->resp_header = new ResponseHeader();
      $this->mp3 = new AudioFile('/Users/topac/dev/php-SHOUTcast-ripper/tmp.mp3');
      $this->http_streaming = new HttpStreaming();
      $this->http_streaming->stream($address, $port, array($this, 'handle_recv_data'));
    }

    private function metadata_block_completed($metadata){
      echo "\nMETADATA IS: ".$metadata->content()." (".$metadata->length().")";
    }

    public function handle_recv_data($buffer){
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
        return true;

      # At this point the respose headers has been stored and removed from the audio stream.
      $buffer_len = strlen($buffer);
      $this->recv_bytes_count += $buffer_len;

      # There is still some metadata in the new buffer.
      if ($this->metadata && $this->metadata->remaining_length() > 0){
        $remaining_len = $this->metadata->remaining_length();
        $this->metadata->write_buffer(substr($buffer, 0, $remaining_len));
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
          $this->metadata->write_buffer(($start != $buffer_len) ? substr($buffer, $start+1, $this->metadata->expected_length()) : '');
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
      return true;
    }
  }
?>