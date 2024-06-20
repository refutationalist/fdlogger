<?php


namespace loggerlink;

/** 
   id  frequency  mode  when        call    exchange_sent  exchange_rcvd
   --  ---------  ----  ----------  ------  -------------  -------------
   1   14081819   FT4   1687675672  KL7TC   7A WWA         1D AK        
   2   14081819   FT4   1687675807  KT4Q    7A WWA         4A NC        
 **/


class entry {
	public string   $call; // callsign
	public int      $tx; // number of transmitters
	public string   $class; // class of station
	public string   $zone; // location zone
	public int      $freq; // frequency in hertz
	public string   $mode; // modulation mode of qso
	public int|null $when; // when the QSO was made.  defaults to now.

	public function readable(): string {

		return sprintf(
			"CALL: %s, TX: %d, CLASS: %s, ZONE: %s, FREQ: %d, MODE: %s, WHEN: %s",
			$this->call,
			$this->tx,
			$this->class,
			$this->zone,
			$this->freq,
			$this->mode,
			( ($this->when == null) ? "UNSET" : $this->when )
		);


	}

	public function dupe(): array {
		return [ $this->call, $this->freq, $this->mode ];
	}

	public function to_log(string $name, null|string $notes = null): array {
		return [
			$this->call,
			$this->tx,
			$this->class,
			$this->zone,
			$this->freq,
			$this->mode,
			$name,
			$notes,
			$this->when
		];
	}


	

	static public function from_wsjtxdb(array $entry): null|entry {

		$new = new entry();


		foreach (['mode', 'frequency', 'call', 'when', 'exchange_rcvd'] as $field) {
			if (!isset($entry[$field])) {
				return null;
			}
		}

	
		// Phase 1: Parse exchange and section
		if (!preg_match("/\b([0-9]+)([a-f]b?)\b/i", $entry["exchange_rcvd"], $exchange)) {
			return null;
		}

		if (!preg_match("/\b([a-z]{2,3})\b/i",      $entry["exchange_rcvd"], $section)) {
			return null;
		}

		if (in_array($entry['mode'], ['FT8', 'JT8', 'FT4', 'JT65'])) {
			$new->mode = $entry['mode'];
		} else {
			$new->mode = 'DIG';
		}

		$new->tx = (int) $exchange[1];
		$new->class = $exchange[2];
		$new->zone = $section[1];

		$new->call = $entry["call"];
		$new->freq = (int) $entry["frequency"];

		$new->when = $entry["when"];

		return $new;


	}


}
