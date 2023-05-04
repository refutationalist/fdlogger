<?php
/**
 *
 * This code sucks marginally less than the US code, but only because CA does more
 * sensible database dumps, so there's far less to load and check.
 *
 * Canadian Callsign Database:
 * 	 Info: https://www.ic.gc.ca/eic/site/025.nsf/eng/h_00004.html
 *	 Link: https://apc-cap.ic.gc.ca/datafiles/amateur_delim.zip
 * 
 * As with the US callsign dumper, it will print the total number of records
 * in a comment at the top.
 *
 * The first line was a record name list, this is what it looked like:
 *
 *[0] => Array
        (
            [0] => callsign
            [1] => first_name
            [2] => surname
            [3] => address_line
            [4] => city
            [5] => prov_cd
            [6] => postal_code
            [7] => qual_a
            [8] => qual_b
            [9] => qual_c
            [10] => qual_d
            [11] => qual_e
            [12] => club_name
            [13] => club_name_2
            [14] => club_address
            [15] => club_city
            [16] => club_prov_cd
            [17] => club_postal_code
        ) 
 */
ini_set("memory_limit", -1);
set_time_limit(0);

$raw = explode("\n", trim(file_get_contents("ca/amateur_delim.txt")));
array_shift($raw);

$subqueries = [];

foreach ($raw as $r) {
	$rec = explode(";", trim($r));

	$call = $rec[0];

	if ($rec[12] == "") {
		$name = $rec[1] . ' ' . $rec[2];
		$city = $rec[4];
		$state = $rec[5];
	} else {
		$name = $rec[12];
		$city = $rec[15];
		$state = $rec[16];
	}

	$subqueries[] = sprintf(
		"\t('%s', '%s', '%s', '%s')",
		$call,
		addslashes($name),
		addslashes($city),
		strtoupper($state)
	);

}


printf("/* Total Number of Active CA Calls: %d */\n", count($subqueries));

foreach (array_chunk($subqueries, 1000) as $part) {
	echo "INSERT INTO fdcallbook(csign, name, city, state) VALUES\n";
	echo join(",\n", $part);
	echo ";\n\n";
}



