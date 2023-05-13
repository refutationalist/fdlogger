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


	/* Zone functions */

	protected function getzones(bool $by_area = false): array {

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

	protected function inzones(string $in): bool {
		foreach ($this->getzones() as $z=>$crap)
			if ($in === $z) return true;

		return false;
	}

	public function zones(): array {
		return [ true, $this->getzones() ];
	}


	/* Class functions */

	protected function getclasses(): array {

		$return = [];

		foreach ($this->fetchall("SELECT * FROM fdclass ORDER BY code") as $c) {
			$return[ $c["code"] ] = $c["text"];
		}

		return $return;

	}

	protected function inclasses(string $in): bool {
		foreach ($this->getclasses() as $c=>$crap)
			if ($in === $c) return true;

		return false;
	}		

	public function classes(): array {
		return [ true, $this->getclasses() ];
	}


	/* Mode classes */

	protected function getmodes(): array {

		$return = [];

		foreach ($this->fetchall("SELECT code FROM fdmode ORDER BY ord") as $c) {
			$return[] = $c["code"];
		}

		return $return;

	}

	protected function inmodes(string $in): bool {

		foreach ($this->getmodes() as $m)
			if ($m === $in) return true;

		return false;

	}

	public function modes(): array {

		return [ true, $this->getmodes() ];
	}


	/* other helpers */

	public function radios(): array {
		$radios = [];
		foreach ($this->fetchall("SELECT name FROM fdradio") as $r) $radios[] = $r["name"];
		return ([ true, $radios ]);
	}

	public function radio(string $name): array {

		$r = $this->fetchall("SELECT name, freq, mode FROM fdradio WHERE name = '%s'", $this->quote($name));

		// we don't want to pop an error if the radio doesn't exist.
		$return = [true, ["noradio" => true]];

		if ($r[0]["name"] == $name) $return = [ true, $r[0] ];

		return $return;


		
	}


	public function servertime(): array {
		return [true, microtime(true)];
	}

	
	public function callbook(string $call): array {
		$r = $this->fetchall(
			"SELECT csign, name, city, state FROM fdcallbook WHERE csign = '%s'",
			$this->quote($call)
		);

		$return = ($r[0]) ? $r[0] : [ "notfound" => true ];


		return([ true, $return ]);
	}

	public function whoami(): array {
		return([
			true,
			[
				"call" => \config::$settings->callsign,
				"exchange" => \config::$settings->exchange,
				"debug" => \config::$debug
			]
		]);

	}


	/* json processing function */


	public function process(): null {
		
		$incoming = json_decode(
			file_get_contents("php://input")
		);


		if ($incoming == false) return null;

		$output = [];

		
		foreach ((array) $incoming as $id=>$request) {

			$cmd = $request->cmd;
			$arg = (@$request->arg) ? $request->arg : [];

			if($cmd == "process") continue;

			if (method_exists($this, $cmd)) {

				try {
					try {
						// this is the part where things are done
						$done = $this->$cmd(...$arg);
					} catch (\logger_exception $exc) {
						$done = [ false, 'PHP Exception Thrown: '. $exc->getMessage() ];
					}
				} catch (\Error $err) {
					$done = [ false, 'PHP code error: '. $err->getMessage() ];
				}


				$output[$id] = [
					"cmd"     => $cmd,
					"result"  => $done[0],
					"data"    => $done[1]
				];

			} else {
				$output[$id] = [
					"cmd"    => $cmd,
					"result" => false,
					"data"   => "command does not exist"
				];

			}
		}
		echo json_encode($output, (\config::$debug) ? JSON_PRETTY_PRINT : null);
		return null;

	}

}
