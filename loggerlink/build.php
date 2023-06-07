#!/usr/bin/env php
<?php

// shamelessly stolen from: https://blog.programster.org/creating-phar-files


const pharfile = __DIR__."/loggerlink.phar";

if (file_exists(pharfile)) unlink(pharfile);
if (file_exists(pharfile . '.gz')) unlink(pharfile . '.gz');

// create phar
$phar = new Phar(pharfile);

// start buffering. Mandatory to modify stub to add shebang
$phar->startBuffering();

// Create the default stub from main.php entrypoint
$stub = $phar->createDefaultStub('stub.php');

// Add the rest of the apps files
$phar->buildFromDirectory(__DIR__ . '/src');

// Customize the stub to add the shebang
$stub = "#!/usr/bin/php \n" . $stub;

// Add the stub
$phar->setStub($stub);

$phar->stopBuffering();

// plus - compressing it into gzip  
$phar->compressFiles(Phar::GZ);

# Make the file executable
chmod(pharfile, 0770);


printf("%s successfully created\n", pharfile);

