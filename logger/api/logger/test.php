<?php

namespace logger;

final class test extends base {

	protected array $calls = array();
	protected array $bands = array();
	protected array $modes = array();


	public function __construct() {
		// where we're going, we're absolutely going to need roads.
		ini_set('memory_limit', -1);
		parent::__construct();

		$this->bands = $this->fetchall("SELECT * FROM fdband WHERE code != 'none'");
		$this->modes = $this->getmodes();

	}

	protected function getcalls() {
		if (count($this->calls) == 0) 
			$this->calls = $this->fetchall("SELECT csign FROM fdcallbook");
	}


	/* so linux, much arch.  wow. */
	protected function random_phrase(int $len = 10): string {
		$string = `shuf -n $len /usr/share/dict/words`;
		$string = preg_replace("/\s/", " ", trim($string));
		return $string;
	}


	public function create_junk_logs(int $logs = 500, $modnote = 20) {

		// get data for fast random
		$this->getcalls();
		$zones = $this->fetchall("SELECT * FROM fdzone");
		$class = $this->fetchall("SELECT * FROM fdclass");
		$end   = time();
		$start = $end - 1209600;

		$times = [];
		for ($i = 0 ; $i < $logs ; $i++) $times[] = rand($start, $end);
		sort($times, SORT_NUMERIC);

		$entries = [];
		for ($i = 0 ; $i < $logs ; $i++) {
			$band = $this->bands[ array_rand($this->bands) ];

			$entries[] = sprintf(
				"('%s', %d, %d, FROM_UNIXTIME(%d), '%s', '%s', '%s', '%s', %s)",
				$this->calls[ array_rand($this->calls) ]["csign"], // callsign
				rand($band["low"], $band["high"]), // frequency
				rand(1,20), // tx
				$times[$i], // time
				$class[ array_rand($class) ]["code"], // class
				$this->modes[ array_rand($this->modes) ], // mode
				$zones[ array_rand($zones) ]["code"], // zone
				'Randmon'.$i, //handle
				($i % $modnote) ? 'NULL' : "'".$this->quote($this->random_phrase())."'"
			);

		}

		foreach (array_chunk($entries, 100) as $part) {
			$this->query(
				"INSERT INTO fdlog(csign, freq, tx, logged, class, mode, zone, handle, notes) VALUES".
				join(",\n", $part)
			);
		}
	
	}

	public function create_junk_notes($notes = 50) {
		$end   = time();
		$start = $end - 1209600;


		$times = [];
		for ($i = 0 ; $i < $notes ; $i++) $times[] = rand($start, $end);
		sort($times, SORT_NUMERIC);

		$entries = [];
		for ($i = 0 ; $i < $notes ; $i++) {

			$entries[] = sprintf(
				"('%s', '%s', FROM_UNIXTIME(%d))",
				$this->quote($this->random_phrase(20)),
				'Ranoted'.$i,
				$times[$i]
			);
		}

		foreach (array_chunk($entries, 10) as $part) {
			$this->query(
				"INSERT INTO fdnote(notes, handle, logged) VALUES".
				join(",\n", $part)
			);
		}
		
	}

	public function update_junk_radio(string $name) {

		$band = $this->bands[ array_rand($this->bands) ];
		$freq = rand($band["low"], $band["high"]); // frequency
		$mode = $this->modes[ array_rand($this->modes) ];

		echo "$name: $freq / $mode\n";
		
		
		$this->query(
			"INSERT INTO fdradio(name, freq, mode) VALUES('%s', %d, '%s') ".
			"ON DUPLICATE KEY UPDATE freq = %d, mode = '%s', logged = NOW()",
			$this->quote($name),
			$freq,
			$this->quote($mode),
			$freq,
			$this->quote($mode)
		);
	}


}
