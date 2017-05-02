<?php

use djondb\DjondbConnection;

class ConnetionTest extends \PHPUnit_Framework_TestCase
{
	public function testShowDbs() {
		$c = new DjondbConnection("localhost", 1243);
		$this->assertEquals(False, $c->open());
		$dbs = $c->showDbs();
		$this->assertTrue($dbs != null);
	}

	public function testDropInsertFind() {
		$c = new DjondbConnection("localhost", 1243);
		$this->assertEquals(False, $c->open());

		$c->dropNamespace("testphp", "testinsert");

		$obj = (object)array("name" => "John", "lastName" => "Smith", "age" => 10);
		$c->insert("testphp", "testinsert", $obj);

		$cur = $c->find("testphp", "testinsert", "*", "");

		$this->assertTrue($cur->next());
		$data = $cur->current();
		$this->assertEquals($data->name, $obj->name);
		$this->assertEquals($data->lastName, $obj->lastName);
		$this->assertEquals($data->age, $obj->age);
	}

	public function testDropInsertFindUpdate() {
		$c = new DjondbConnection("localhost", 1243);
		$this->assertEquals(False, $c->open());

		$c->dropNamespace("testphp", "testupdate");

		$obj = (object)array("name" => "John", "lastName" => "Smith", "age" => 10, "salary" => 1200232.23);
		$c->insert("testphp", "testupdate", $obj);

		$cur = $c->find("testphp", "testupdate", "*", "");

		$this->assertTrue($cur->next());
		$data = $cur->current();

		$data->address = "Ave 123";
		$c->update("testphp", "testupdate", $data);

		$cur = $c->find("testphp", "testupdate", "*", "");

		$this->assertTrue($cur->next());
		$data = $cur->current();

		$this->assertEquals($data->name, $obj->name);
		$this->assertEquals($data->lastName, $obj->lastName);
		$this->assertEquals($data->age, $obj->age);
		$this->assertEquals($data->address, "Ave 123");
		$this->assertEquals($data->salary, 1200232.23);
	}

	public function testTX() {
		$c = new DjondbConnection("localhost", 1243);
		$this->assertEquals(False, $c->open());

		$c->dropNamespace("testphp", "testtx");
		$c->beginTransaction();

		$obj = (object)array("name" => "John", "lastName" => "Smith", "age" => 10);
		$c->insert("testphp", "testtx", $obj);

		$cur = $c->find("testphp", "testtx", "*", "");

		$this->assertTrue($cur->next());
		$data = $cur->current();

		$this->assertEquals($data->name, $obj->name);
		$this->assertEquals($data->lastName, $obj->lastName);
		$this->assertEquals($data->age, $obj->age);
		$c->commitTransaction();

		$cur = $c->find("testphp", "testtx", "*", "");

		$this->assertTrue($cur->next());
		$data = $cur->current();

		$this->assertEquals($data->name, $obj->name);
		$this->assertEquals($data->lastName, $obj->lastName);
		$this->assertEquals($data->age, $obj->age);

		// test Rollback
		$c->beginTransaction();
		$c->dropNamespace("testphp", "testtx");
		$cur = $c->find("testphp", "testtx", "*", "");
		$this->assertFalse($cur->next());
		$c->rollbackTransaction();
		$cur = $c->find("testphp", "testtx", "*", "");
		$this->assertTrue($cur->next());
	}

	public function testExecuteQuery() {
		$c = new DjondbConnection("localhost", 1243);
		$this->assertEquals(False, $c->open());

		$c->executeUpdate("dropNamespace \"testphp\", \"testquery\"");

		$obj = (object)array("name" => "John", "lastName" => "Smith", "age" => 10);
		$insert = "insert { \"name\": \"John\", \"lastName\": \"Smith\", \"age\": 10 } into testphp:testquery";
		$c->executeUpdate($insert);

		$select = "select * from testphp:testquery";
		$cur = $c->executeQuery($select);

		$this->assertTrue($cur->next());
		$data = $cur->current();

		$data->address = "Ave 123";
		$json = json_encode($data);
		$update = "update $json into testphp:testquery";
		$c->executeUpdate($update);

		$select = "select * from testphp:testquery";
		$cur = $c->executeQuery($select);

		$this->assertTrue($cur->next());
		$data = $cur->current();

		$this->assertEquals($data->name, $obj->name);
		$this->assertEquals($data->lastName, $obj->lastName);
		$this->assertEquals($data->age, $obj->age);
		$this->assertEquals($data->address, "Ave 123");
	}
}

?>
