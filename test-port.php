<?php
$host = '34.9.17.64';
$port = 3306;

$connection = @fsockopen($host, $port, $errno, $errstr, 5);
if ($connection) {
    echo "Port $port is open on $host";
    fclose($connection);
} else {
    echo "Cannot connect to $host:$port - $errstr ($errno)";
}
