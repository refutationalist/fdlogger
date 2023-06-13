<?php

namespace loggerlink;

class wsjtx extends loggerlink {
	use db;
	protected string $dir;
	protected int    $wait = 60;

	public function __construct(\loggerlink\naive_getopt $args) {

		$this->options($args);
		if (!$args->_string("x")) $this->bomb("directory not specified");
		if (!is_dir($args->x)) $this->bomb("destination not a directory");
		$this->dir = $args->x;

		$this->file = $this->dir . '/db.sqlite';
		$this->whoami();
		$this->go();

	}

	public function go() {

		$db_id = 0;

		$this->debug("looking for '$this->file'");
		while (!is_readable($this->file)) {
			echo "-- waiting for log database --\n";
			sleep(5);
		}

		try {
			list($row) = $this->fetchall("SELECT MAX(id) AS id FROM cabrillo_log_v2");
			$this->dbclose();
		} catch (\Exception $e) {
			$this->bomb("couldn't open database: " . $e->getMessage());
		}

		$this->debug(print_r($row, true), 2);

		$db_id = $row["id"];

		echo "max id is: $db_id\n";

		do {

			// just to keep things clean, we want stay connected to the db as little as possible
			$in = $this->fetchall("SELECT * FROM cabrillo_log_v2 WHERE id > %f", $db_id);
			$this->dbclose();

			$this->debug("new entires: ".count($in));


			foreach ($in as $entry) {
				$this->debug("new entry: ".print_r($in, true), 2);

				// Phase 1: Parse exchange and section
				if (!preg_match("/\b([0-9]+)([a-f]b?)\b/i", $entry["exchange_rcvd"], $exchange)) {
					$this->nonfatal("couldn't parse exchange", $entry);
					continue;
				}
				$this->debug("exchange catch: ".print_r($exchange, true), 2);

				if (!preg_match("/\b([a-z]{2,3})\b/i", $entry["exchange_rcvd"], $section)) {
					$this->nonfatal("couldn't parse section from record", $entry);
					continue;
				}
				$this->debug("section catch: ".print_r($section, true), 2);
				

				// Phase 2: parse mode
				$this->debug("preparse mode: ".$entry["mode"]);
				if (in_array($entry['mode'], ['FT8', 'JT8', 'FT4', 'JT65'])) {
					$mode = $entry['mode'];
				} else {
					$mode = 'DIG';
				}
				$this->debug("final mode: $mode");

				$tolog =  [
					$entry["call"], // 0
					(int) $exchange[1], // 1 
					$exchange[2], // 2
					$section[1], // 3
					(int) $entry["frequency"], // 4
					$mode, // 5
					$this->name, // 6
					null, // 7
					$entry["when"] // 8
				];

				$todupe = [
					$tolog[0],
					$tolog[4],
					$tolog[5]
				];

				$dupe = $this->call("user", [[
					'cmd' => 'dupe',
					'arg' => $todupe
				]]);

				if ($dupe[0]->result != true) { // okay, cool, log
					$result = $this->call('user', [[
						'cmd' => 'add',
						'arg' => $tolog
					]]);

					if ($result[0]->result == true) {
						echo "New Log: ".join(', ', $tolog)."\n";
					} else {
						$this->nonfatal("failed to submit", $entry);
					}
				} else {  // make a note for a dupe
						
					$say = sprintf("WSJT-X DUPE: Call: %s, Freq: %d, Mode: %s", $tolog[0], $tolog[4], $tolog[5]);

					$this->call('user', [[
						'cmd' => 'note',
						'arg' => [ $say, $this->name ]
					]]);
					echo "##### $say\n";

				}

			}
			$this->debug("sleeping now: ", $this->wait);
			sleep($this->wait);
		} while(1);

	}

	protected function nonfatal(string $txt, array $entry) {

		$say = "Warning! WSJT-X Logging Failure Must be Done By Hand!\n".print_r($entry, true);
		echo "#####\n$say\n#####\n";

		$this->call([[
			$say,
			$this->name
		]]);
	}


}
