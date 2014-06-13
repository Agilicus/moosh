#!/bin/bash
source functions.sh

install_db
install_data
cd $MOODLEDIR

moosh forum-newdiscussion --subject "Forum Name" --message "test_msg" 2 1 2

if echo "SELECT * FROM mdl_forum_posts WHERE useris = '2' AND message='test_msg'"  \
    | mysql -u "$DBUSER" -p"$DBPASSWORD" "$DBNAME" ; then
  exit 0
else
  exit 1
fi

