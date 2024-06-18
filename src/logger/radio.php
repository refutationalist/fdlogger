<?php

namespace logger;


class radio extends base {


	public function set(
		string $name,
		int    $freq,
		string $mode
	): array  {

		$name = $this->cleanstring($name);

		// this will toss
		$this->query(
			"INSERT INTO fdradio(name, freq, mode) VALUES('%s', %d, '%s') ".
			"ON DUPLICATE KEY UPDATE freq = %d, mode = '%s', logged = NOW()",
			$this->quote($name),
			$freq,
			$this->quote($mode),
			$freq,
			$this->quote($mode)
		);

		return([true, "radio: set"]);

	}

	public function process(): null {

		if (isset($_GET["name"])) {

			$r = $this->set(
				$_GET["name"],
				intval($_GET["freq"]),
				$_GET["mode"]
			);

			echo ($r[0] == true) ? 1 : 0;
			return null;

		} else {
			return parent::process();
		}


	}

}
