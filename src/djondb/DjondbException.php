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

?>
