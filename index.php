<?php
/*-----------------------------------

* SimPas
* (c) Macsch15 - web@macsch15.pl

-----------------------------------*/

(version_compare(phpversion(), '5.3.0', '>') ? null : die('Requires PHP 5.3 or higher'));

$microtime = microtime(true);

require_once __DIR__ . DIRECTORY_SEPARATOR . 'simpas.php';

$simpas = new SimPas($microtime);
$simpas -> doOutput();