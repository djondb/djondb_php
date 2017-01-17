<?php
namespace djondb_php;

class NetworkTest extends \PHPUnit_Framework_TestCase
{
	public function testStream() {
		$s = new \djondb_php\Network();
		$s->writeInt(1);
		$text = "Hello";
		$s->writeString($text);
		$b = True;
		$s->writeBoolean($b);
		$d = 2323223.23232;
		$s->writeDouble($d);
		$l = 10;
		$s->writeLong($l);
		$l2 = PHP_INT_MAX;
		$s->writeLong($l2);

		$obj = (object)array("name" => "John", "lastName" => "Smith", "age" => 10);
		$s->writeBSON($obj);

		$arr = array($obj, $obj);
		$s->writeBSONArray($arr);

		$s->seek(0);

		$res = $s->readInt();
		$this->assertEquals(1, $res);
		$res = $s->readString();
		$this->assertEquals($text, $res);
		$res = $s->readBoolean();
		$this->assertEquals($b, $res);
		$res = $s->readDouble();
		$this->assertEquals($d, $res);
		$res = $s->readLong();
		$this->assertEquals($l, $res);
		$res = $s->readLong();
		$this->assertEquals($l2, $res);
		$res = $s->readBSON();
		$this->assertEquals($obj, $res);
		$res = $s->readBSONArray();
		$this->assertEquals($arr, $res);
	}

}



?>
