<?php
/*
 *
 * okay, so what I do to make this work is go to the ARRL zones
 * page and copy and paste into libreoffice calc, clean it up,
 * and then save it as a CSV in "sections.csv"
 *
 * Here's the URL: https://contests.arrl.org/contestmultipliers.php?a=wve
 *
 */

$lines = explode("\n", trim(file_get_contents("sections.csv")));

$sections = [];
$json = [];
$curr = '';


foreach ($lines as $l) {
	$b = str_getcsv($l);

	$b[1] = substr($b[1], 0, -2);

	if (strlen($b[1]) == 0) {
		$curr = $b[0];
		$sections[$curr] = [];
		continue;
	} else {
		$sections[$curr][] = $b;
		$json[$b[0]] = $b[1];
	}
}


switch (@$argv[1]) {

	case 'html':
		foreach ($sections as $area=>$s) {
			echo "<div>\n\t<h4>$area</h4>\n\t<ul>\n";
			foreach ($s as $e) {
				echo "\t\t<li><span>{$e[0]}</span> &mdash; {$e[1]}</li>\n";
			}
			echo "\t</ul>\n</div>\n";
		}

		break;

	case 'json':
		echo json_encode($json, JSON_PRETTY_PRINT)."\n";
		break;

	case 'sql':
	default:

		$subq = [];
		foreach ($sections as $area=>$s) {

			if ($area == "Canada") {
				$area = 'C';
			} else {
				preg_match("/(\d)/", $area, $m);
				$area = $m[0];

			}

			foreach ($s as $e) {

				$subq[] = sprintf(
					"\t('%s', '%s', '%s')",
					$e[0],
					$e[1],
					$area
				);

			}
		}

		echo 
			"INSERT INTO fdzones(code, name, area) VALUES\n".
			join(",\n", $subq).
			";\n";

		break;

}

