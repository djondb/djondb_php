<?php
namespace djondb;

use djondb\Network;

class CommandTest extends \PHPUnit_Framework_TestCase
{
	public function testShowDbs() {
		$n = new Network();
		$n->connect("localhost", 1243);
		$c = new Command($n);
		$res = $c->showDbs();

		$this->assertEquals("array", gettype($res));
	}

	public function testShowNS() {
		$n = new Network();
		$n->connect("localhost", 1243);
		$c = new Command($n);
		$res = $c->showNamespaces("db");

		$this->assertEquals("array", gettype($res));
	}

	public function testDropNS() {
		$n = new Network();
		$n->connect("localhost", 1243);
		$c = new Command($n);
		$res = $c->dropNamespace("db", "ns");

		$this->assertEquals(True, $res);
	}

	public function testInsert() {
		$n = new Network();
		$n->connect("localhost", 1243);
		$c = new Command($n);
		$data = (object)array("name" => "John", "lastName" => "Smith", "age" => 10);

		$res = $c->insert("db", "ns", $data);
	}

	public function testFind() {
		$n = new Network();
		$n->connect("localhost", 1243);
		$c = new Command($n);
		$data = (object)array("name" => "John", "lastName" => "Smith", "age" => 10);

		$res = $c->insert("db", "ns", $data);

		$cur = $c->find("db", "ns", "*", "");

		$this->assertTrue($cur != null);
		$this->assertTrue($cur->next());

		$obj = $cur->current();
		$this->assertEquals($data->name, $obj->name);
		$this->assertEquals($data->lastName, $obj->lastName);
		$this->assertEquals($data->age, $obj->age);
	}

	public function testUpdate() {
		$n = new Network();
		$n->connect("localhost", 1243);
		$c = new Command($n);
		$data = (object)array("name" => "John", "lastName" => "Smith", "age" => 10);

		$res = $c->update("db", "ns", $data);
	}

	public function testRemove() {
		$n = new Network();
		$n->connect("localhost", 1243);
		$c = new Command($n);
		$res = $c->remove("db", "ns", "1234", "12345");
		$this->assertEquals(False, $res);
		$res = $c->remove("db", "ns", "1234");
		$this->assertEquals(False, $res);
	}

}

?>
