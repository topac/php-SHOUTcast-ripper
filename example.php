<?php
  error_reporting(E_ALL);
  set_time_limit(0);

  require 'lib/ripper.php';

  $address = "fsolerio.primcast.com";
  $port = 6178;

  $ripper = new SHOUTcastRipper\Ripper($address, $port);
  $ripper->start_ripping();
?>