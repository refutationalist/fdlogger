<?php
require("boatload.php");

$t = new logger\test();
$c = 0;

do {
	printf("%s: %d / %s\n", ...$t->update_junk_radio("radio1"));
	printf("%s: %d / %s\n", ...$t->update_junk_radio("radio2"));
	printf("%s: %d / %s\n", ...$t->update_junk_radio("radio3"));


	if ($c++ % 2) {
		$t->delete_radio("radio4");
		echo "radio4 deleted\n";
	} else {
		printf("%s: %d / %s\n", ...$t->update_junk_radio("radio4"));
	}

	echo "---\n";

} while(sleep(30) == 0);

