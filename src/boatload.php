<?php

/* 0: Options and Defines */

/* 1: SET TIME ZONE */
date_default_timezone_set("UTC");


/* 2: GENERATE CONFIGURATION OBJECT */

class config {
	public static object $settings;
	public static object $db;
	public static array  $modes;
	public static bool $debug;

	public static function parse() {

		$file = getenv('LOGGERINI') ?: "/etc/logger.ini";


		if (!is_readable($file)) {
			/* FIXME: make work with web */
			printf("'%s' is not readable\n", $file);
			exit(1);
		}


		$raw = parse_ini_file($file, true, INI_SCANNER_TYPED);
		if ($raw === false) {
			/* FIXME: make work with web */
			printf("'%s' is nonsensical\n", $file);
			exit(1);
		}

		config::$settings = new stdClass();

		@config::$db       = (object) $raw["db"];
		@config::$debug    = (bool) $raw["settings"]["debug"] ?: false;

		@config::$settings->callsign    = (string) $raw["settings"]["callsign"]   ?: "N0CALL";
		@config::$settings->exchange    = (string) $raw["settings"]["exchange"]   ?: "0D-DX";
		@config::$settings->radiopurge  = (int)    $raw["settings"]["radiopurge"] ?: 300;

	}

	public static function dump() {
		echo "DEBUG: ".config::$debug."\n\n";

		echo "DB: ";
		print_r(config::$db);

		echo "\n\nSETTINGS: ";
		print_r(config::$settings);
	}

}

config::parse();


/* 3: AUTOLOADER */

// taken directly from the standard and modified.
// I'll move to composer only if I need it.

spl_autoload_register(function ($class) {

	if (class_exists($class)) return;

    // project-specific namespace prefix
    $prefix = 'logger\\';

    // base directory for the namespace prefix
    $base_dir = __DIR__ . '/logger/';

    // does the class use the namespace prefix?
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        // no, move to the next registered autoloader
        return;
    }

    // get the relative class name
    $relative_class = substr($class, $len);

    // replace the namespace prefix with the base directory, replace namespace
    // separators with directory separators in the relative class name, append
    // with .php
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';

    // if the file exists, require it
    if (file_exists($file)) {
        require $file;
    }

});

/* 4: Exception */

class logger_exception extends Exception { }


/* 5: CLI Handling */

if (php_sapi_name() == "cli") {
	echo "http mode only\n";
	exit(0);
}

/* 6: HTTP Handling */


class httpmode {
	public const failtype = 'application/octet-stream';
	public const htdir    = __DIR__ . '/http';

	/* This is obviously not a complete list
	 * of mime types, just the ones we're likely to see
	 * in an app like this.   Expand as necessary
	 */
	public static array $types = [
		'php'  => 'text/html',
		'html' => 'text/html',
		'jpg'  => 'image/jpeg',
		'jpeg' => 'image/jpeg',
		'js'   => 'application/x-javascript',
		'png'  => 'image/png',
		'css' => 'text/css',
		'gif' => 'image/gif',
		'svg' => 'image/svg+xml',
		'woff2' => 'font/woff2',
		'woff' => 'font/woff',
		'ttf' => 'font/ttf',
		'json' => 'application/json'
	];

	public static function pathinfo(string $uri): array {
		$file = pathinfo(httpmode::htdir.$uri);
		$file["uri"] = $uri;
		$file["full"] = httpmode::htdir.$uri;
		$file["exists"] = false;
		return $file;
	}

	public static function getfile(): array {

		// This should handle an alias in lighttpd

		if (@$_SERVER["PATH_TRANSLATED"]) {

			$uri = substr_replace(
				$_SERVER["REQUEST_URI"],
				"",
				0,
				strlen($_SERVER["SCRIPT_NAME"])
			);


			if ($uri == "") $uri = "/";

		} else {
			$uri = $_SERVER["REQUEST_URI"];
		}



		//cleansing of URL
		$uri = str_replace('/../', '/', strtok($uri ?? "/", '?'));
		$url = str_replace('://', '/', $uri);
		if (substr($uri, 0, 1) != '/') $uri = '/' . $uri;

		$file = httpmode::pathinfo($uri);

		// okay.  is this a directory?  look for an index.
		if (is_dir($file["full"])) {

			// create new uri, potentially appending a /
			$dir = $file["uri"] . ((substr($file["uri"], -1, 1) != '/') ? '/' : null);

			foreach ([ 'index.html', 'index.php' ] as $index) {

				$try = httpmode::pathinfo(sprintf($dir.$index));

				if (file_exists($try["full"])) {
					$file = $try;
					break;
				}

			}

		}

		// awesome, check for existence and add mimetype.
		if (file_exists($file["full"])) {
			$file["exists"] = true;
			$file["type"] = (httpmode::$types["{$file["extension"]}"]) ?: httpmode::failtype;
		}

		return $file;
	}


	public static function do404() {
		// yes, you could make it nicer looking.
		header('HTTP/1.0 404 Not Found');
		header('Content-type: text/plain');
		echo "Document not found.\n";
		exit();
	}

	public static function send() {

		$file = httpmode::getfile();

		if ($file["uri"] == "/api") {

			$part = null;
			switch (@$_GET["a"]) {

				case "user":
					$part = new logger\user();
					break;

				case "radio":
					$part = new logger\radio();
					break;

				case "summary":
					$part = new logger\summary();
					break;

				case "test";
					if (\config::$debug) $part = logger\test();
					break;

			}

			if ($part != null) {
				$part->process();
			} else {
				echo json_encode([ "error" => "mode detection error" ]);
			}


		} else {

			if ($file["exists"] !== true) httpmode::do404();

			header("Content-type: {$file["type"]}");
			if ($file["extension"] == "php") {
				require($file["full"]);
			} else {
				readfile($file["full"]);
			}

		}

	}

}

httpmode::send();

/* PHASE 5: Helper Functions */

__HALT_COMPILER();

