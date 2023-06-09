<?php

namespace loggerlink;

class propwatch extends loggerlink {

	public static string $opts = "p:";

	protected string $dir;
	protected int    $wait = 500000;

	protected readonly array $pipespec;

	public function __construct(\loggerlink\naive_getopt $args) {

		$this->pipespec = [
			0 => array("file", "/dev/null", "w"), // stdin
			1 => array("pipe", "w"), // stdout
			2 => array("pipe", "w") // stderr
		];

		$this->options($args);

		if (!$args->_string("p")) $this->bomb("directory not specified");


		if (!is_dir($args->p)) $this->bomb("destination not a directory");
		$this->dir = $args->p;

		$this->whoami();
		$this->go();

	}


	/* FIXME I fscking hate that I'm doing it this way.   For some reason
 	 * I can't figure out how to get PHP to listen for changes to the file
 	 * using fopen, fseek, and whatnow.
 	 *
 	 * I feel like I got close, but one evening is all I've got to implement
 	 * this, so here is.
 	 */

	protected function go() {

		$file = $this->dir . '/ALL.TXT';

		$this->debug("looking for '$file'");
		while (!is_readable($file)) {
			echo "-- waiting for ALL.TXT --\n";
			sleep(5);
		}

		$process = proc_open(
			[
				"/usr/bin/tail",
				"-n0",
				"-f",
				escapeshellcmd($file)
			],
			$this->pipespec,
			$pipes
		);

		if ($process === false) $this->bomb("couldn't open tail process");
		stream_set_blocking($pipes[1], false);

		$output = [];

		do {

			// get line
			$line = fgets($pipes[1]);

			if ($line !== false) {
				$output[] = $line;
				echo $line;
			} else {
				if (count($output) > 0) {
					printf("received %d records, sending note.\n", count($output));

					list($call) = $this->call(
						"user",
						[[
							"cmd" => "note",
							"arg" => [
								"{Heard from WSJT-X:\n".join("", $output)."}",
								$this->name
							]
						]]
					);

					if ($call->result !== true) {
						$this->bomb("couldn't post note to logger");
					}
					$output = [];
					
				};
			}


			if (proc_get_status($process)["running"] !== true)
				$this->bomb("connection to tail died.");

			usleep($this->wait);


		} while (1);


	}

}
