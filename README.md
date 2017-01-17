djondb PHP driver
================

Welcome to the djondb php driver, here you will find the basics on how to use it for more
detailed information please go to http://djondb.com.

Usage
==========

We recommend using composer to resolve the dependencies, but if you can't you just need to copy the djondb.php and reference
it in your code, here's an example:

Create a file named test.php, copy and paste this:
	<?php

	use "djondb.php;

	$c = new DjondbConnection("localhost", 1243);
	if ($c->open()) {
		$address = (object)array("street" => "Washington", "number" => 3);
		$addresses = array($address);
		$obj = (object)array("name" => "Peter", "lastName" => "Parker", "addresses": $addresses);

		$json = json_encode($obj);
		$insertDQL = "insert $json into phprocks:customer";
		$c->executeUpdate($insertDQL);

		// Find
		$cur = $c->executeQuery("select * from phprocks:customer";
		$cur->next();

		$recovered = $cur->current();
		print($recovered->name);
	}

	?>

Note: json objects have to be represented using StdClass objects, for json arrays use standard php arrays.

Now you can test it using:

	php test.php

Done, congratulations you're ready to use djondb with your php code.


Tests
=====
To use the tests you will have to install composer, once you have the composer installed you will be able to use:

   composer update
   ./vendor/bin/phpunit

the first instruction will install the dependencies, which includes phpunit, this will create a folder vendor with
the script phpunit on it. The second instruction will trigger the tests
