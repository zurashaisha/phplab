<?php
if(isset($_GET['path'])) {
    $url = $_GET['path'];
    clearstatcache();

    header('Content-Description: File Transfer');
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="'.basename($url).'"');
    header('Content-Length: ' . filesize($url));
    header('Pragma: public');
    flush();
    readfile($url,true);
    die();
}

?>