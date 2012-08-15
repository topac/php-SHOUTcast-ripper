<?php
  error_reporting(E_ALL);
  set_time_limit(0);
  date_default_timezone_set('Europe/Rome');

  require 'lib/ripper.php';

  $url = "http://fsolerio.primcast.com:6178";
  // $url = "http://ghost.wavestreamer.com:5254/stream/18446744073709551615/";

  $ripper = new SHOUTcastRipper\Ripper(array(
    'path'               => './ripped_streams',
    'split_tracks'       => true,
    'max_track_duration' => 3600
  ));

  $ripper->start($url);
?>