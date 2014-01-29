<?php

include("djonwrapper.php");

$c = DjondbConnectionManager::getConnection("localhost");
$c->open();

$json = "{ name: 'Peter', lastName: 'Parker', occupations: [ { company: 'Daily Bugle', position: 'Photographer'}, { position: 'Superhero' } ], nicknames: [{ name: 'Spiderman', main: 1}, {'name': 'Spìdey'}] }";

$c->insert('phpdb', 'superheroes', $json);
echo '<p>Inserted</p>';

$j['name'] = "Peter";
$j['lastName'] = "Parker";
$t = json_encode($j);
echo "new t: $t";
$c->insert('phpdb', 'a', $t);

echo '<p>Finding</p>';
$cursor = $c->find('phpdb', 'superheroes', '$"name" == "Peter"');

if ($cursor->next()) {
	$res = $cursor->current();

	echo $res->toChar()."\n";

}
$cursor = $c->find('phpdb', 'superheroes', '*', '$"lastName" == "Parker"');
if ($cursor->next()) {
	$res = $cursor->current();

	echo "nickname: ";

	echo $res->toChar()."\n";
}

$cursor = $c->find('phpdb', 'superheroes', '*', '$"name" == "Peter"');

echo "With objects";
while ($cursor->next()) {
	$res = $cursor->current();

	$obj = json_decode($res->toChar());

	echo 'Name: '.$obj->{'name'}."\n";
}

DjondbConnectionManager::releaseConnection($c);

?>
