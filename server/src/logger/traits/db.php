<?php
namespace logger\traits;

if (!class_exists("mysqli")) throw new \logger_exception("db trait: mysqli extension not enabled");

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
			throw new \logger_exception("db trait: configuration not available, can't connect to db");

		if (dbc::$con == null) {
			$success = false;
		} else {
			try {
				$success = dbc::$con->ping();
			} catch (\Exception $e) {
				$success = false;
			}

		}

		if (!$success) {
			try {
				dbc::$con = new \mysqli(
					\config::$db->host,
					\config::$db->user,
					\config::$db->pass,
					\config::$db->name
				);
				$success = dbc::$con->ping();
			} catch (\mysqli_sql_exception $e) { 
				$success = false;
			}
		}

		if (!$success) throw new \logger_exception("db trait: could not connect to database");

	}

	protected function query(string $format, ...$variables): \mysqli_result|bool {

		$this->dbcheck();

		$sql = (count($variables) > 0) ? vsprintf($format, $variables) : $format;
		dbc::$last_sql = $sql;

		try {
			$res = dbc::$con->query($sql);
		} catch (\mysqli_sql_exception $exc) {
			throw new \logger_exception($exc->getMessage());
		}

		if (dbc::$con->error)
			throw new \logger_exception(sprintf("db trait: query failure '%s' on SQL: %s", dbc::$con->error, $sql));

		$this->querylog();
		return $res;

	}



	/* just for calling stored procedures, mainly */
	protected function multiquery(string $format, ...$variables): array {

		$this->dbcheck();

		$sql = (count($variables) > 0) ? vsprintf($format, $variables) : $format;
		dbc::$last_sql = $sql;

		dbc::$con->multi_query($sql);

		if (dbc::$con->error)
			throw new \logger_exception(sprintf("db trait: multiquery fail with error '%s' on SQL: %s", dbc::$con->error, $sql));

		$results = array();

		do {
			if ($result = dbc::$con->store_result()) {
				$results[] = $result->fetch_all(MYSQLI_ASSOC);
			}
		} while (dbc::$con->next_result());

		$this->querylog();
		return $results;
	}

	protected function fetchall(string $format, ...$variables): array {
		$res = $this->query($format, ...$variables);
		$return = $res->fetch_all(MYSQLI_ASSOC);
		$res->free();
		return $return;
	}

	protected function quote(string $str): string {
		$this->dbcheck();
		return dbc::$con->escape_string($str);
	}

	protected function row(\mysqli_result $res, int $method): null|array {

		$return = $res->fetch_array($method);
		if (dbc::$con->error)
			throw new \logger_exception("db trait: row retrieve fail '%s'", dbc::$con->error);

		return $return;
	}

	protected function ping() {
		return dbc::$con->ping();
	}

	protected function insert_id(): int {
		return dbc::$con->insert_id;
	}

	protected function affected_rows(): int {
		return dbc::$con->affected_rows;
	}

	protected function last_sql(): string {
		return dbc::$last_sql;
	}

	protected function querylog() {

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

