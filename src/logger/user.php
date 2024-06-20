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

		string $handle,
		null|string $notes = "",
		null|int $time = null
	): array {

		// arb strings
		$call   = $this->cleanstring($call);
		$handle = $this->cleanstring($handle);

		// test for valid class
		if (!$this->inclasses($class)) return([false, "add: invalid class"]);

		// test for valid zone
		if (!$this->inzones($zone)) return([false, "add: invalid zone"]);

		// test for valid mode
		if (!$this->inmodes($mode)) return([false, "add: invalid mode"]);

		// check for dupe
		if (@count((array) $this->getdupe($call, $freq, $mode)) > 0) return([false, "add: this is a dupe"]);

		// notes!
		$sql_notes = (trim($notes) !== "") ? "'".$this->quote($notes)."'" : 'NULL';

		// handle
		if (trim($handle) === "")
			return([false, 'add: need a handle']);

		$time = ($time == null) ? 'NULL' : "FROM_UNIXTIME($time)";




		if ($this->query(
			"INSERT INTO fdlog SET ".
			"csign = '%s', tx = %d, class = '%s', zone = '%s', freq = %d, mode = '%s', ".
			"handle = '%s', notes = %s, logged = %s",
			$call, $tx, $this->quote($class), $this->quote($zone), $freq, $this->quote($mode),
			$handle, $sql_notes, $time
		)) {
			return([true, 'add: submitted']);
		} else {
			return([false, 'add: db commit error']);
		}

	}

	public function note(
		string $notes, string $handle
	): array {
		$handle = $this->cleanstring($handle);

		// notes can carry arbitrary data, client needs to handle that

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
		return([true, $rows]);

	}

	public function since(int $logid, int $noteid): array {

		$rows = $this->fetchall(
			"SELECT * FROM fdlogdisplay WHERE (kind = 'log' AND id > %d) OR (kind = 'note' AND id > %d)",
			$logid,
			$noteid
		);

		return( [ true, $rows ] );

	}

	public function dupe(string $call, string|int $freq, string $mode): array {
		$freq = intval($freq);

		return([
			true,
			$this->getdupe($call, $freq, $mode)
		]);

	}

	protected function getdupe(string $call, string|int $freq, string $mode): null|array {
		return @$this->fetchall(
			"SELECT csign, exch, mode, logged, band FROM fdlogdisplay ".
			"WHERE csign = '%s' ".
			"AND band = (SELECT code FROM fdband WHERE low <= %d AND high >= %d LIMIT 1) ".
			"AND mode IN((SELECT code FROM fdmode WHERE cab = (SELECT cab FROM fdmode WHERE code = '%s')))",
			$this->quote($call),
			$freq, $freq,
			$this->quote($mode)
		)[0];
	}

}
