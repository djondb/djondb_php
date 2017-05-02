<?php

namespace djondb;

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

?>



