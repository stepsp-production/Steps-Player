<?php
$u = 'https://46.152.153.249/hls/live/playlist.m3u8';
$h = @get_headers($u, 1);
header('Content-Type: text/plain; charset=UTF-8');
var_export($h);
