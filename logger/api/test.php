<?php
require("boatload.php");

$p = new logger\test();

echo "inserting junk logs\n";
$p->create_junk_logs(100000);
echo "inserting junk notes\n";
$p->create_junk_notes();
echo "done\n";
