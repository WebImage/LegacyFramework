<?php

FrameworkManager::loadDAO('pageparameter');

class PageParameterLogic {

	public static function getPageParameterById($id) {
		$parameter_dao = new PageParameterDAO();
		return $parameter_dao->load($id);
	}
	
	public static function getPageParametersByPageId($page_id) {
		$parameter_dao = new PageParameterDAO();
		return $parameter_dao->getPageParametersByPageId($page_id);
		
	}
	
	public static function getAllPageParameters() {
		$parameter_dao = new PageParameterDAO();
		return $parameter_dao->loadAll();
	}
	
	public static function save($parameter_struct) {
		$parameter_dao = new PageParameterDAO();
		return $parameter_dao->save($parameter_struct);
	}
	
	public static function delete($param_id) {
		$parameter_dao = new PageParameterDAO();
		return $parameter_dao->delete($param_id);
	}
}

?>