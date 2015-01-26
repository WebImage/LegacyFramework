<?php

FrameworkManager::loadDAO('formfield');
class FormFieldLogic {
	
	public static function getFormFieldsByFormId($form_id) {
		$form_field_dao = new FormFieldDAO();
		return $form_field_dao->getFormFieldsByFormId($form_id);
	}
	public static function getFormFieldByFormIdAndFieldId($form_id, $field_id) {
		$form_field_dao = new FormFieldDAO();
		return $form_field_dao->getFormFieldByFormIdAndFieldId($form_id, $field_id);
	}
	
	public static function getMaxFieldIdForForm($form_id) {
		$form_field_dao = new FormFieldDAO();
		return $form_field_dao->getMaxFieldIdForForm($form_id);
	}
	
	public static function getMaxSortOrderForForm($form_id) {
		$form_field_dao = new FormFieldDAO();
		return $form_field_dao->getMaxSortOrderForForm($form_id);
	}
	
	public static function getNextFieldIdForForm($form_id) {
		return self::getMaxFieldIdForForm($form_id) + 1;
	}
	
	public static function getNextSortOrderForForm($form_id) {
		return self::getMaxSortOrderForForm($form_id) + 1;
	}
	
	public static function save(FormFieldStruct $struct, $force_insert=false) {
		$form_field_dao = new FormFieldDAO();
		if ($force_insert) $form_field_dao->setForceInsert(true);
		return $form_field_dao->save($struct);
	}
	public static function create(FormFieldStruct $struct) {
		FormFieldLogic::save($struct, true);
	}
}

?>