<?php
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/App/autoload.php';

$console = new App\Controllers\Console(getopt('us::a:r::c::i:b:d:'));
$console();