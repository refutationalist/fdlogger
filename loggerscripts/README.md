# Scripts for the N9MII Logger

This is a set of scripts to ease the use of the field day logger on computers actually in the field.   It's designed to use ``xfce4-terminal`` and pop windows in GNOME, but it's fairly straightforward.

 * ``loggerscripts-rigctld`` opens hamlib's rigctl to connect to the radio
 * ``loggerscripts-follow`` opens ``loggerlink`` in radio follow mode
 * ``loggerscripts-wsjtx`` opens ``loggerlink`` for monitoring WSJT-X for QSOs
 
Copy ``/usr/share/loggerscripts/defaults.fielddayrc`` to ``$HOME/.config/fielddayrc`` and edit appropriately.
