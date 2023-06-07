<?php

if (php_sapi_name() != "cli") {
	echo "cli only.\n";
	exit(99);
}

spl_autoload_register(function ($class) {

	if (class_exists($class)) return;

    // project-specific namespace prefix
    $prefix = 'loggerlink\\';

    // base directory for the namespace prefix
    $base_dir = __DIR__ . '/loggerlink/';

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
/*
$argp = getopt(
	"u:r:hv". // base class
	"fd:w:n".  // follower class
	"w:".
	"p:"
);
 */

$argp = getopt("fwj");

if (isset($argp["f"])) {
	new loggerlink\follower();
} else if (isset($argp["w"])) {
	new loggerlink\wsjtx();
} else if (isset($argp["p"])) {
	new loggerlink\propwatch();
} else {
	$p = new loggerlink\loggerlink();
	$p->help();
}

__HALT_COMPILER();
