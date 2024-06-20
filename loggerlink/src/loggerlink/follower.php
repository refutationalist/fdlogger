<?php

namespace loggerlink;


class follower extends loggerlink {

	private $s;

	protected bool   $do_mode = true;
	protected string $host    = 'localhost';
	protected int    $port    = 4532;

	public function __construct(\loggerlink\naive_getopt $args) {

		$this->options($args);

		if ($args->_string("d")) {
			@list($host, $port) = explode(":", $args->d);
			$host = trim($host);
			$port = intval($port);

			if ($host != "") $this->host = $host;

			if ($port != 0) $this->port = $port;

		}
		$this->debug("host: $this->host / port: $this->port");

		$this->do_mode = !$args->_test("n");
		$this->debug("send mode: " . (($this->do_mode) ? "YES" : "NO"));

		$this->debug("connecting to rigctl");
		$this->rigctl_open();

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
			$this->debug("rigctl get:\n".print_r($get, true), 3);

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
				"cmd" => "set",
				"arg" => [ $this->name, $freq, $mode ]
			]]);

			if ($ret->result != true) $this->bomb("API call fail");

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


	protected function rigctl_open() {
		$this->s = fsockopen($this->host, $this->port, $eno, $err, 1);
		if (!$this->s) {
			$this->debug("rigctl opening fail: $err\n");
			$this->bomb("could not connect to rigctl\n");
		}
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
