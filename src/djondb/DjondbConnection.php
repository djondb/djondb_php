<?php

namespace djondb;

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



