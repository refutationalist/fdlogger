<?php

namespace logger;

class base {
	use traits\db;

	public function __construct() {

		// ham radio is UTC all the time
		$this->query("SET time_zone = '+00:00'");

		// might as well check for dead radios
		$this->query(
			"DELETE FROM fdradio WHERE UNIX_TIMESTAMP() - logged > " .
			(int) \config::$settings->radiopurge
		);
	}

	public function servertime(): float {
		return microtime(true);
	}

	public function zones(bool $by_area = false): array {

		$return = [];

		if ($by_area) {
			$db_zones = $this->fetchall("SELECT * FROM fdzone ORDER BY area ASC, code ASC");
			

			foreach ($db_zones as $z) {

				if (!isset($return[ $z["area"] ])) $return[ $z["area"] ] = [];
				$return[ $z["area"] ][] = [ $z["code"], $z["name"] ];

			}

		} else {
			$db_zones = $this->fetchall("SELECT * FROM fdzone ORDER BY code ASC");
			foreach ($db_zones as $z) $return[ $z["code"] ] = $z["name"];
		}

		return $return;
	}

	public function classes(): array {

		$return = [];

		foreach ($this->fetchall("SELECT * FROM fdclass ORDER BY code") as $c) {
			$return[ $c["code"] ] = $c["text"];
		}

		return $return;

	}

}
