#!/bin/sh

while getopts s:j:d:u o
   do case "$o" in
		d)  DIR="$OPTARG";;
	   u)  UPLOAD="true";;
	   s)  SUFFIX="$OPTARG";;
		\?)  echo "Usage: $0 -d dist_dir [-s suffix]" && exit 1;;
	esac
done


OS=`uname -s`
if test "$OS" = "Darwin"; then
cp /usr/lib/libdjon-client.dylib /usr/lib/libdjon-client.dylib .
else
cp /usr/lib/libdjon-client.so .
fi

phpize

./configure --enable-djonwrapper
make


if [ ! -d "dist" ];
then
	mkdir dist
fi
zipfile="dist/djondb_phpext_`uname`_`uname -m`${SUFFIX}.zip"

zip $zipfile test.php modules/djonwrapper.so djonwrapper.php

if [ ! -z "${DIR}" ]; 
then
	cp $zipfile $DIR
fi
