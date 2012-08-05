<?php
  namespace SHOUTcastRipper;

  require 'http_headers.php';


  function icy_metaint($http_response_headers){
    $header_name   = "icy-metaint";
    if (($end_of_header_name = stripos($http_response_headers, "$header_name:")) === false)
      return null;
    $end_of_header_name += strlen($header_name)+1;
    $end_of_header_value = strpos($http_response_headers, "\r\n", $end_of_header_name);
    return substr($http_response_headers, $end_of_header_name, $end_of_header_value-$end_of_header_name)*1;
  }

  function handle_metadata($metadata){
    echo "\nMETADATA IS $metadata";
  }

  function write_mp3($buffer, $start=0, $len = null){
    global $mp3;
    $buffer = $len ? substr($buffer, $start, $len) : substr($buffer, $start);
    fwrite($mp3, $buffer);
    fflush($mp3);
  }

  function write_mp3_skip_metadata($buffer, $start, $len){
    global $mp3;
    fwrite($mp3, substr($buffer, 0, $start));
    fwrite($mp3, substr($buffer, $start+$len));
    fflush($mp3);
  }

  function end_of_response_headers($buffer){
    $delimiter = "\r\n\r\n";
    $end_of_headers = strpos($buffer, $delimiter) + 4;
    return ($end_of_headers==4) ? null : $end_of_headers;
  }

  function strip_http_response_headers($buffer){
    global $headers, $icy_metaint, $next_metadata_index, $http_response_headers;

    if ($http_response_headers)
      return $buffer;

    $headers .= $buffer;
    $end_of_headers = end_of_response_headers($headers);
    if ($end_of_headers == null)
      return null;
    $http_response_headers = substr($headers, 0, $end_of_headers);
    $icy_metaint = icy_metaint($http_response_headers);
    $next_metadata_index = $icy_metaint;
    echo "headers readed\nicy_metaint=$next_metadata_index";
    return substr($headers, strlen($http_response_headers));
  }

  error_reporting(E_ALL);
  set_time_limit(60*60);

  // $address = "http://fsolerio.primcast.com";
  $address = "fsolerio.primcast.com";
  // $address = "38.96.148.37";
  $port = 6178;


  if (($fp = fsockopen($address, $port, $errno, $errstr)) == false)
    die("Error $errno: $errstr\n");

  // stream_set_timeout($fp, 0, 10 * 1000);
  $http_header_opts = array(
    'port'           => $port,
    'custom_headers' => array('Icy-MetaData' => 1)
  );
  $req = new Http\RequestHeaders($address, $http_header_opts);

  echo "\n\n{$req}\n\n";

  fputs($fp, $req);

  $mp3 = fopen('/Users/topac/dev/php-SHOUTcast-ripper/tmp.mp3', 'wb');
  $recv_bytes_count = 0;
  $http_response_headers = null;
  $headers = '';
  $next_metadata_index = null;
  $icy_metaint = null;

  $metadata = '';
  $remaining_meta = 0;
  $metadata_len = 0;

  while ($buffer = fread($fp, 2048)) {

    $buffer = strip_http_response_headers($buffer);

    if ($buffer == null)
      continue;

    $buffer_len = strlen($buffer);
    $recv_bytes_count += $buffer_len;

    # There is still some metadata in the new buffer.
    if ($remaining_meta > 0){
      $metadata .= substr($buffer, 0, $remaining_meta);
      $remaining_meta = $metadata_len - strlen($metadata);
      if ($remaining_meta == 0)
        handle_metadata($metadata);
    }

    if ($icy_metaint && $recv_bytes_count > $next_metadata_index) {
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
        write_mp3_skip_metadata($buffer, $start+1, $metadata_len+1);
        if ($remaining_meta == 0){
          handle_metadata($metadata);
        }
      } else {
        # Metadata block is not present.
        write_mp3_skip_metadata($buffer, $start, 1);
        $metadata = '';
        $remaining_meta = 0;
      }

      echo "\nremaining_meta = $remaining_meta";
      $next_metadata_index = $next_metadata_index+$icy_metaint+$metadata_len+1;
    } else{
      write_mp3($buffer);
    }

  } //end while

  fclose($mp3);
  fclose($fp);
?>