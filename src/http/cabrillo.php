<?php

$filename = "fdlogger-results-".time().".cab";
header("Content-type: text/plain");
header('Content-Type: application/octet-stream');
header("Content-Transfer-Encoding: Binary"); 
header("Content-disposition: attachment; filename=\"$filename\""); 

$cab = new logger\cabrillo();

echo $cab->get();
