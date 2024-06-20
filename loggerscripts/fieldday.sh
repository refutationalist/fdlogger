CONFIG="$HOME/.config/fielddayrc"

if [ ! -f $CONFIG ]; then
	echo "$0: no config file $CONFIG"
	exit 1
fi

. $CONFIG

if [ "noterm" = "$1" ]; then
	echo "$0: not spawning terminal"
	NOTERM=1
else 
	NOTERM=0
fi

if [ ! -x "/usr/bin/loggerlink" ]; then
	echo "$0: loggerlink is not install or not executable"
	exit 1
fi

doit() {

	if [ $NOTERM = 1 ]; then
		exec $1
	else
		exec /usr/bin/xfce4-terminal -T "$2" -x bash -c "$1 ; echo 'Press Enter to Close' ; read"
	fi


}


