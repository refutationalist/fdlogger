<?php

namespace loggerlink;

class wsjtx extends loggerlink {
	use db;
	protected string $dir;
	protected string $errorfile;

	public function __construct(\loggerlink\naive_getopt $args) {

		$this->options($args);
		$this->whoami();
		
		if (!$args->_string("x")) $this->bomb("directory not specified");
		if (!is_dir($args->x)) $this->bomb("destination not a directory");

		if (trim(exec("pgrep wsjtx")) != "") $this->bomb("wsjtx is running.");

		$home = getenv('HOME');
		$this->debug("home: ".$home);

		// manage moving the sqlite file

		$cachedir = $home . "/.cache/loggerlink";

		if (!is_dir($cachedir)) {
			if (!mkdir($cachedir, 0755, true)) $this->bomb("can't create dir: $cachedir");
		}


		$origfile = $args->x.'/db.sqlite';
		$this->debug("origfile: ".$origfile);
		if (!file_exists($origfile)) $this->bomb("no QSO log");

		do {
			$this->file = $cachedir."/db.sqlite.".time();
		} while (file_exists($this->file));

		$this->debug("new cache file: {$this->file}");

		if (!rename($origfile, $this->file)) $this->bomb("QSO log move failed");

		// find a blank errorfile

		do {
			$this->errorfile = $home . "/loggerlink-wsjtx-error-".time().".txt";
		} while (file_exists($this->errorfile));
		$this->debug("error file: ".$this->errorfile);

		$this->go();

	}

	public function go() {

		$noparse = [];
		$failed  = [];
		$isdupe    = [];

		// just to keep things clean, we want stay connected to the db as little as possible
		$in = $this->fetchall("SELECT * FROM cabrillo_log_v2");

		$this->debug("new entires: ".count($in));


		foreach ($in as $entry) {
			$this->debug("new entry: ".join(', ', $entry));

			// Phase 1: Parse exchange and section
			if (!preg_match("/\b([0-9]+)([a-f]b?)\b/i", $entry["exchange_rcvd"], $exchange)) {
				$noparse[] = $entry;
				continue;
			}
			$this->debug("exchange catch: ".join(', ', $exchange), 2);

			if (!preg_match("/\b([a-z]{2,3})\b/i", $entry["exchange_rcvd"], $section)) {
				$noparse[] = $entry;
				continue;
			}
			$this->debug("section catch: ".join(', ', $section), 2);
			

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

			/*
			$todupe = [
				$tolog[0],
				$tolog[4],
				$tolog[5]
			];
			 */

			$dupe = $this->call("user", [[
				'cmd' => 'dupe',
				//'arg' => $todupe
				'arg' => [ $tolog[0], $tolog[4], $tolog[5] ]
			]]);

			if ($dupe[0]->data != true) { // okay, cool, log
				$result = $this->call('user', [[
					'cmd' => 'add',
					'arg' => $tolog
				]]);

				if ($result[0]->result == true) { // yay, logged
					echo "New Log: ".join(', ', $tolog)."\n";
				} else { // oh no, it failed for some reason
					$failed[] = $entry;
				}
			} else {  // make a note for a dupe
				$isdupe[] = $entry;
			}

		}

		if (count($noparse) > 0 || count($isdupe) > 0 || count($failed) > 0) {
			$this->debug("there are failed records");


			$report = "";

			if (count($noparse) > 0) {
				$report .= "[noparse] These records could not be parsed:\n\n";
				foreach ($noparse as $p) $report .= join(",", $p)."\n";
				$report .= "\n\n";
			}

			if (count($failed) > 0) {
				$report .= "[failed] These records could not be posted:\n\n";
				foreach ($failed as $p) $report .= join(",", $p)."\n";
				$report .= "\n\n";
			}

			if (count($isdupe) > 0) {
				$report .= "[dupe] These records are dupes in the log:\n\n";
				foreach ($isdupe as $p) $report .= join(",", $p)."\n";
				$report .= "\n\n";
			}

			echo $report;

			if (!file_put_contents($this->errorfile, $report))
				echo "Warning!  Could not post error file!  Warn the sysadmin (likely Sam)!\n";

			$notetxt = sprintf(
				"WSJT-X Error Summary: %d dupes, %d parse failures, %d post failures.  Check error report.",
				count($isdupe),
				count($noparse),
				count($failed)
			);


			$errres = $this->call('user', [[
				'cmd' => 'note',
				'arg' => [ $notetxt, $this->name ]
			]]);

			if ($errres[0]->result != true)
				echo "Warning!  Could not post note!  Warn the sysadmin (likely Sam)!\n";

		}

	}

}
