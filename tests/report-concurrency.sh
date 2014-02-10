#!/bin/bash
source functions.sh

install_db
install_data
cd $MOODLEDIR

if moosh report-concurrency | grep "users online" ; then
  exit 0
else
  exit 1
fi