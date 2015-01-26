<?php

FrameworkManager::loadDAO('form');
class FormLogic {
	public static function getForms() {
		$form_dao = new FormDAO();
		return $form_dao->loadAll();
	}
	public static function getFormById($id) {
		$form_dao = new FormDAO();
		return $form_dao->load($id);
	}
	public static function save($form_struct) {
		$form_dao = new FormDAO();
		return $form_dao->save($form_struct);
	}
}

?>