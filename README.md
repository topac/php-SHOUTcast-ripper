### php-SHOUTcast-ripper

A PHP script to rip a SHOUTcast server stream.

#### Example

Try out the example.php.

    require 'lib/ripper.php';
    $url = "http://shout_cast_server_addr.com:6178";
    $ripper = new SHOUTcastRipper\Ripper(array(
      'path'               => './ripped_streams',
      'split_tracks'       => true,
      'max_track_duration' => 3600
    ));
    $ripper->start($url);

Options are:

__path__  - Where the mp3 file(s) will be created.  
__split\_tracks__  - If false rip everything in a single mp3 file, otherwise it splits the stream according to the current song.  
__max\_track\_duration__  - Stops ripping and exit when a track reach the specified duration (in seconds).  
__max_track_length__  - Stops ripping and exit when a track reach the specified size (in bytes).  
