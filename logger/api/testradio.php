<?php
require("boatload.php");

$t = new logger\test();

do {
	$t->update_junk_radio("radio1");
	$t->update_junk_radio("radio2");
	$t->update_junk_radio("radio3");
} while(sleep(30) == 0);
