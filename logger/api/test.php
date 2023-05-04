<?php
require("boatload.php");

$p = new logger\test();

/*
echo "inserting junk logs\n";
$p->create_junk_logs();
echo "inserting junk notes\n";
$p->create_junk_notes();
echo "done\n";
 */

echo $p->servertime() . "\n\n";

/*
print_r($p->zones());
print_r($p->zones(true));
 */

print_r($p->classes());
