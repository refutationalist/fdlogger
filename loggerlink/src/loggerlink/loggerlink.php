<?php

namespace loggerlink;


class loggerlink {

	protected string $url;
	protected string $name;
	protected string $mycall;
	protected string $myexchange;

	public int $debug = 0;



	public function options(\loggerlink\naive_getopt $args):null {

		$this->debug = $args->_test("v");

		if ($args->_bool("v")) {
			$this->debug = 1;
		} else if ($args->_int("v")) {
			$this->debug = $args->v;
		}
		echo "debug: "; var_dump($this->debug);


		if (!$args->_string("r")) $this->bomb("no radio name");
		$this->name = $args->r;
		$this->debug("radio name: $this->name");
		
		if (!$args->_string("u")) $this->bomb("no logger url");
		$this->url = $args->u;
		$this->debug("url: $this->url");

		return null;
		

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

		$json = json_encode($command);

		$this->debug("call url: ".$url);
		$this->debug("json send: $json", 2);

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
		$this->debug("json recv: $rawtext\n", 2);

		if ((int) curl_getinfo($curl, CURLINFO_RESPONSE_CODE) != 200)
			$this->bomb("call to logger has bad response code");

		curl_close($curl);

		$rawjson = json_decode($rawtext);

		return ($rawjson == null) ? null : (array) $rawjson;


	}


	protected function debug(string $str, int $level = 1): null {
		if ($this->debug >= $level) echo "## $str\n";
		return null;
	}


	protected function bomb(string $error, int $errno = 1): null {
		echo "loggerlink: $error\n";
		exit($errno);
		return null; // nyaaa
	}



}

