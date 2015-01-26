<?php

FrameworkManager::loadLibrary('store');
FrameworkManager::loadDAO('category');

class CategoryLogic {
	public static function getCategoryById($category_id) {
		$category_dao = new CategoryDAO();
		return $category_dao->load($category_id);
	}
	
	public static function getCategoryBaseById($category_id) {
		$category = CategoryLogic::getCategoryById($category_id);
		if (!empty($category)) {
			$category_base = new CategoryBase($category);
			// Meta fields
			MetaHelper::extend($category->meta_class_id, $category->id, $category_base);
			return $category_base;		
		} else {
			return new CategoryBase();
		}
	}
	public static function save($category_struct) {
		$category_dao = new CategoryDAO();
		return $category_dao->save($category_struct);
	}
	
	public static function getAllCategories() {
		$category_dao = new CategoryDAO();
		return $category_dao->loadAll();
	}
	
	public static function _getAllCategoriesExcept($category_id_array=array()) { // Not currently used anywhere except CategoryLogic::getCategoriesExceptId
		$category_dao = new CategoryDAO();
		return $category_dao->getAllExcept($category_id_array);
	}
	
	public static function getAllCategoriesExceptId($category_id) {
		return CategoryLogic::_getAllCategoriesExcept(array($category_id));
	}
	
	public static function getCategories() {
		$category_dao = new CategoryDAO();
		return $category_dao->getCategories();
	}
	
	public static function getCategoriesByParentId($category_id) {
		$category_dao = new CategoryDAO();
		return $category_dao->getCategoriesByParentId($category_id);
	}
	public static function getAllCategoriesByParentId($category_id) {
		$category_dao = new CategoryDAO();
		return $category_dao->getAllCategoriesByParentId($category_id);
	}
}

?>