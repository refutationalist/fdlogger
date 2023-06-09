<?php

namespace loggerlink;


class naive_getopt {


	function __construct(array $override = null) {

		if ($override != null) {
			$in = $override;
		} else {
			global $argv;
			$in = $argv;
		}

		$this->__name = array_shift($in);
		$this->__end  = null;


		$last = null;

		foreach ($in as $idx=>$part) {

			if ($part == "--") { // we hit two dashes alone, we're done looking.

				$this->__end = join(" ", array_slice($in, $idx + 1));

				break;
			} else if (
				strlen($part) > 2 &&
				$part[0] == $part[1] &&
				$part[0] == '-'
			) { // we found two dashes
				$stripped = substr($part, 2);

				if (method_exists($this, $stripped)) continue;
				if (in_array($part, ["__name", "__end"])) continue;

				if (strpos($stripped, '=')) { // do we have a value?
					list($opt, $val) = explode("=", $stripped);
					$this->$opt = $val;
					$last = $opt;
				} else {
					$this->$stripped = true;
					$last = $stripped;
				}

			} else if ($part[0] == '-' && strlen($part) == 2) { // single flag alone
				$last = $opt = substr($part, 1);
				$this->_do_true($opt);
			} else if($part[0] == '-' && strlen($part) > 2) { // grouped flags
				foreach (str_split(substr($part, 1)) as $opt) {
					$this->_do_true($opt);
				}
			} else { // value after flag
				if ($last != null) {
					if (@$this->$last) $this->$last = $part;
					$last = null;
				}
			}
		}
	}

	private function _do_true($val) {


		if (isset($this->$val)) {
			if (is_string($this->$val)) {
				$this->$val = true;
			} else if (is_bool($this->$val)) {
				$this->$val = 2;
			} else if (is_int($this->$val)) {
				$this->$val++;
			} else {
				echo "HARKONNENS!\n";
				exit();
			}

		} else {
			$this->$val = true;
		}


	}

	public function _test($var) {
		return (@$this->$var) ? true : false;
	}

	public function _string($var) {
		return (@is_string($this->$var)) ? true : false;
	}

	public function _bool($var) {
		return (@is_bool($this->$var)) ? true : false;
	}

	public function _int($var) {
		return (@is_int($this->$var)) ? true : false;
	}

}

