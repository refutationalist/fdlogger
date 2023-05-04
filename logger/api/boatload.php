<?php

/* -2: SET TIME ZONE */
date_default_timezone_set("UTC");

/* PART 2: GENERATE CONFIGURATION OBJECT */

class config {
	public static object $settings;
	public static object $db;
	public static array  $modes;
	public static bool $debug;

	public static function parse() {

		$file = getenv('LOGGERINI') ?: "/etc/logger.ini";


		if (!is_readable($file)) {
			/* FIXME: make work with web */
			fprintf(STDERR, "'%s' is not readable\n", $file);
			exit(1);
		}


		$raw = parse_ini_file($file, true, INI_SCANNER_TYPED);
		if ($raw === false) {
			/* FIXME: make work with web */
			fprintf(STDERR, "'%s' is nonsensical.\n", $file);
			exit(1);
		}

		config::$settings = new stdClass();

		@config::$db       = (object) $raw["db"];
		@config::$debug    = (bool) $raw["settings"]["debug"] ?: false;

		@config::$settings->callsign    = (string) $raw["settings"]["callsign"]   ?: "N0CALL";
		@config::$settings->exchange    = (string) $raw["settings"]["exchange"]   ?: "0D-DX";
		@config::$settings->radiopurge  = (int)    $raw["settings"]["radiopurge"] ?: 300;

		// for some reason I made modes an enum.  was this a good idea?
		config::$modes = [ 'CW', 'AM', 'FM', 'USB', 'LSB', 'DIG' ];

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


/* PART 4: AUTOLOADER */

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
