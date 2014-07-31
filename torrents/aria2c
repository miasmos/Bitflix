#!/bin/sh
#
# aria     This shell script takes care of starting and stopping
#               the aria2
#
# chkconfig: - 90 10
# description: Download things, yo

# Do preliminary checks here, if any
#### START of preliminary checks #########


##### END of preliminary checks #######


# Handle manual control parameters like start, stop, status, restart, etc.

case "$1" in
  start)
    # Start daemons.

    echo -n $"Starting aria daemon: "
    echo
    nohup aria2c --enable-rpc --enable-dht --bt-save-metadata --bt-metadata-only --rpc-allow-origin-all --max-concurrent-downloads=20 --bt-stop-timeout=600 --max-overall-download-limit=500K -c -D >/var/www/movies/logs/aria.log 2>&1 &
    echo "aria"
    ;;

  stop)
    # Stop daemons.
    echo -n $"Shutting down aria: "
    killall aria2c
    echo "aria"

    # Do clean-up works here like removing pid files from /var/run, etc.
    ;;
  reload|force-reload)
    echo "Reload not supported. "
	echo
	
    ;;
  restart)
    $0 stop
    $0 start
    ;;

  *)
    echo $"Usage: $0 {start|stop|restart}"
    exit 1
esac

exit 0