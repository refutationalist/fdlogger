<?php

namespace loggerlink;


class follower extends loggerlink {

	private $s;

	protected int    $wait    = 3;
	protected bool   $do_mode = true;
	protected string $host    = 'localhost';
	protected int    $port    = 4532;

	public function __construct() {

		$options = $this->options("d:w:n");

		if (@isset($options["d"])) {
			list($host, $port) = explode(":", $options["d"]);
			$host = trim($host);
			$port = intval($port);

			if ($host != "") $this->host = $host;

			if ($port != 0) $this->port = $port;

		}
		$this->debug("host: $this->host / port: $this->port");

		$this->wait = (int) (isset($options["w"])) ? intval($options["w"]) : 3;
		if ($this->wait == 0) $this->bomb("invalid wait time");
		$this->debug("wait: $this->wait");

		$this->do_mode = !isset($options["n"]);
		$this->debug("send mode: " . (($this->do_mode) ? "YES" : "NO"));

		$this->debug("connecting to rigctl");
		try {
			$this->rigctl_open();
		} catch (\Exception $e) {
			$this->bomb("couldn't connect to rigctld: ".$e->getMessage());
		}

		$this->whoami();
		$this->go();
		
	}

	public function __destruct() {
		fclose($this->s);
	}

	protected function go() {

		$change = "";

		do {

			$get = $this->rigctl();
			if (!$get) $this->bomb("lost connection to rigctl");

			list($freq, $mode, $pass) = $get;

			if ($freq == 0) $this->bomb("frequency unreadable");

			if ($this->do_mode) {
				$this->debug("preparse mode: $mode");
				if (!in_array($mode, ['CW', 'AM', 'USB', 'LSB', 'FM'])) {

					switch ($mode) {

						case "CWR":
							$mode = "CW";
							break;

						case "PKTUSB":
						case "PKTLSB":
							$mode = "DIG";
							break;

						case "WFM":
							$mode = "FM";
							break;

						case "RTTYR":
							$mode = "RTTY";
							break;


						default:
							$mode = "UNK";
							break;
					}

				}	

			} else {
				$mode = "UNK";
			}


			list($ret) = $this->call("radio", [[
				"cmd" => "name",
				"arg" => [ $this->name, $freq, $mode ]
			]]);

			if (!$ret->result != true) {
				$this->debug("api return: ".json_encode($ret));
				$this->bomb("API call fail");
			}

			if ($change != $freq.$mode) {
				printf(
					"Radio [%s]: freq(%d) mode(%s)\n",
					$this->name, $freq, $mode
				);
				$change = $freq.$mode;
			}


			sleep($this->wait);
		} while(true);



	}


	protected function rigctl_open(string $host = 'localhost', int $port = 4532) {
		$this->s = fsockopen($host, $port, $eno, $e, 2);
		if (!$this->s) throw new Exception("yikes");
	}

	protected function rigctl(): null|array {

		if (!fwrite($this->s, "fm\n")) return null;

		$freq = fgets($this->s);
		$mode = fgets($this->s);
		$pass = fgets($this->s);

		if ($freq == false || $mode == false || $pass == false) return null;

		return([
			intval(trim($freq)),
			trim($mode),
			intval(trim($pass))
		]);

	}

}
