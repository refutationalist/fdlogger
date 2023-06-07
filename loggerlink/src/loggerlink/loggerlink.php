<?php

namespace loggerlink;


class loggerlink {

	protected string $url;
	protected string $name;
	protected string $mycall;
	protected string $myexchange;

	public bool $debug;



	public function options(string $extra) {

		$options = getopt("u:r:hv".$extra);

		if (isset($options["h"])) $this->help();
		$this->debug = isset($options["v"]);

		if (!isset($options["r"])) $this->bomb("no radio name");
		$this->name = $options["r"];
		$this->debug("radio name: $this->name");
		
		if (!isset($options["u"])) $this->bomb("no logger url");
		$this->url = $options["u"];
		$this->debug("url: $this->url");
		
		return $options;

	}

	public function whoami():null {

		list($data) = $this->call("user", [[ "cmd" => "whoami" ]]);

		if ($data->result == false || $data == null)
			$this->bomb("whoami call failed");

		$this->mycall     = $data->data->call;
		$this->myexchange = $data->data->exchange;

		echo "Starting Up! We are [$this->mycall], running [$this->myexchange].\n";

		return null;


	}



	protected function call(string $mode, array $command): array|null {

		$url = sprintf("%s/api?a=%s", $this->url, $mode);
		$this->debug("call url: ".$url);

		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt(
			$curl,
			CURLOPT_HTTPHEADER,
			[
				'Accept: application/json',
				'Content-Type: application/json'
			]
		);
		curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($command));
		$rawtext = curl_exec($curl);

		if ((int) curl_getinfo($curl, CURLINFO_RESPONSE_CODE) != 200)
			$this->bomb("call to logger has bad response code");

		curl_close($curl);

		$rawjson = json_decode($rawtext);

		return ($rawjson == null) ? null : (array) $rawjson;


	}


	protected function debug(string $str): null {
		if ($this->debug) echo "## $str\n";
		return null;
	}


	protected function bomb(string $error, int $errno = 1): null {
		echo "loggerlink: $error\n";
		exit($errno);
		return null; // nyaaa
	}

	function help(int $exit = 0): null {

		echo <<<EndHELP
loggerlink: send radio data to N9MII's FD logger

Required Settings:
     -u <url>            - URL of N9MII logger
     -r <name>           - the name of your radio as it will
                           appear in your logger

Radio Follow Mode (-f):
     -d <host>:<port>    - host and port of rigctld server
                           defaults to localhost and 4532
     -w <int>            - wait <int> seconds between updates
                           defaults to 3
     -n                  - do not send modulation information

WSJTX Logging (-w):

WSJT-X Propagation Monitoring (-p):

Supplemental Settings:
     -h                  - this help
     -v                  - print debugging info



EndHELP;
		exit($exit);
	}


	


}

