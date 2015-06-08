#!/bin/bash
#
# Test script for the test configuration
#
case "$1" in
   "delayoutput")
      echo "First line of output, sleep 2 seconds."
      sleep 2
      echo "Show processlist"
      ps -ef
      echo "And sleep again for 2 seconds."
      sleep 2
      echo "Showing directory listing:"
      ls -al
      echo "And sleep again for 2 seconds."
      sleep 2
      echo "Done"
   ;;
   "selectbox-keyval")
      echo -e "Item1\tThis is item 1"
      echo -e "Item2\tThis is item 2"
      echo -e "Item3\tThis is item 3"
   ;;
   "selectbox-standard")
      echo -e "This is item 1"
      echo -e "This is item 2"
      echo -e "This is item 3"
   ;;
esac
