#!/bin/bash
source functions.sh

install_db
install_data

cd $MOODLEDIR

$MOOSHCMD maintenance-off
if ! curl -s $WWW/index.php | grep "The site is undergoing maintenance and is currently not available"; then
  exit 0 
else
  exit 1
fi

