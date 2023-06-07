<?php

namespace loggerlink;

define('ASSOC', SQLITE3_ASSOC);
define('NUM', SQLITE3_NUM);
define('BOTH',  SQLITE3_BOTH);

trait db {

	protected \SQLite3|null $con = null;
	protected string $last_sql = "";

	
	protected function dbcheck() {
		// with these options, sqlite will bomb out if it can't open the file
		// also, change this to work with your config file.
		if ($this->con == null) {
			$dbfile = getenv("ENGLOGDB");
			if (!file_exists($dbfile)) throw new \Exception("environment var ENGLOGDB doesn't point to a file");
			$this->con = new \SQLite3($dbfile, SQLITE3_OPEN_READWRITE);
			$this->exec("PRAGMA foreign_keys = ON;");
		}
	}

	protected function errorcheck(string $from = "generic") {

		if ($this->con->lastErrorCode() != 0) {
			throw new \Exception(sprintf(
				"db trait: %s failure '%s' on SQL: %s",
				$from,
				$this->con->lastErrorMsg(),
				$this->last_sql
			));
		}

	}

	public function query(string $format, ...$variables): \SQLite3Result|bool {

		$this->dbcheck();

		$sql = (count($variables) > 0) ? vsprintf($format, $variables) : $format;
		$this->last_sql = $sql;

		$res = $this->con->query($sql);
		$this->errorcheck("query");

		return $res;

	}

	public function exec(string $format, ...$variables): bool {

		$this->dbcheck();
		$sql = (count($variables) > 0) ? vsprintf($format, $variables) : $format;
		$this->last_sql = $sql;

		$return = $this->con->exec($sql);
		$this->errorcheck("query");

		return $return;
		

	}

	public function prepare(string $sql): \SQLite3Stmt|bool {

		$this->dbcheck();

		$this->last_sql = $sql;

		$res = $this->con->prepare($sql);
		$this->errorcheck("prepare");

		return $res;

	}

	public function fetchall(string $format, ...$variables): array {
		$res = $this->query($format, ...$variables);

		$return = array();
		while ($r = $res->fetchArray(ASSOC)) {
			$return[] = $r;
		}
		$res->finalize();
		return $return;
	}
	

	public function quote(string $str): string {
		$this->dbcheck();
		return $this->con->escapeString($str);
	}

	public function row(\SQLite3Result $res, int $method = ASSOC): null|array {
		$return = $res->fetchArray($method);
		$this->errorcheck("row");
		return $return;
	}

	public function free(\SQLite3Result $res) {
		$res->finalize();
	}

	public function insert_id(): int {
		return $this->con->lastInsertRowID();
	}

	public function affected_rows(): int {
		return $this->con->changes();
	}

	public function last_sql(): string {
		return $this->last_sql;
	}
	
}

