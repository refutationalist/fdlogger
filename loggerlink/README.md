# loggerlink -- Automatically Report Stuff to N9MII Logger

Run ``make`` to build it.

Okay, I won't pretend this documentation doesn't suck, but it's just for me and my folks, so hey.

Here's the help file:

```
loggerlink: send radio data to N9MII's FD logger

Required Settings:
     -u <url>            URL of N9MII logger

     -r <name>           the name of your radio in Radio Follow mode
                         or your log handle in the other modes
Optional Settings:
     -w <int>            wait <int> seconds between updates defaults to 3


Radio Follow Mode:
     -f                  engage follow mode
     -d <host>:<port>    host and port of rigctld server defaults to
                         localhost and 4532
     -n                  do not send modulation information

WSJTX Logging:
     -x <directory>      directory containing contest log for
                         WSJT-X instance
WSJT-X Propagation Monitoring:
     -p <directory>      directory containing ALL.TXT for
                         WSJT-X instance
Supplemental Settings:
     -h                  this help
     -v                  print debugging info
```

Save for ``-h``, you will always need to supply the URL to the logger with ``-u`` and some sort of name with ``-r``.   It will either be the name of a radio for the radio follow feature, or it will be **your** name for logging WSJT-X contacts.

## Radio Follow

Toss out ``-f`` to enable follow mode.  You'll need to have ``rigctld`` set up for your radio, and by default ``loggerlink`` will look at the default port on localhost for your radio.  Once it's connected it will send the frequency and the modulation mode your radio is on to the logger.

If you select the radio from the dropdown on the logger, you will no longer have to track the frequency and mode of your radio.   Makes things tons easier.

If you add a ``-n``, modulation info will be suppressed.  The follow feature will check the frequency every three seconds, unless ``-w`` is set to a different integer.

## WSJT-X Logging

``loggerlink`` read the sqlite3 data output from WSJT-X to log in semi-realtime to the logger.   Feed it the configuration directory of your active WSJT-X, and an optional wait time as in the Radio Follow mode.

It will first check all prexisting entries to see if they were logged, and log any that are missing.  It will then look for subsequent entries every ``wait`` seconds and post them.

WSJT-X logging mode always creates a file in your home directory called ``loggerlink-wsjtx-error-TIMESTAMP.txt`` to log any noticable errors in logging.

If WSJT-X logging mode fails to log what it believes is a loggable QSO, it will add a note with the specific details to the logger as well as add it to the error log file.

## WSJT-X Propagation Monitoring Mode

This mode is for unattended monitoring of WSJT-X stations with not a lot of contacts.  It's not very well tested, and I'm currently leaning between documenting it and pulling it out.