# loggerlink -- Automatically Report Stuff to N9MII Logger

Run ``make`` to build it.

Okay, I won't pretend this documentation sucks, but it's just for me and my folks, so hey.

Here's the help file:

```
loggerlink: send radio data to N9MII's FD logger

Required Settings:
     -u <url>            URL of N9MII logger

     -r <name>           the name of your radio in Radio Follow mode
                         or your log handle in the other modes

Radio Follow Mode:
     -f                  engage follow mode
     -d <host>:<port>    host and port of rigctld server defaults to
                         localhost and 4532

     -w <int>            wait <int> seconds between updates defaults to 3

     -n                  do not send modulation information

WSJTX Logging:
     -x <directory>      directory containing contest log for
                         WSJT-X instance
     -w <int>            wait <int> seconds between checks
                         defaults to 60


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

This is going to change before field day.   It works in batch mdoe now.   This is junk.
