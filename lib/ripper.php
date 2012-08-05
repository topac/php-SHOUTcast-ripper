<?php
  namespace SHOUTcastRipper;

  require 'http_request_headers.php';
  require 'reponse_header.php';
  require 'audio_file.php';

  function handle_metadata($metadata){
    echo "\nMETADATA IS: $metadata (".strlen($metadata).")";
  }

  $address = "fsolerio.primcast.com";
  $port = 6178;

  if (($fp = fsockopen($address, $port, $errno, $errstr)) == false)
    die("Error $errno: $errstr\n");

  $http_header_opts = array(
    'port'           => $port,
    'custom_headers' => array('Icy-MetaData' => 1)
  );

  $req = new Http\RequestHeaders($address, $http_header_opts);

  fputs($fp, $req);

  $mp3 = new AudioFile('/Users/topac/dev/php-SHOUTcast-ripper/tmp.mp3');

  $recv_bytes_count = 0;
  $http_response_headers = null;
  $headers = '';
  $next_metadata_index = null;
  $icy_metaint = null;
  $metadata = '';
  $remaining_meta = 0;
  $metadata_len = 0;

  $resp_header = new ResponseHeader();

  echo "[receiving]";

  while ($buffer = fread($fp, 2048)) {

    # Read headers and the icy-metaint value.
    if (!$resp_header->is_complete()){
      $resp_header->append_content($buffer);
    }

    if ($resp_header->is_complete() && $next_metadata_index == null){
      $icy_metaint = $resp_header->icy_metaint();
      $next_metadata_index = $resp_header->icy_metaint();
      $buffer = $resp_header->remove_tail_stream_data();
    }

    if ($next_metadata_index == null)
      continue;

    # At this point the respose headers has been stored and removed from the audio stream.
    $buffer_len = strlen($buffer);
    $recv_bytes_count += $buffer_len;

    # There is still some metadata in the new buffer.
    if ($remaining_meta > 0){
      $remaining_meta_copy = $remaining_meta;
      $metadata .= substr($buffer, 0, $remaining_meta);
      $remaining_meta = $metadata_len - strlen($metadata);
      if ($remaining_meta == 0) {
        handle_metadata($metadata);
        $mp3->write_buffer_skipping_metadata($buffer, 0, $remaining_meta_copy+1);
      }
    }
    # A new metadata block has begun
    else if ($icy_metaint && $recv_bytes_count > $next_metadata_index) {
      $start = $buffer_len-($recv_bytes_count-$next_metadata_index);
      $metadata_len = ord($buffer[$start])*16;
      $end = $start+1+$metadata_len;

      echo "\n=====================\nstart = $start";
      echo "\nend = $end";
      echo "\nmetadata_len = $metadata_len";
      echo "\nbuf len = ".$buffer_len;
      echo "\nnext metaint = $next_metadata_index";
      echo "\nrecv_bytes_count = $recv_bytes_count";

      # Metadata block is present.
      if ($metadata_len > 0){
        $metadata = ($start != $buffer_len) ? substr($buffer, $start+1, $metadata_len) : '';
        $remaining_meta = $metadata_len - strlen($metadata);
        $mp3->write_buffer_skipping_metadata($buffer, $start+1, $metadata_len+1);
        if ($remaining_meta == 0){
          handle_metadata($metadata);
        }
      } else {
        # Metadata block is not present.
        $mp3->write_buffer_skipping_metadata($buffer, $start, 1);
        $metadata = '';
        $remaining_meta = 0;
      }

      echo "\nremaining_meta = $remaining_meta";
      $next_metadata_index = $next_metadata_index+$icy_metaint+$metadata_len+1;
    }
    # Buffers with only audio stream can be dumped directly.
    else{
      $mp3->write_buffer($buffer);
    }

  } //end of infinite loop

  fclose($fp);
?>