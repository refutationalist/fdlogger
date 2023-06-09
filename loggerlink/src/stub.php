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


$args = new loggerlink\naive_getopt();
var_dump($args);

if ($args->_test("h")) {
	help();
} else if ($args->_test("f")) {
	new loggerlink\follower($args);
} else if ($args->_test("w")) {
	new loggerlink\wsjtx($args);
} else if ($args->_test("p")) {
	new loggerlink\propwatch($args);
} else {
	echo $argv[0] .": WHATS WRONG?\n";
	help();
}


function help(int $exit = 0): null {

	echo <<<EndHELP
loggerlink: send radio data to N9MII's FD logger

Required Settings:
     -u <url>            URL of N9MII logger

     -r <name>           the name of your radio as it will appear in
                         your logger

Radio Follow Mode (-f):
     -d <host>:<port>    host and port of rigctld server defaults to
                         localhost and 4532

     -w <int>            wait <int> seconds between updates defaults to 3

     -n                  do not send modulation information

WSJTX Logging:
     -p <directory>      directory containing contest log for
                         WSJT-X instance

WSJT-X Propagation Monitoring:

     -p <directory>      directory containing ALL.TXT for
                         WSJT-X instance
Supplemental Settings:
     -h                  this help
     -v                  print debugging info



EndHELP;
	exit($exit);
}



__HALT_COMPILER();
