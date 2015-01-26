<?php

class Permission {
	var $m_create, $m_read, $m_update, $m_delete; // Permissions
	
	function Permission($create, $read, $update, $delete) {
		$this->m_create = $create;
		$this->m_read = $read;
		$this->m_update = $update;
		$this->m_delete = $delete;
	}
	function canCreate() { return $this->m_create; }
	function canRead() { return $this->m_read; }
	function canUpdate() { return $this->m_update; }
	function canDelete() { return $this->m_delete; }
	
	function setCreate($allowed) { $this->m_create = $allowed; }
	function setRead($allowed) { $this->m_read = $allowed; }
	function setUpdate($allowed) { $this->m_update = $allowed; }
	function setDelete($allowed) { $this->m_delete = $allowed; }
}