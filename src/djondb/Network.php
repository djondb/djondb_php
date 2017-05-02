<?php

namespace djondb;

function int_fromendian($data) {
	$res = unpack("V", $data);
	return $res[1];
}

function double_fromendian($data) {
	$res = unpack("d", $data);
	return $res[1];
}

function int_toendian($val) {
	$res = pack('V', $val);
	return $res;
}

function double_toendian($val) {
	return pack("d", $val);
}

class Network {
	public $bufferLen = 0;
	public $bufferPos = 0;
	public $count = 0;

	public function connect($host, $port) {
		$addr = gethostbyname($host);

		$this->client = stream_socket_client("tcp://$addr:$port", $errno, $errorMessage);
		stream_set_blocking($this->client, FALSE);

		stream_set_timeout($this->client, 2);

		if ($this->client === false) {
			throw new UnexpectedValueException("Failed to connect: $errorMessage");
		}
	}

	public function waitAvailable() {
		while ($this->currentPosition() >= $this->size()) {
			$pos = ftell($this->client);
			$res = fread($this->client, 1024*100);
			$newPos = ftell($this->client);
			$len = $newPos - $pos;
			$posBeforeRead = $this->currentPosition();
			$this->writeRaw($res, $len);
			$this->seek($posBeforeRead);
		}
	}

	public function checkBuffer($size) {
		$pos = $this->currentPosition();
		if ($this->size() < ($pos + $size)) {
			$this->waitAvailable();
		}
		if ($this->size() < ($this->currentPosition() + $size)) {
			throw new \Exception("Not enough data");
		}
	}

	public function reset() {
		$this->bufferLen = 0;
		$this->bufferPos = 0;
		fseek($this->buffer, 0);
	}

	public function flush() {
		$buffer = $this->getContents();
		
		fwrite($this->client, $buffer, $this->size());
		fflush($this->client);
		$this->reset();
	}

	public function __construct() {
		$this->buffer = fopen("php://memory", "w+b");
	}

	public function writeInt($val) {
		fwrite($this->buffer, int_toendian($val), 4);
		$this->bufferLen += 4;
		$this->bufferPos += 4;
	}

	public function readInt() {
		$this->checkBuffer(4);
		$read = fread($this->buffer, 4);
		$res = int_fromendian($read);
		$this->bufferPos += 4;
		return $res;
	}

	public function writeLong($val) {
		$highMap = 0xffffffff00000000;
		$lowMap = 0x00000000ffffffff;
		$higher = ($val & $highMap) >> 32;
		$lower = ($val & $lowMap);
		$this->writeInt($lower);
		$this->writeInt($higher);
	}

	function readLong() {
		$this->checkBuffer(8);
		$lower = $this->readInt();
		$higher = $this->readInt();
		$res = $higher << 32 | $lower;
		return $res;
	}

	function writeString($data) {
		$this->writeInt(strlen($data));
		fwrite($this->buffer, $data, strlen($data));
		$this->bufferLen += strlen($data);
		$this->bufferPos += strlen($data);
	}

	public function writeBSON($data) {
		$paramtype = gettype($data);
		if ($paramtype != "object") {
			throw new DjondbException(601, "Illegal argument type. Expecting writeBSON expects object(stdClass)");
		}
		$data = (array)$data;
		$len = sizeof($data);
		$this->writeLong($len);
		$INT32_MAX = 0xffffffff;
		foreach ($data as $key => $value) {
			$this->writeString($key);
			$elementtype = gettype($value);
			switch ($elementtype) {
				case "integer": 
					if ($value > $INT32_MAX) {
						$this->writeLong(2); // LONG
						$this->writeLong($value);
					} else {
						$this->writeLong(0); // INT
						$this->writeInt($value);
					}
					break;
				case "double": 
					$this->writeLong(1);
					$this->writeDouble($value);
					break;

				case "string": 
					$this->writeLong(4);
					$this->writeString($value);
					break;

				case "object": 
					$this->writeLong(5);
					$this->writeBSON($value);
					break;

				case "array": 
					$this->writeLong(6);
					$this->writeBSONArray($value);
					break;

				case "boolean": 
					$this->writeLong(10);
					$this->writeBool($value);
					break;

				default:
					throw new DjondbException(601, "Unsupported datatype $elementtype");
					break;
			}
		}
	}

	public function readBSON() {
		$elements = $this->readLong();
		$result = array();
		for ($x = 0; $x < $elements; $x++) {
			$key = $this->readString();
			$datatype = $this->readLong();

			switch ($datatype) {
			case 0:
					$val = $this->readInt();
					break;
			case 1:
					$val = $this->readDouble();
					break;
			case 2:
					$val = $this->readLong();
					break;
			case 4:
					$val = $this->readString();
					break;
			case 5:
					$val = $this->readBSON();
					break;
			case 6:
					$val = $this->readBSONArray();
					break;
			case 10:
					$val = $this->readBoolean();
					break;
			default:
					throw new DjondbException(601, "Unsupported datatype $datatype");
					break;
			};

			$result[$key] = $val;
		};
		return (object)$result;
	}

	
	function readString() {
		$len = $this->readInt();
		$this->checkBuffer($len);
		$res = fread($this->buffer, $len);
		return $res;
	}

	function seek($pos) {
		fseek($this->buffer, $pos);
		$this->bufferPos = $pos;
	}

	function currentPosition() {
		return $this->bufferPos;
	}

	function size() {
		return $this->bufferLen;
	}

	function writeRaw($data, $len) {
		fwrite($this->buffer, $data, $len);
		$this->bufferLen += $len;
		$this->bufferPos += $len;
	}

	function writeBoolean($val) {
		if ($val) {
			$this->writeChar(chr(1));
		} else {
			$this->writeChar(chr(0));
		}
	}

	function writeChar($val) {
		fwrite($this->buffer, $val, 1);
		$this->bufferLen += 1;
		$this->bufferPos += 1;
	}

	function readChar() {
		$this->checkBuffer(1);
		$res = fread($this->buffer, 1);
		$this->bufferPos += 1;
		return ord($res);
	}

	function readBoolean() {
		$res = ($this->readChar() == 1);
		return $res;
	}

	function writeDouble($val) {
		fwrite($this->buffer, double_toendian($val), 8);
		$this->bufferLen += 8;
		$this->bufferPos += 8;
	}

	function readDouble() {
		$this->checkBuffer(8);
		$read = fread($this->buffer, 8);
		$res = double_fromendian($read);
		$this->bufferPos += 8;
		return $res;
	}

	function writeBSONArray($data) {
		$arraySize = sizeof($data);
		$this->writeLong($arraySize);
		for ($i = 0; $i < $arraySize; $i++) {
			$bson = $data[$i];
			$this->writeBSON($bson);
		}
	}

	function readBSONArray() {
		$arraySize = $this->readLong();
		$res = array();
		for ($i = 0; $i < $arraySize; $i++) {
			$bson = $this->readBSON();
			$res[$i] = $bson;
		}
		return $res;
	}

	function getContents() {
		$posToRestore = $this->currentPosition();
		$this->seek(0);
		$res = fread($this->buffer, $this->bufferLen);
		$this->seek($posToRestore);
		return $res;
	}
}


?>



