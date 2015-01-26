if (typeof(CWI) === 'undefined') CWI = function() {};
if (typeof(CWI.Assets) === 'undefined') CWI.Assets = function() {};

CWI.Assets.AssetManager = function() {};
CWI.Assets.AssetManager.prototype.resetAssets = function() { this.assets = []; }
CWI.Assets.AssetManager.prototype.addAsset = function(asset) {
	this.assets.push(asset);
	alert('Asset ID' + asset.getId());
}

CWI.Assets.Asset = function() {
	this.assetTypeId;
	this.categoryId; // deprecated
	this.config;
	this.created;
	this.createdBy;
	this.fileSrc;
	this.folderId;
	//this.height;
	this.id;
	this.manageable;
	this.updated;
	this.updatedBy;
	//this.width;
	this.parameters = {};
}

// Getters
CWI.Assets.Asset.prototype.getAssetTypeId = function() { return this.assetTypeId; }
CWI.Assets.Asset.prototype.getCategoryId = function() { return this.categoryId; } // deprecated
CWI.Assets.Asset.prototype.getConfig = function() { return this.config; }
CWI.Assets.Asset.prototype.getCreated = function() { return this.created; }
CWI.Assets.Asset.prototype.getCreatedBy = function() { return this.createdBy; }
CWI.Assets.Asset.prototype.getFileSrc = function() { return this.fileSrc; }
CWI.Assets.Asset.prototype.getFolderId = function() { return this.folderId; }
//CWI.Assets.Asset.prototype.getHeight = function() { return this.height; }
CWI.Assets.Asset.prototype.getId = function() { return this.id; }
CWI.Assets.Asset.prototype.getManageable = function() { return this.manageable; }
CWI.Assets.Asset.prototype.getUpdated = function() { return this.updated; }
CWI.Assets.Asset.prototype.getUpdatedBy = function() { return this.updatedBy; }
//CWI.Assets.Asset.prototype.getWidth = function() { return this.width; }
CWI.Assets.Asset.prototype.getParameter = function(name, defaultValue) {
	if (typeof(this.parameters[name]) == 'undefined') return defaultValue;
	else return this.parameters[name];
}

// Setters
CWI.Assets.Asset.prototype.setAssetTypeId = function(assetTypeId) { this.assetTypeId = assetTypeId; }
CWI.Assets.Asset.prototype.setCategoryId = function(categoryId) { this.categoryId = categoryId; }// deprecated
CWI.Assets.Asset.prototype.setConfig = function(config) { this.config = config; }
CWI.Assets.Asset.prototype.setCreated = function(created) { this.created = created; }
CWI.Assets.Asset.prototype.setCreatedBy = function(createdBy) { this.createdBy = createdBy; }
CWI.Assets.Asset.prototype.setFileSrc = function(fileSrc) { this.fileSrc = fileSrc;  }
CWI.Assets.Asset.prototype.setFolderId = function(folderId) { this.folderId = folderId; }
//CWI.Assets.Asset.prototype.setHeight = function(height) { this.height = height; }
CWI.Assets.Asset.prototype.setId = function(id) { this.id = id; }
CWI.Assets.Asset.prototype.setManageable = function(manageable) { this.manageable = manageable; }
CWI.Assets.Asset.prototype.setUpdated = function(updated) { this.updated = updated; }
CWI.Assets.Asset.prototype.setUpdatedBy = function(updatedBy) { this.updatedBy = updatedBy; }
//CWI.Assets.Asset.prototype.setWidth = function(width) { this.width = width; }
CWI.Assets.Asset.prototype.setParameter = function(name, value) { this.parameters[name] = value; }
/*
CWI.Assets.AssetParameter = function(name, value) {
	this.setName(name);
	this.setValue(value);
}
// Getters
CWI.Assets.AssetParameter.prototype.getName = function() { return this.name; }
CWI.Assets.AssetParameter.prototype.getValue = function() { return this.value; }

// Setters
CWI.Assets.AssetParameter.prototype.setName = function(name) { this.name = name; }
CWI.Assets.AssetParameter.prototype.setValue = function(value) { this.value = value; }
*/