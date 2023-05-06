<?php
require("boatload.php");

/*
$p = new logger\test();

echo "inserting junk logs\n";
$p->create_junk_logs();
echo "inserting junk notes\n";
$p->create_junk_notes();
echo "done\n";
 */
//echo $p->servertime() . "\n\n";

/*
print_r($p->zones());
print_r($p->zones(true));
 */

//print_r($p->classes());
//

$u = new logger\user();
/*
print_r(
	$u->add(
		"KG7FZH",
		1,
		"A",
		"DX",
		"146520000",
		"FM",
		5,
		"test",
		"this is a note"
	)
);
 */
//print_r($u->note("yay", "ihandle"));
//
print_r($u->get(0));
