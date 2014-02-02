djondb PHP driver
================

Welcome to the djondb php driver, here you will find the compilation/installation instructions, if you need further information
please go to http://djondb.com, there you will find how to use it.

Compilation
===========

Just execute the script compile.sh and it will do the work for you, like this:

chmod +x compile.sh
./compile.sh

If you cannot execute the compile script, the steps you will need to do are as follows:


phpize
./configure --enable-djonwrapper
make
sudo make install

Then you will need to edit your php.ini file to add the extension like this:

	extension=djonwrapper.so

If you are working on a recent release of PHP you will need to create a file djondb.ini at your /etc/php5/conf.d folder with the above
line on it.

If you are using php under apache, you will need to restart apache to be able to use the driver.

Usage
==========

To be able to use the driver you will need to copy the djonwrapper.php file in your application directory, after this you
will be ready to work with the driver. Here's a quick sample that you can test in your console. (remember to startup your
djondb server using "djondbd -n" in your command line).

Create a file named test.php, copy and paste this:
	<?php

	include("djonwrapper.php");

	$c = DjondbConnectionManager::getConnection("localhost");
	$c->open();

	$json = "{ name: 'Peter', lastName: 'Parker', occupations: [ { company: 'Daily Bugle', position: 'Photographer'}, { position: 'Superhero' } ], nicknames: [{ name: 'Spiderman', main: 1}, {'name': 'Spidey'}] }";

	$c->insert('phpdb', 'superheroes', $json);
	echo 'Inserted';

	echo 'Finding';
	$cursor = $c->find('phpdb', 'superheroes', '$"name" == "Peter"');

	while ($cursor->next()) {
		$res = $cursor->current();
		echo $res->toChar();
	}

	DjondbConnectionManager::releaseConnection($c);

	?>

Now you can test it using:

	php test.php

Done, congratulations you're ready to use djondb with your php code.
