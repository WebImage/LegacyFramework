<?php

class CWI_CLASS_SCANNER_ClassCollection extends Collection {
	public function hasValue($value) {
		return in_array($value, $this->getAll());
	}
}

?>