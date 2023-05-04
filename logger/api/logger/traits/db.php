<?php
namespace logger\traits;

if (!class_exists("mysqli")) throw new \Exception("db trait: mysqli extension not enabled");

define('ASSOC', MYSQLI_ASSOC);
define('NUM', MYSQLI_NUM);
define('BOTH',  MYSQLI_BOTH);


/*
 * This class contains only static methods so each use of the db trait calls
 * the same mysql connection.
 */

class dbc {
	public static \mysqli|null $con = null;
	public static string $last_sql = "";


	static public function close() {
		if (dbc::$con != null) dbc::$con->close();
		dbc::$con = null;
	}

}

/*
 * mariadb will occasionally complain if we don't disconnect nicely.
 */

register_shutdown_function(function() {
	dbc::close();
});


trait db {

	protected function dbcheck() {

		/* this might change depending on the project */
		if (gettype(\config::$db) != "object")
			throw new \Exception("db trait: configuration not available, can't connect to db");

		if (dbc::$con == null) {
			$success = false;
		} else {
			try {
				$success = dbc::$con->ping();
			} catch (Exception $e) {
				$success = false;
			}

		}

		if (!$success) {
			dbc::$con = new \mysqli(
				\config::$db->host,
				\config::$db->user,
				\config::$db->pass,
				\config::$db->name
			);
			$success = dbc::$con->ping();
		}

		if (!$success) throw new \Exception("db trait: could not connect to database");

	}

	public function query(string $format, ...$variables): \mysqli_result|bool {

		$this->dbcheck();

		$sql = (count($variables) > 0) ? vsprintf($format, $variables) : $format;
		dbc::$last_sql = $sql;

		$res = dbc::$con->query($sql);

		if (dbc::$con->error)
			throw new \Exception(sprintf("db trait: query failure '%s' on SQL: %s", dbc::$con->error, $sql));

		$this->querylog();
		return $res;

	}



	/* just for calling stored procedures, mainly */
	public function multiquery(string $format, ...$variables): array {

		$this->dbcheck();

		$sql = (count($variables) > 0) ? vsprintf($format, $variables) : $format;
		dbc::$last_sql = $sql;

		dbc::$con->multi_query($sql);

		if (dbc::$con->error)
			throw new \Exception(sprintf("db trait: multiquery fail with error '%s' on SQL: %s", dbc::$con->error, $sql));

		$results = array();

		do {
			if ($result = dbc::$con->store_result()) {
				$results[] = $result->fetch_all(MYSQLI_ASSOC);
			}
		} while (dbc::$con->next_result());

		$this->querylog();
		return $results;
	}

	public function fetchall(string $format, ...$variables): array {
		$res = $this->query($format, ...$variables);
		$return = $res->fetch_all(MYSQLI_ASSOC);
		$res->free();
		return $return;
	}

	public function quote(string $str): string {
		$this->dbcheck();
		return dbc::$con->escape_string($str);
	}

	public function row(\mysqli_result $res, int $method): null|array {

		$return = $res->fetch_array($method);
		if (dbc::$con->error)
			throw new \Exception("db trait: row retrieve fail '%s'", dbc::$con->error);

		return $return;
	}

	public function ping() {
		return dbc::$con->ping();
	}

	public function insert_id(): int {
		return dbc::$con->insert_id;
	}

	public function affected_rows(): int {
		return dbc::$con->affected_rows;
	}

	public function last_sql(): string {
		return dbc::$last_sql;
	}

	private function querylog() {

		if (\config::$db->querylog == false) return;

		$fh = fopen(\config::$db->querylog, 'a+');
		if ($fh == false) return;

		fwrite(
			$fh,
			json_encode([
				"time" => microtime(true),
				"client" => @$_SERVER["REMOTE_ADDR"] ?: "none",
				"query" => dbc::$last_sql
			])."\n"
		);

		fclose($fh);
	}


}

