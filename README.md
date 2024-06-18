# N9MII's Field Day Logger

In 2020 and 2022, we used [CloudLog](https://www.magicbug.co.uk/cloudlog/) to capture our data.

While that eventually worked, it required a lot of SQL finagling to get the data into Cabrillo file format that the ARRL site understood.  Most other field day loggers we could find seemed hard to work with and incomplete, or written for Windows users.  As we are strictly a unix shop, that wouldn't do.

For the record: In 2021, I had some brain wrong that made field day kinda hard.

## How the Logger Works

It's basically a response to things I realized while I was logging FD in 2022.  I would often type my notes in a text editor, and then copy what I copied into the logger.  Based on that, this logger gives the user an open text field.  The parser attempts to figure out the call and exchange from what you type in.  If you add some notes in between square brackets it will include those notes in the log.

It's pretty slick once you get it.  As long as it doesn't have horrible parser bugs.

## Logger Requirements

The server needs:

 * PHP >8 with functional webserver, developed on 8.3, but doesn't use anything that new.
 * MariaDB, developed with 11.3.
   * Notably, we use views and triggers.  SQLite was considered, but concurrency is good.
 * ``curl`` for downloading logbook data (optional)
   
The clients need:
  * A web browser.  Currently only tested with Firefox
  * [hamlib](https://hamlib.github.io/) for rigctl
  * PHP CLI >8 for reporting rigctl data to the logger.
  
## The Basic Installation Idea

  * Create a database
  * Import ``extra/logger.sql`` into the database
  * Generate the callbook sql
	  * Create a work directory for the callbook generator.
	  * Run ``extra/callbookgen -o /your/work/dir``
	  * Wait a minute.
	  * import /your/work/dir/callbook.sql into the database
  * run ``make``
  * use the resultant logger.phar with your webserver
	  * ``php -S 0.0.0.0:8888 logger.phar`` works
	  * There is an example config for lighttpd in ``extra/``
  * Configure ``extra/logger.ini`` appropriately.  Copy it either to ``/etc`` or a location specified in the webserver's environment variable ``LOGGERINI``.

 To use ``loggerlink`` see the readme in ``loggerlink``.
 
 
## Arch Linux

I've created PKGBUILDs for all of this in my [personal PKBUILD repo](https://github.com/refutationalist/saur).

## Security?

Nope.

This is designed for local use only.  In our use case, we have a single wifi router that only the computers that are working Field Day are allowed to join.

If you put this on the internet, you will be sad.

## License

This code is AGPL3.  I'll get to adding appropriate headers later.

The font I'm using is [RedHat Mono](https://github.com/RedHatOfficial/RedHatFont), which is distributed via the OFL.