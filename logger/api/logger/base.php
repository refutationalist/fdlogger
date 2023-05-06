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

	public function servertime(): array {
		return [true, microtime(true)];
	}

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
	public function zones(): array {
		return [ true, $this->getzones() ];
	}

	protected function getclasses(): array {

		$return = [];

		foreach ($this->fetchall("SELECT * FROM fdclass ORDER BY code") as $c) {
			$return[ $c["code"] ] = $c["text"];
		}

		return $return;

	}
	public function classes(): array {
		return [ true, $this->getclasses() ];
	}

	public function process(): null {
		
		$incoming = json_decode(
			file_get_contents("php://input")
		);


		if ($incoming == false) return null;

		$output = [];

		
		foreach ((array) $incoming as $id=>$request) {

			$cmd = $request->cmd;
			$arg = $request->arg;

			if($cmd == "process") continue;

			if (method_exists($this, $cmd)) {

				try {
					try {
						// this is the part where things are done
						$done = $this->$cmd(...$arg);
					} catch (logger\Exception $exc) {
						$done = [ false, 'PHP Exception Thrown: '. $exc->getMessage() ];
					}
				} catch (Error $err) {
					$done = [ false, 'PHP code error: '. $err->getMessage() ];
				}


				$output[$id] = [
					"cmd"  => $cmd,
					"res"  => $done[0],
					"data" => $done[1]
				];

			} else {
				$output[$id] = [
					"cmd" => $cmd,
					"res" => false,
					"data" => "command does not exist"
				];

			}
		}
		echo json_encode($output, (\config::$debug) ? JSON_PRETTY_PRINT : null);
		return null;

	}

}
