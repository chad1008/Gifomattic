<?php

// Set the query input to the ID passed in by Alfred
$url = $argv[1];

$local_file = $_SERVER['alfred_workflow_data'] . '/staged_gif.gif';

$image = file_get_contents( $url );
file_put_contents( $local_file ,$image );

// Set the output to the file path so the next script can easily grab it
echo $local_file;