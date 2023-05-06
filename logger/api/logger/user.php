<?php

namespace logger;


class user extends base {

	public function add(
		string $call,
		int    $tx,
		string $class,
		string $zone,

		int    $freq,
		string $mode,
		int    $power,

		string $handle,
		null|string $notes = ""
	): array {


		// test for valid class
		$vc = false;
		foreach ($this->getclasses() as $c=>$crap) {
			if ($class === $c) {
				$vc = true;
				break;
			}
		}

		if ($vc == false) return([false, "add: invalid class"]);


		// test for valid zone
		$vz = false;
		foreach ($this->getzones() as $z=>$crap) {
			if ($zone === $z) {
				$vz = true;
				break;
			}
		}

		if ($vz == false) return([false, "add: invalid zone"]);

		// test for valid mode
		if (!in_array($mode, \config::$modes)) return([false, "add: invalid mode"]);

		
		// do I test for valid callsign?
		// for now, no.

		// notes!
		$sql_notes = (trim($notes) !== "") ? "'".$this->quote($notes)."'" : 'NULL';

		// handle
		if (trim($handle) === "") 
			return([false, 'add: need a handle']);

		// everything makes sense.   let's go!
		
		if ($this->query(
			"INSERT INTO fdlog SET ".
			"csign = '%s', tx = %d, class = '%s', zone = '%s', freq = %d, mode = '%s', ".
			"power = %d, handle = '%s', notes = %s",
			$call, $tx, $this->quote($class), $this->quote($zone), $freq, $this->quote($mode),
			$power, $handle, $sql_notes
		)) {
			return([true, 'add: submitted']);
		} else {
			return([false, 'add: db commit error']);
		}

	}

	public function note(
		string $notes, string $handle
	): array {
		if (trim($handle) === "") 
			return([false, 'need a handle']);
		if (trim($notes) === "") 
			return([false, 'note is empty']);

		if ($this->query(
			"INSERT INTO fdnote(handle, notes) VALUES('%s', '%s')",
			$this->quote($handle),
			$this->quote($notes)
		)) {
			return([true, "note: submitted"]);
		} else {
			return([false, "note: db commit error"]);
		}
	}

	public function get(int $lines): array {

		if ($lines != 0) {
			$post = " LIMIT $lines";
		}

		$rows = $this->fetchall("SELECT * FROM fdlogdisplay" . $post);

		if ($rows == false) {
			return([false, "get: db query error"]);
		} else {
			return([true, $rows]);
		}


	}

}
