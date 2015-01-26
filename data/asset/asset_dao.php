<?php
/**
 * DataAccessObject for Assets
 * 
 * @author Robert Jones II <support@corporatewebimage.com>
 * @copyright Copyright (c) 2007 Corporate Web Image, Inc.
 * @package DataAccessObject
 * @version 1.0 (10/01/2007), Athena_v1.0
 */

// Load required data structures
FrameworkManager::loadStruct('asset');

class AssetDAO extends DataAccessObject {
	
	var $tableName;
	var $modelName = 'AssetStruct';
	var $primaryKey = 'id';
	var $searchJoins = array(
		'asset_folders'	=> array(
			'columns'	=> 'asset_folders.name AS folder_name',
			'join_criteria' => 'asset_folders.id = assets.folder_id',
			'join_type'	=> 'LEFT'
			),
		'asset_types'		=> array(
			'columns'	=> 'asset_types.name AS asset_type_name',
			'join_criteria'	=> 'asset_types.id = assets.asset_type_id',
			'join_type'	=> 'LEFT'
			)
		);

	#var $updateFields = array('caption', 'created', 'created_by', 'asset_type_id', 'config', 'description', 'display_date', 'file_src', 'folder_id', 'height', 'manageable', 'options', 'original_file_name', 'properties','type_id', 'width', 'updated', 'updated_by');
	var $updateFields = array('caption','category_id','created','created_by','description','enable','file_src','folder_id','manageable','original_file_name','parent_id','properties','updated','updated_by','variation_key','version');

	function __construct() {
		$this->tableName = DatabaseManager::getTable('assets');
	}
	
	public function getBaseSearch() {
		
		FrameworkManager::loadLibrary('db.daosearch');
		$search = new DAOSearch('assets');
		
		$search->addJoin( new DAOJoin(
			'asset_folders', 
			DAOJoin::JOIN_LEFT, 
			array(
				'asset_folders.id'=>'assets.folder_id'
			),
			array(
				'folder' => 'folder_path',
				'name' => 'folder_name',
				'parent_id' => 'folder_parent_id',
			)
		) );
		
		return $search;
	}
	
	public function getAssetById($id) {
		
		$search = $this->getBaseSearch();
		$search->addSearchField( new DAOSearchField('assets', 'id', $id) );
		
		$query = $this->search($search);
		return $query->getAt(0);
	}

	public function searchManageableAssets($folder_id=null, $asset_type_id=null, $width_min=null, $width_max=null, $height_min=null, $height_max=null, $file_src=null, $current_page=null, $results_per_page=null) {
		FrameworkManager::loadLibrary('db.daosearch');
		$search = new DAOSearch('assets', $current_page, $results_per_page);
		
		$search_manageable = new DAOSearchField('assets', 'manageable', 1);
		$search->addSearchField($search_manageable);
		
		$search->addJoin( new DAOJoin(
			'asset_folders', 
			DAOJoin::JOIN_LEFT, 
			array(
				'asset_folders.id'=>'assets.folder_id'
			),
			array(
				'folder' => 'folder_path',
				'name' => 'folder_name',
				'parent_id' => 'folder_parent_id',
			)
		) );
		
		if (strlen($folder_id) > 0) {
			
			AssetManagerLogic::addSearchFolder($search, $folder_id);
			
		}
		
		if (!empty($asset_type_id)) {
			$search_asset_type = new DAOSearchField('assets', 'asset_type_id', $asset_type_id);
			$search->addSearchField($search_asset_type);
		}

		return $this->search($search);
	}
	
}


class AssetSearchDAO extends AssetDAO {

}


?>