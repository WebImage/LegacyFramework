<?php

FrameworkManager::loadDAO('sku');

class SkuLogic {
	
	public static function getSkuByCode($code) {
		$sku_dao = new SkuDAO();
		return $sku_dao->getSkuByCode($code);
	}
	
	public static function getSkuById($sku_id) {
		$sku_dao = new SkuDAO();
		return $sku_dao->load($sku_id);
	}
	public static function save($sku_struct) {
		$sku_dao = new SkuDAO();
		return $sku_dao->save($sku_struct);
	}
	
	//
	// Cross Selling
	//
	public static function getCrossSellsBySkuId($sku_id) {
		
		$dao = new CrosSellDAO();
		return $dao->getCrossSellsBySkuId($sku_id);
		
	}
	public static function getCrossSellRelationship($sku_id, $related_sku_id) {
		
		$dao = new CrosSellDAO();
		return $dao->getCrossSellRelationship($sku_id, $related_sku_id);
		
	}
	public static function removeCrossSell($sku_id, $related_sku_id) {
		
		$dao = new CrosSellDAO();
		return $dao->delete($sku_id, $related_sku_id);
		
	}
	public static function addProductXSell($sku_id, $related_sku_id, $sortorder) {
		
		$dao = new CrosSellDAO();
		
		// Check if relationship exists
		if ($cross_sell_struct = SkuLogic::getCrossSellRelationship($sku_id, $related_sku_id)) {
			// If it does, but sortorder has not changed then we can exit
			if ($cross_sell_struct->sortorder == $sortorder) {
				return $cross_sell_struct;
			}
		} else {
			$dao->setForceInsert(true);
			
			$cross_sell_struct = new CrossSellStruct();
			$cross_sell_struct->sku_id = $sku_id;
			$cross_sell_struct->related_sku_id = $related_sku_id;
			$cross_sell_struct->sortorder = $sortorder;
		}
		return $dao->save($cross_sell_struct);
	}
	
}
class CrossSellDAO extends DataAccessObject {
	var $modelName = 'CrossSellStruct';
	var $updateFields = array('created', 'created_by', 'sortorder', 'updated', 'updated_by');
	var $primaryKey = array('sku_id', 'related_sku_id');
	public static function __construct() {
		$this->tableName = DatabaseManager::getTable('cross_sells');
	}
	
	public static function getCrossSellsBySkuId($sku_id) {
		
		$sql_select = "
			SELECT 
				cross_sells.sku_id, cross_sells.related_sku_id, cross_sells.sortorder,
				products.code AS product_code, products.description AS product_description, products.id AS product_id, products.manufacturer_id AS product_manufacturer_id, products.meta_class_id AS product_meta_class_id, products.name AS product_name, products.template_id AS product_template_id,
				skus.code AS sku_code, skus.cycle_length AS sku_cycle_length, skus.cycle_mode AS sku_cycle_mode, skus.description AS sku_description, skus.license_agreement_id AS sku_license_agreement_id, skus.max_cycles_count AS sku_max_cycles_count, skus.meta_class_id AS sku_meta_class_id, skus.name AS sku_name, skus.out_of_stock_visible AS sku_out_of_stock_visible, skus.package_id AS sku_package_id, skus.package_qty AS sku_package_qty, skus.price AS sku_price, skus.reorder_min_qty AS sku_reorder_min_qty, skus.reserve_qty AS sku_reserve_qty, skus.ship_enabled AS sku_ship_enabled, skus.sku_template_id AS sku_sku_template_id, skus.sku_type AS sku_sku_type, skus.sn_package_id AS sku_sn_package_id, skus.stock_qty AS sku_stock_qty, skus.tax_category_id AS sku_tax_category_id, skus.warehouse_id AS sku_warehouse_id, skus.weight AS sku_weight
			FROM `" . $this->tableName . "` cross_sells
				INNER JOIN `" . DatabaseManager::get('skus') . "` skus ON skus.id = cross_sells.related_sku_id
				INNER JOIN `" . DatabaseManager::get('products') . "` products ON products.id = skus.product_id
			WHERE 
				cross_sells.sku_id = '" . $this->safeString($sku_id) . "' AND
				skus.enable = 1 AND
				products.enable = 1 AND 
				skus.visible = 1
			ORDER BY cross_sells.sortorder, skus.sortorder";
		
		return $this->selectQuery($sql_select);
		
	}
	
	public static function getCrossSellRelationship($sku_id, $related_sku_id) {
		
		$sql_select = "
			SELECT *
			FROM `" . $this->tableName . "` cross_sells
			WHERE 
				sku_id = '" . $this->safeString($sku_id) . "' AND 
				related_sku_id = '" . $this->safeString($related_sku_id) . "'";
		
		return $this->selectQuery($sql_select, 'CrossSellStruct')->getAt(0);
		
	}
	
	public static function delete($sku_id, $related_sku_id) {
		
		$sql_command = "
			DELETE *
			FROM `" . $this->tableName . "` cross_sells
			WHERE 
				sku_id = '" . $this->safeString($sku_id) . "' AND 
				related_sku_id = '" . $this->safeString($related_sku_id) . "'";
		
		return $this->commandQuery($sql_command);
		
	}
}
?>