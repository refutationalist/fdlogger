<?php
require('boatload.php');

$part = null;
switch ($_GET["a"]) {

	case "user":
		$part = new logger\user();
		break;

	case "radio":
		$part = new logger\radio();
		break;

	case "summary":
		$part = new logger\summary();
		break;

	case "test";
		if (\config::$debug) $part = logger\test();
		break;

}

if ($part != null) {
	$part->process();
} else {
	echo json_encode([ "error" => "mode detection error" ]);
}

?>
