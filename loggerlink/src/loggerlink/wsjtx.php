<?php

namespace loggerlink;

const LOG_GOOD = 0;
const LOG_DUPE = 1;
const LOG_FAIL = 2;

class wsjtx extends loggerlink {
	use db;
	protected string $dir;
	protected $errorlog; // how the hell do I typehint this?

	public function __construct(\loggerlink\naive_getopt $args) {


		if (!class_exists("SQLite3")) {
			echo "{$argv[0]}: requires sqlite3 extension.\n";
			exit(98);
		}


		$this->options($args);
		$this->whoami();

		if (!$args->_string("x")) $this->bomb("directory not specified");
		if (!is_dir($args->x)) $this->bomb("destination not a directory");

		$home = getenv('HOME');
		$this->debug("home: ".$home);

		$this->file = $args->x.'/db.sqlite';
		$this->debug("file: ".$this->file);

		if (!file_exists($this->file)) $this->bomb("no QSO log");

		$this->debug("WSJT-X sqlite3 file: {$this->file}");

		// find a blank errorfile
		do {
			$errorfile = $home . "/loggerlink-wsjtx-error-".time().".txt";
		} while (file_exists($errorfile));
		$this->debug("error file: ".$errorfile);
		$this->errorlog = fopen($errorfile, 'w');
		if ($this->errorlog == false) $this->bomb("can't open error log");

		$this->go();

	}

	public function __destruct() {
		fclose($this->errorlog);
	}


	protected function error(string $txt) {
		fwrite($this->errorlog, "$txt\n");
		$this->debug($txt);
	}

	protected function add_log(entry $e, bool $prechecked = false) {

		if (!$prechecked) {
			$dupe = $this->call("user", [[
				'cmd' => 'dupe',
				'arg' => $e->dupe()
			]]);

			if ($dupe[0]->data !== null) {
				$this->error("dupe found: ". $e->readable());
				return false;
			}
		}

		$result = $this->call('user', [[
			'cmd' => 'add',
			'arg' => $e->to_log($this->name)
		]]);
		
		if ($result[0]->result == true) { // yay, logged
			echo "New Log: ".$e->readable()."\n";
		} else { // oh no, it failed for some reason
			$this->note("Could not log loggable WSJT-X QSO! [".$e->readable()."]");
		}
		

	}

	protected function initial_check(): int {


		// just to keep things clean, we want stay connected to the db as little as possible
		$this->debug("beginning intial log check");
		$in = $this->fetchall("SELECT * FROM cabrillo_log_v2");

		$this->debug("preexisting entires: ".count($in));

		$send = [];
		$logs = [];
		$id   = 0;

		
		foreach ($in as $entry) {

			$e = \loggerlink\entry::from_wsjtxdb($entry);

			if ($e !== null) {
				$logs[] = $e;
			} else {
				$this->error("ERROR, Failed to parse: ".join(', ', $entry));
			}

			if ($entry["id"] > $id) $id = $entry["id"];

		}

		foreach ($logs as $idx=>$e) {
			$send[] = ['cmd' => 'dupe', 'arg' => $e->dupe()];
		}

		$recv = $this->call('user', $send);
		$dupes = 0;

		if ($recv) {

			foreach ($recv as $idx=>$check) {

				if ($check->data === null) {
					$this->error("Unlogged Entry Found: ". $logs[$idx]->readable());
					$this->add_log($logs[$idx], true);
				} else {
					$dupes++;
				}

			}

		}

		$this->debug("initial check complete.  dupes found: ".$dupes.", highest id: ".$id);
		return $id;


	}

	protected function note(string $intxt) {

		$this->error("POSTED NOTE: $intxt");
		
		$errres = $this->call('user', [[
			'cmd' => 'note',
			'arg' => [ $intxt, $this->name ]
		]]);

		if ($errres[0]->result != true)
			$this->error("WARNING!  Could not post note!  Warn the sysadmin (likely Sam)!");
	}

	protected function watch(int $id) {

		$this->debug("beginning db watch");

		do {
			$in = $this->fetchall("SELECT * FROM cabrillo_log_v2 WHERE id > %d", $id);
			foreach ($in as $entry) {

				$e = \loggerlink\entry::from_wsjtxdb($entry);

				if ($e !== null) {
					$logs[] = $e;
				} else {
					$this->error("ERROR, Failed to parse: ".join(', ', $entry));
				}

				if ($entry["id"] > $id) $id = $entry["id"];

				$this->add_log($e);
			}
			sleep($this->wait);

		} while(1);


	}

	public function go() {
		$this->watch($this->initial_check());

	}

}
