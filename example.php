<?php
  error_reporting(E_ALL);
  set_time_limit(0);
  date_default_timezone_set('Europe/Rome');

  require 'lib/ripper.php';

  # You must always specify the protocol (http://)
  $url = "http://fsolerio.primcast.com:6178";

  $ripper = new SHOUTcastRipper\Ripper(array(
    'path'               => './ripped_streams',
    'split_tracks'       => true,
    'max_track_duration' => 3600
  ));

  echo "ripping...\n";
  $ripper->start($url);
?>