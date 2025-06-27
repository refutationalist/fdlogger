<?php

namespace logger;

class cabrillo extends base {

	public function __construct() {
		parent::__construct();
	}

	public function get(): string {

		// UGLY.
		$buffer =
			"START-OF-LOG: 3.0\n".
			"CONTEST: ARRL-FD\n".
			"LOCATION: " . \config::$settings->zone ."\n".
			"CALLSIGN: " . \config::$settings->callsign . "\n" .
			"CATEGORY: " . \config::$settings->tx . \config::$settings->class . "\n" .

			"CATEGORY-BAND: "     . \config::$cabrillo->band . "\n" .
			"CATEGORY-MODE: "     . \config::$cabrillo->mode . "\n" .
			"CATEGORY-OPERATOR: " . \config::$cabrillo->operator . "\n" .
			"CATEGORY-POWER: "    . \config::$cabrillo->power . "\n" .
			"CATEGORY-STATION: "  . \config::$cabrillo->station . "\n" .
			"CLAIMED-SCORE: \n".

			"CLUB: " . \config::$club->club . "\n" .
			"CREATED BY: N9MII Field Day Logger\n" .
			"NAME: " . \config::$club->name . "\n" .
			"ADDRESS: " . \config::$club->address . "\n" .
			"ADDRESS: " . \config::$club->city . ", " . \config::$club->state . ' ' . \config::$club->zip . "\n".
			"ADDRESS-CITY: " . \config::$club->city . "\n".
			"ADDRESS-STATE-PROVINCE: " . \config::$club->state . "\n" .
			"ADDRESS-POSTALCODE: " . \config::$club->zip . "\n" .
			"ADDRESS-COUNTRY: ". \config::$club->country . "\n" .
			"EMAIL: " . \config::$club->email . "\n" .
			"OPERATORS: " . \config::$club->operators . "\n" .
			"SOAPBOX: " . \config::$club->soapbox . "\n"; 
			





		$qsos = $this->fetchall(
			"SELECT ".
			"B.cabfreq AS freq, ".
			"C.cab AS mode, ".
			"DATE_FORMAT(A.logged, '%Y-%m-%d %H%i') AS date, ".
			"A.csign, ".
			"A.tx, ".
			"A.class, ".
			"A.zone ".
			"FROM fdlog A ".
			"LEFT JOIN fdband B ON A.band = B.code ".
			"LEFT JOIN fdmode C ON A.mode = C.code ".
			"WHERE A.band != 'none' ".
			"ORDER BY logged ASC"
		);

		foreach ($qsos as $q) {
			/*
 			 *                               123456789012345
			 * QSO:  7000 PH 2023-06-24 2126 N9MII           7A WWA VX7NA           5A BC 
			 * QSO: 14000 PH 2023-06-24 2127 N9MII           7A WWA N0KV            3A CO 
 			 */
			$buffer .= sprintf(
				"QSO: %5s %s %s %-15s %d%s %s %-15s %d%s %s\n",
				$q["freq"],
				$q["mode"],
				$q["date"],
				\config::$settings->callsign,
				\config::$settings->tx,
				\config::$settings->class,
				\config::$settings->zone,
				$q["csign"],
				$q["tx"],
				$q["class"],
				$q["zone"]
			);

		}

		$buffer .= "END OF LOG:\n";


		return $buffer;



	}


}
