<?php
/**
 *
 * Alright, this code sucks, but it only really needs to be run once a year,
 * so doing by hand is not really a problem.
 *
 * This requires the database dumps for US callsigns.  These are *large* pipe 
 * delimited text files.
 * 
 * US Callsign Database:
 *   Info: https://www.fcc.gov/uls/transactions/daily-weekly
 *   Link: https://data.fcc.gov/download/pub/uls/complete/l_amat.zip
 * 
 * The fields are defined in this document: 
 *   https://www.fcc.gov/sites/default/files/public_access_database_definitions_v9_0.pdf
 *
 * Last time I ran it, it took up about six gig of memory, so keep that in mind.
 * The count of the records is printed as a comment at the top so you can check
 * with the import.
 *
 */
ini_set("memory_limit", -1);
set_time_limit(0);

$en = fccparse("fcc/EN.dat");
$hd = fccparse("fcc/HD.dat");
$am = fccparse("fcc/AM.dat");

$subqueries = [];

foreach ($hd as $idx=>$hdline) {
	if ($hdline[3] != 'A') continue;


	$call = $en[$idx][2];
	$name = ($am[$idx][6] == "") ? $en[$idx][6] . ' ' . $en[$idx][8] : $en[$idx][5];
	$city = $en[$idx][14];
	$state = $en[$idx][15];

	$subqueries[] = sprintf(
		"\t('%s', '%s', '%s', '%s')",
		$call,
		addslashes($name),
		addslashes($city),
		strtoupper($state)
	);

}

printf("/* Total Number of Active US Calls: %d */\n", count($subqueries));

foreach (array_chunk($subqueries, 1000) as $part) {
	echo "INSERT INTO fdcallbook(csign, name, city, state) VALUES\n";
	echo join(",\n", $part);
	echo ";\n\n";
}

function fccparse(string $filename): array|null {

	if (!file_exists($filename)) return null;
	$ret = [];
	foreach (explode("\n", trim(file_get_contents($filename))) as $line) {
		$values = explode("|", $line);
		array_shift($values); // file type
		$id = array_shift($values);
		$ret[$id] = $values;
	}

	return $ret;

}
