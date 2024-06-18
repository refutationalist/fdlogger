<?php
require("../api/boatload.php");

$p = new logger\test();

echo "inserting junk logs\n";
$p->create_junk_logs();
echo "inserting junk notes\n";
$p->create_junk_notes();
echo "done\n";
