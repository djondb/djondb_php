<?php

namespace djondb;

use djondb\DjondbCursor;

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
					// Unsupported from executeQuery discard results
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

?>
