<?php
  error_reporting(E_ALL);
  set_time_limit(0);
  date_default_timezone_set('Europe/Rome');

  require 'lib/ripper.php';

  $address = "fsolerio.primcast.com";
  $port = 6178;

  $ripper = new SHOUTcastRipper\Ripper();
  $ripper->start($address, $port);
?>