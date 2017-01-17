<?php

namespace djondb_php;

class DjondbException extends \Exception {
	public function __construct($code, $description, $previous = null) {
		parent::__construct($description, $code, $previous);
		$this->code = $code;
		$this->description = $description;
	}

	public function __toString() {
		return "Error: $this->code. Description: $this->description";
	}
};

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

abstract class CursorStatus {
	const CS_LOADING = 1;
	const CS_RECORDS_LOADED = 2;
	const CS_CLOSED = 3;
}

class DjondbCursor {
	public function __construct($net, $cursorId=null, $firstPage=null) {
		$this->_net = $net;
		$this->_cursorId = $cursorId;
		$this->_rows = $firstPage;
		$this->_position = 0;
		$this->_current = null;

		if ($this->_rows == null) {
			$this->_count = 0;
		} else {
			$this->_count = sizeof($this->_rows);
		}

		if ($cursorId != null) {
			$this->_status = CursorStatus::CS_LOADING;
		} else {
			$this->_status = CursorStatus::CS_RECORDS_LOADED;
		}
	}

	public function next() {
		if ($this->_status == CursorStatus::CS_CLOSED) {
			throw new DjondbException("Cursor is closed");
		}
		$result = TRUE;
		if ($this->_count > $this->_position) {
			$this->_current = $this->_rows[$this->_position];
			$this->_position += 1;
		} else {
			if ($this->_status == CursorStatus::CS_LOADING) {
				$cmd = new Command($this->_net);
				$page = $cmd->fetchRecords($this->_cursorId);
				if ($page == null) {
					$this->_status = CursorStatus::CS_RECORDS_LOADED;
					$result = FALSE;
				} else {
					$this->_rows = array_merge($this->_rows, $page);
					$this->_count = sizeof($this->_rows);
					$result = $this->next();
				}
			} else {
				$result = FALSE;
			}
		}
		return $result;
	}

	public function previous() {
		if ($this->_status == CursorStatus::CS_CLOSED) {
			throw new DjondbException("Cursor is closed");
		}
		$result = TRUE;
		if (($this->_count > 0) && ($this->_position > 0)) {
			$this->_position -= 1;
			$this->_current = $this->_rows[$this->_position];
		} else {
			$result = FALSE;
		}
		return $result;
	}

	public function current (){
		return $this->_current;
	}
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


abstract class CommandType {
	const INSERT = 0;
	const UPDATE = 1;
	const FIND = 2;
	const CLOSECONNECTION = 3;
	const DROPNAMESPACE = 4;
	const SHUTDOWN = 5;
	const SHOWDBS = 6;
	const SHOWNAMESPACES = 7;
	const REMOVE = 8;
	const COMMIT = 9;
	const ROLLBACK = 10;
	const FETCHCURSOR = 11;
	const FLUSHBUFFER = 12;
	const CREATEINDEX = 13;
	const BACKUP = 14;
	const RCURSOR = 15;
	const PERSISTINDEXES = 16;
	const EXECUTEQUERY = 17;
	const EXECUTEUPDATE = 18;
}

class Command {
	public function __construct($net) {
		$this->net = $net;
		$this->_activeTransactionId = null;
		$this->resultCode = 0;
		$this->resultMessage = null;
	}

	public function writeHeader() {
		$version = "3.5.60822";
		$this->net->writeString($version);
	}

	public function readErrorInformation() {
		$this->resultCode = $this->net->readInt();
		if ($this->resultCode > 0) {
			$this->resultMessage = $this->net->readString();
		}
	}

	public function readResultShowDbs() {
		$results = $this->net->readInt();
		$dbs = array();
		for ($x = 0; $x < $results; $x++) {
			$dbs[$x] = $this->net->readString();
		}

		$this->readErrorInformation();
		return $dbs;
	}

	public function showDbs() {
		$this->net->reset();
		$this->writeHeader();
		$this->net->writeInt(CommandType::SHOWDBS);
		$this->writeOptions();
		$this->net->flush();
		return $this->readResultShowDbs();
	}

	public function writeOptions() {
		$options = array();
		if ($this->_activeTransactionId != null) {
			$options["_transactionId"] = $this->_activeTransactionId;
		}
		$this->net->writeBSON((object)$options);
	}

	public function readResultShowNamespaces() {
		$results = $this->net->readInt();
		$dbs = array();
		for ($x = 0; $x < $results; $x++) {
			$dbs[$x] = $this->net->readString();
		}

		$this->readErrorInformation();
		return $dbs;
	}

	public function showNamespaces($db) {
		$this->net->reset();
		$this->writeHeader();
		$this->net->writeInt(CommandType::SHOWNAMESPACES);
		$this->writeOptions();
		$this->net->writeString($db);
		$this->net->flush();
		return $this->readResultShowNamespaces();
	}

	public function readResultDropNamespace() {
		$result = $this->net->readInt();

		$this->readErrorInformation();
		return True;
	}

	public function dropNamespace($db, $ns, $transactionId = null) {
		$this->net->reset();
		$this->writeHeader();
		$this->net->writeInt(CommandType::DROPNAMESPACE);
		$this->writeOptions();
		$this->net->writeString($db);
		$this->net->writeString($ns);
		$this->net->flush();
		return $this->readResultDropNamespace();
	}

	public function readResultRemove() {
		$res = $this->net->readBoolean();
		$this->readErrorInformation();
		return $res;
	}

	public function remove($db, $ns, $id, $revision = null) {
		$this->net->reset();
		$this->writeHeader();
		$this->net->writeInt(CommandType::REMOVE);
		$this->writeOptions();
		$this->net->writeString($db);
		$this->net->writeString($ns);
		$this->net->writeString($id);

		if ($revision != null) {
			$this->net->writeString($revision);
		} else {
			$this->net->writeString("");
		}

		$this->net->flush();
		return $this->readResultRemove();
	}



	public function readResultInsert() {
		$result = $this->net->readInt();
		$this->readErrorInformation();
		return $result;
	}

	public function insert($db, $ns, $data) {
		$this->net->reset();
		$this->writeHeader();
		$this->net->writeInt(CommandType::INSERT); # Insert command
		$this->writeOptions();
		$this->net->writeString($db);
		$this->net->writeString($ns);
		$this->net->writeBSON($data);
		$this->net->flush();
		return $this->readResultInsert();
	}


	public function readResultUpdate() {
		$result = $this->net->readBoolean();
		$this->readErrorInformation();
		return $result;
	}

	public function update($db, $ns, $data) {
		$this->net->reset();
		$this->writeHeader();
		$this->net->writeInt(CommandType::UPDATE); # Update command;
		$this->writeOptions();
		$this->net->writeString($db);
		$this->net->writeString($ns);
		$this->net->writeBSON($data);
		$this->net->flush();
		return $this->readResultUpdate();
	}

	public function readResultFind() {
		$cursorId = $this->net->readString();
		$flag = $this->net->readInt();
		$results = array();
		if ($flag == 1) {
			$results = $this->net->readBSONArray();
		}

		$result = new DjondbCursor($this->net, $cursorId, $results);
		$this->readErrorInformation();
		return $result;
	}

	public function find($db, $ns, $select, $filter) {
		$this->net->reset();
		$this->writeHeader();
		$this->net->writeInt(CommandType::FIND); # find command
		$this->writeOptions();
		$this->net->writeString($db);
		$this->net->writeString($ns);
		$this->net->writeString($filter);
		$this->net->writeString($select);
		$this->net->flush();

		return $this->readResultFind();
	}

	public function readResultFetchRecords() {
		$flag = $this->net->readInt();
		$results = null;
		if ($flag == 1) {
			$results = $this->net->readBSONArray();
		}
		
		$this->readErrorInformation();

		return $results;
	}

	public function fetchRecords($cursorId) {
		$this->net->reset();
		$this->writeHeader();
		$this->net->writeInt(CommandType::FETCHCURSOR); # find command
		$this->writeOptions();
		$this->net->writeString($cursorId);
		$this->net->flush();
		return $this->readResultFetchRecords();
	}

	public function beginTransaction() {
		$this->_activeTransactionId = uniqid();
		return $this->_activeTransactionId;
	}

	public function readResultCommitTransaction() {
		$this->readErrorInformation();
	}

	public function commitTransaction() {
		if ($this->_activeTransactionId != null) {
			$this->net->reset();
			$this->writeHeader();
			$this->net->writeInt(CommandType::COMMIT); # find command
			$this->writeOptions();
			$this->net->writeString($this->_activeTransactionId);
			$this->net->flush();

			$this->readResultCommitTransaction();
			$this->_activeTransactionId = null;
		} else {
			throw new DjondbException(10001, 'Nothing to commit, you need beginTransaction before committing or rollback');
		}
	}

	public function readResultRollbackTransaction() {
		$this->readErrorInformation();
	}

	public function rollbackTransaction() {
		if ($this->_activeTransactionId != null) {
			$this->net->reset();
			$this->writeHeader();
			$this->net->writeInt(CommandType::ROLLBACK); # find command
			$this->writeOptions();
			$this->net->writeString($this->_activeTransactionId);
			$this->net->flush();

			$this->readResultRollbackTransaction();
			$this->_activeTransactionId = null;
		} else {
			throw new DjondbException(10001, 'Nothing to rollback, you need beginTransaction before committing or rollback');
		}
	}

	public function readResultCreateIndex() {
		$this->readErrorInformation();
	}

	public function createIndex($indexDef) {
		$this->net->reset();
		$this->writeHeader();
		$this->net->writeInt(CommandType::CREATEINDEX); # createindex command
		$this->writeOptions();
		$this->net->writeBSON($indexDef);
		$this->net->flush();
		return readResultCreateIndex();
	}

	public function readResultBackup() {
		$result =  $this->readInt();
		$this->readErrorInformation();
		return $result;
	}

	public function backup($db, $destFile) {
		$this->net->reset();
		$this->writeHeader();
		$this->net->writeInt(CommandType::BACKUP); # createindex command
		$this->writeOptions();
		$this->writeString($db);
		$this->writeString($destFile);
		$this->net->flush();
		return $this->readResultBackup();
	}

	public function executeQuery($query) {
		$this->net->reset();
		$this->writeHeader();
		$this->net->writeInt(CommandType::EXECUTEQUERY); # executequery command
		$this->writeOptions();
		$this->net->writeString($query);
		$this->net->flush();

		$flag = $this->net->readInt();
		$cursorResult = null;
		if ($flag == 1) {
			$commandType = $this->net->readInt();
			switch ($commandType) {
				case CommandType::INSERT:
					$this->readResultInsert();
					break;

				case CommandType::UPDATE:
					$this->readResultUpdate();
					break;

				case CommandType::FIND:
					$cursorResult = $this->readResultFind();
					break;

				case CommandType::DROPNAMESPACE:
					$this->readResultDropNamespace();
					break;

				case CommandType::SHOWDBS:
					$dbs = $this->readResultShowDbs();
					$this->readErrorInformation();
					$arrDbs = array();
					foreach ($dbs as $db) {
						$row = array();
						$row["db"] = $db;
						array_push($arrDbs, (object)$row);
					}
					$cursorId = null;
					$cursorResult = new DjondbCursor($net, $cursorId, $arrDbs);
					break;

				case CommandType::SHOWNAMESPACES:
					$nss = $this->readResultShowNamespaces();
					$this->readErrorInformation();
					$arrNs = array();
					foreach ($ns as $nss) {
						$row = array(); 
						$row["ns"] = $ns;
						array_push($arrNs, (object)$row);
					}
					$cursorId = null;
					$cursorResult = new DjondbCursor($net, $cursorId, $arrNs);
					break;

				case CommandType::REMOVE:
					$this->readResultRemove();
					break;

				case CommandType::COMMIT:
					$this->readResultCommitTransaction();
					$this->_activeTransactionId = null;
					break;

				case CommandType::ROLLBACK:
					$this->readResultRollbackTransaction();
					$this->_activeTransactionId = null;
					break;

				case CommandType::FETCHCURSOR:
					$this->readResultFetchRecords();
					break;

				case CommandType::CREATEINDEX:
					$this->readResultCreateIndex();
					break;

				case CommandType::BACKUP:
					$this->readResultBackup();
					break;
			}
		} else {
			$this->readErrorInformation();
		}

		if ($cursorResult == null) {
			$arr = array();
			$row = array();
			date_default_timezone_set('UTC');
			$row["date"] = date("Y/m/d");
			$row["success"] = TRUE;
			array_push($arr, (object)$row);
			$cursorResult = new DjondbCursor($net, null, $arr);
		}

		return $cursorResult;
	}

	public function executeUpdate($query) {
		$this->net->reset();
		$this->writeHeader();
		$this->net->writeInt(CommandType::EXECUTEUPDATE); # executeupdate command
		$this->writeOptions();
		$this->net->writeString($query);
		$this->net->flush();

		$flag = $this->net->readInt();
		if ($flag == 1) {
			$commandType = $this->net->readInt();
			switch ($commandType) {
				case CommandType::INSERT:
					return $this->readResultInsert();

				case CommandType::UPDATE:
					return $this->readResultUpdate();

				case CommandType::DROPNAMESPACE:
					return $this->readResultDropNamespace();

				case CommandType::REMOVE:
					return $this->readResultRemove();

				case CommandType::COMMIT:
					$res = $this->readResultCommitTransaction();
					$this->_activeTransactionId = null;
					return $res;

				case CommandType::ROLLBACK:
					$res = $this->readResultRollbackTransaction();
					$this->_activeTransactionId = null;
					return $res;

				case CommandType::CREATEINDEX:
					return $this->readResultCreateIndex();

				case CommandType::BACKUP:
					return $this->readResultBackup();
			}
		} else {
			$this->readErrorInformation();
		}
		return null;
	}
}

class DjondbConnection {
	public function __construct($host, $port = 1423) {
		$this->host = $host;
		$this->port = $port;
	}

	public function checkError($cmd) {
		if ($this->cmd->resultCode > 0) {
			throw new DjondbException($this->cmd->resultCode, $this->cmd->resultMessage);
		}
	}

	public function open() {
		$this->network = new Network();
		$this->network->connect($this->host, $this->port);
		$this->cmd = new Command($this->network);
	}

	public function showDbs() {
		$res = $this->cmd->showDbs($this->network);
		$this->checkError($this->cmd);
		return $res;
	}

	public function showNamespaces($db) {
		$res = $this->cmd->showNamespaces($this->network, $db);
		$this->checkError($this->cmd);
		return $res;
	}

	public function insert($db, $ns, $data) {
		$res = $this->cmd->insert($db, $ns, $data);
		$this->checkError($this->cmd);
		return $res;
	}

	public function update($db, $ns, $data) {
		$res = $this->cmd->update($db, $ns, $data);
		$this->checkError($this->cmd);
		return $res;
	}

	public function find($db, $ns, $select, $filter) {
		$res =$this->cmd->find($db, $ns, $select, $filter);
		$this->checkError($this->cmd);
		return $res;
	}

	public function dropNamespace($db, $ns) {
		$res =$this->cmd->dropNamespace($db, $ns);
		$this->checkError($this->cmd);
		return $res;
	}

	public function remove($db, $ns, $id, $revision = null) {
		$res =$this->cmd->remove($db, $ns, $id, $revision);
		$this->checkError($this->cmd);
		return $res;
	}

	public function beginTransaction() {
		$this->_activeTransactionId = $this->cmd->beginTransaction();
	}
		
	public function commitTransaction() {
		$this->cmd->commitTransaction($this->network);
		$this->_activeTransactionId = null;
		$this->checkError($this->cmd);
	}
		
	public function rollbackTransaction() {
		$this->cmd->rollbackTransaction($this->network);
		$this->_activeTransactionId = null;
		$this->checkError($this->cmd);
	}

	public function createIndex($indexDef) {
		$this->cmd->createIndex($indexDef);
		$this->checkError($this->cmd);
	}

	public function executeQuery($query) {
		$res =$this->cmd->executeQuery($query);
		$this->checkError($this->cmd);
		return $res;
	}

	public function executeUpdate($query) {
		$res =$this->cmd->executeUpdate($query);
		$this->checkError($this->cmd);
		return $res;
	}
}

?>


