<?php
include 'Torrent.php';
header('Content-type: text/plain');
$torrent = new Torrent(file_get_contents('/media/htpc/bit.torrents/tv/Squidbillies.torrent'));
        $hash = $torrent->hash_info();
        $files = $torrent->content();
        $scrape = $torrent->scrape(null, null, 3);
        print_r(array(
          'hash' => $hash,
          'files' => $files,
          'scrape' => $scrape
        ));
?>
