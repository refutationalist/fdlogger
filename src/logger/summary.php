<?php

namespace logger;

class summary extends base {

	public function count(): array {
		$r = $this->fetchall("SELECT COUNT(*) AS cnt FROM fdlog");
		return([ true, $r[0]["cnt"] ]);
	}

	public function byband(): array {
		return $this->sumtable("fdbyband");
	}

	public function bymode(): array {
		return $this->sumtable("fdbymode");
	}

	public function bycabmode(): array {
		return $this->sumtable("fdbycabmode");
	}

	public function byzone(): array {
		return $this->sumtable("fdbyzone");
	}

	public function byclass(): array {
		return $this->sumtable("fdbyclass");
	}

	protected function sumtable(string $table): array {
		return([
			true,
			$this->fetchall("SELECT * FROM %s", $table)
		]);
	}



}
