#!/bin/sh

while getopts s:j:d:u o
   do case "$o" in
		d)  DIR="$OPTARG";;
	   u)  UPLOAD="true";;
	   s)  SUFFIX="$OPTARG";;
		\?)  echo "Usage: $0 -d dist_dir [-s suffix]" && exit 1;;
	esac
done

sh update.sh

swig -c++ -php -o djonphpdriver.cpp driver.i


OS=`uname -s`
if test "$OS" = "Darwin"; then
cp /usr/local/lib/libdjon-client.dylib .
else
cp /usr/lib/libdjon-client.so .
fi

echo "php55"
if test "$OS" = "Darwin"; then
/usr/local/opt/php55/bin/phpize
else
phpize
fi

./configure --enable-djonwrapper
make clean
make


if [ ! -d "dist" ];
then
	mkdir dist
fi
zipfile="dist/djondb_phpext55_`uname`_`uname -m`${SUFFIX}.zip"

zip $zipfile test.php modules/djonwrapper.so djonwrapper.php

echo "php54"
if test "$OS" = "Darwin"; then
/usr/local/opt/php55/bin/phpize
else
phpize
fi

./configure --enable-djonwrapper
make clean
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
