<?php
return array(
	'VERSION' => 1.0,
	'settings' => array(
		'general' => array(
			'SYNC_SERVER' => 'http://sync.athenacms.com',

			'DIR_WS_ADMIN' => '/admin/', /* Website location for admin */
			'DIR_WS_ADMIN_CONTENT' => '%DIR_WS_ADMIN%content/', /* Website location for accessing the admin version website content */
			'DIR_WS_HOME' => '/', /* Website location for home directory */

			/* Admin Assets */
			'DIR_WS_ADMIN_ASSETS' => '/assets/admin/',
			'DIR_WS_ADMIN_ASSETS_IMG' => '%DIR_WS_ADMIN_ASSETS%img/',
			'DIR_WS_ADMIN_ASSETS_CSS' => '%DIR_WS_ADMIN_ASSETS%css/',
			'DIR_WS_ADMIN_ASSETS_JS' => '%DIR_WS_ADMIN_ASSETS%js/',

			/* Site Specific Assets */
			/*
			'DIR_WS_ASSETS' => '/assets/%SITE_KEY%/',
			'DIR_WS_ASSETS_IMG' => '%DIR_WS_ASSETS%img/',
			'DIR_WS_ASSETS_CSS' => '%DIR_WS_ASSETS%css/',
			'DIR_WS_ASSETS_JS' => '%DIR_WS_ASSETS%js/',
			'DIR_WS_ASSETS_WMS' => '%DIR_WS_ASSETS%wms/',
			*/
			'DIR_WS_ASSETS' => '/assets/',
			'DIR_WS_ASSETS_IMG' => '%DIR_WS_ASSETS%img/',
			'DIR_WS_ASSETS_CSS' => '%DIR_WS_ASSETS%css/',
			'DIR_WS_ASSETS_JS' => '%DIR_WS_ASSETS%js/',
			'DIR_WS_ASSETS_WMS' => '%DIR_WS_ASSETS%',

			/* Global Assets */
			'DIR_WS_GASSETS' => '/assets/global/',
			'DIR_WS_GASSETS_IMG' => '%DIR_WS_GASSETS%img/',
			'DIR_WS_GASSETS_CSS' => '%DIR_WS_GASSETS%css/',
			'DIR_WS_GASSETS_JS' => '%DIR_WS_GASSETS%js/',

			'DIR_FS_TMP' => '%DIR_FS_FRAMEWORK_APP%tmp/',
			/* 'DIR_FS_PLUGINS' => '%DIR_FS_TMP%plugins/',*/
			'DIR_FS_PLUGINS' => '%DIR_FS_FRAMEWORK_APP%plugins/',

			'DIR_FS_CACHE' => '%DIR_FS_TMP%cache/',
			/* 'DIR_FS_ASSETS_WMS' => '%DIR_FS_HOME%assets/%SITE_KEY%/wms/', */
			/* 'DIR_FS_ASSETS_WMS' => '%DIR_FS_HOME%assets/', */
			'DIR_FS_ASSETS' => '%DIR_FS_HOME%assets/',
			'DIR_FS_ASSETS_WMS' => '%DIR_FS_ASSETS%', /* Legacy */

			'ENABLE_CACHE' => 'true',
			'ENABLE_LOCALES' => 'false',
			'ENABLE_ADMIN_LOCALES' => 'false',
			'ENABLE_PROFILES' => 'false',
			'ENABLE_ADMIN_PROFILES' => 'false',
			'ENABLE_PAGE_STATS' => 'true',

			'FILE_WS_LOGIN' => '%DIR_WS_HOME%login.html',
			'FILE_WS_ADMIN_LOGIN' => '%FILE_WS_LOGIN%',
			'URL_LOGIN' => '%FILE_WS_LOGIN%',
			'SESSION_KEY_PREPEND' => '', /* Value to prepend to session key */
			'PHONE_HOME' => 'no',
			'LOG_LEVEL' => 'error',
			'EMAIL_ERRORS' => 'errors@athenacms.com',
			'EMAIL_LOGS' => 'logs@athenacms.com',
			'EMAIL_NOTIFICATIONS' => 'rjones@corporatewebimage.com',

			'ENCKEY' => '95kdka%lk$dj50a9b_*$%@&}29{|al4|\';lkd$kkdhji@0dkjv98L$89lsdf~19dkl4k$(l$)kdkml2djhhmvnx47U$j&*$(9k31',
			/* NOT YET USED
			'LINK_STORE_PRODUCT' => '%DIR_WS_HOME%products/{product.name}-c{category.id}-p{product.id}',
			'LINK_STORE_CATEGORY' => '%DIR_WS_HOME%shop/{category.name}',*/

			'STORE_USE_SECURE_CHECKOUT' => 'true',
			'STORE_EMAIL_ORDER_NOTIFICATION' => 'orders@athenacms.com',

			'THEME' => 'xyz', /* Front End / While Editing */
			'THEME_ADMIN' => 'athenacms', /* Admin / Back-end */
			'THEME_ADMIN_CONTENT' => 'athenacms', /* Admin / Back-end */
			'THEME_ASSETMANAGER' => 'xyz',

			'DATABASE_CACHE_RESULTS' => 'true',
			/*
			'BLOG_PERMALINK_FORMAT' => '/blog/{$year}/{$month}/{$day}/{$slug}.{$output_format}',
			*/
		),
		'custodian' => array(
			'enable' => true
		)
	),
	'pages' => array(
		'requestHandlers' => array(
			'File' => array(
				'classFile' => '~base/libraries/providers/requesthandlers/file/file.php',
				'className' => 'FileRequestHandler',
				'sortorder' => '1'
			),
			'Database' => array(
				'name' => 'Database',
				'classFile' => '~base/libraries/providers/requesthandlers/database/database.php',
				'className' => 'DatabaseRequestHandler',
				'sortorder' => '2'
			),
			/*
			 * Error: Primarily used by 404 Page Not Found
			 * This page request handler will handle any requests that get this far.  
			 */
			'Error' => array(
				'classFile' => '~base/libraries/providers/requesthandlers/error/error.php',
				'className' => 'ErrorRequestHandler',
				'sortorder' => '1000'
			),
			/*
			 * These page request handlers will never be checked because the error handler above intercepts everything
			 * Items here can only be called directly using a pathMappings entry below
			 */
			/*array(
				'name' => 'Store',
				'classFile' => '~/plugins/store/libraries/providers/requesthandlers/store/store.php',
				'className' => 'StoreRequestProvider'
			),*/
			'Admin' => array(
				'classFile' => '~base/libraries/providers/requesthandlers/admin/admin.php',
				'className' => 'AdminRequestHandler'
			),
			'Blog' => array(
				'classFile' => '~base/libraries/providers/requesthandlers/blog/blog.php',
				'className' => 'BlogRequestHandler'
			),
		),
		'pathMappings' => array(
			array(
				'path' => '^/admin/(.*)',
				'requestHandler' => 'Admin',
				'themeKey' => 'admin'
			),
			/*array(
				'path' => '^/products(/?.+\.html)',
				'translate' => '/shop/productview.html?prodkey=$1',),
			),
			array(
				'path' => '^/shop/?(index\.html)?',
				'translate' => '/shop/categoryview.html?catkey=',
				'requestHandler' => 'Store'
			),
			array(
				'path' => '^/shop/(.+\.html)',
				'translate' => '/shop/categoryview.html?catkey=$1', 
				'exampleFile' => '~/shop/categoryview.html?catkey=$1',
				'requesthandler' => 'Store'
			),
			array(
				'path' => '^/blog.*',
				'__translate' => '/view.html?year=$1&month=$2&day=$3&slug=$4',
				'requestHandler' => 'Blog'
			),*/
		),
		'requireSecureConnection' => array(
			array(
				'path' => '/checkout/.*'
			)
		)
		/* UNUSED
		'location' => array(
			array(
				'path' => '/admin/.*',
				'theme' => 'athenacms'
			),
			array(
				'path' => '/admin/content/.*',
				'theme' => 'athenacms'
			)
		)
		*/
	),
	'membership' => array(
		'defaultProvider' => 'SqlMemberProvider',
		'providers' => array(
			'SqlMemberProvider' => array(
				'classFile' => '~/libraries/providers/members/sqlmembershipprovider.php',
				'className' => 'SqlMembershipProvider',
				'loginUrl' => '/login.php',
				'requireSSL' => 'false',
				'slidingExpiration' => 'true',
				'applicationName' => 'MyApplication'
				),
			'ListingMembershipProvider' => array(
				'classFile' => '~/libraries/providers/members/listingmembershipprovider.php',
				'className' => 'ListingMembershipProvider'
				),
			'CartMember' => array(
				'classFile' => '~/libraries/providers/members/cartmember.php',
				'className' => 'SqlCartMembershipProvider',
				'loginUrl' => '/login.php',
				'requireSSL' => 'false',
				'slidingExpiration' => 'true',
				'applicationName' => 'MyApplication'
				)
		)
	),
	'roleManager' => array(
		'defaultProvider' => 'SqlRoleProvider',
		'providers' => array(
			'SqlRoleProvider' => array(
				'classFile' => '~/libraries/providers/roles/sqlroleprovider.php',
				'className' => 'SqlRoleProvider',
				'applicationName' => 'MyApplication',
			),
		),
		'permissions' => array(
			'Admin.Pages' => array('description' => 'Manage site pages'),
			'Admin.Pages.Controls' => array('description' => 'Manage site controls'),
			'Admin.LiveSite' => array('description' => 'Add live site option to pages menu'),
			'Admin.AssetManager' => array('description' => ''),
			'Admin.Roles' => array('description' => 'Manage user roles'),
			'Admin.MetaClasses' => array('description' => 'Manage meta classes'),
			'Admin.MetaFields' => array('description' => 'Manage meta fields'),
			'Admin.Templates' => array('description' => 'Manage content templates'),
			'Admin.Memberships' => array('description' => 'Manage users'),
			'Admin.Memberships.Roles' => array('description' => 'Manage roles'),
			'Admin.Memberships.AssignRoles' => array('description' => 'User roles'),
			'Admin.Memberships.Roles.Permissions' => array('description' => 'Role permissions'),
			'Admin.Cache.Site' => array('description' => 'Manage site cache'),
			'Admin.Cache.GlobalSite' => array('description' => 'Manage global cache (applies to all sites)'),
			'Admin.Cache.Base' => array('description' => 'Manage framework cache'),
			'Admin.Sites' => array('description' => 'Manage sites'),
		)
	),
	'profile' => array(
		'providers' => array(
			'iPhone' => array(
				'className' => '\WebImage\ExperienceProfile\IPhoneProfile',
				'supportedProfiles' => array('Mobile')
				),
			'Mobile' => array(
				'className' => '\WebImage\ExperienceProfile\MobileProfile'
				),
			'Default' => array(
				'className' => '\WebImage\ExperienceProfile\DefaultProfile',
			)
		)
	),
	'cacheManager' => array(
		'defaultProvider' => '',
		'providers' => array(
			'content' => array(
				'classFile' => '~/libraries/providers/cache/cache.php',
				'className' => 'CWI_PROVIDER_CacheProvider',
			),
			'object' => array(
				'classFile' => '~/libraries/providers/cache/object.php',
				'className' => 'CWI_PROVIDER_ObjectCacheProvider',
				),
			'config' => array(
				'classFile' => '~/libraries/providers/cache/object.php',
				'className' => 'CWI_PROVIDER_ObjectCacheProvider',
				),
			'sync' => array(
				'classFile' => '~/libraries/providers/cache/object.php',
				'className' => 'CWI_PROVIDER_ObjectCacheProvider',
				),
			'model' => array(
				'classFile' => '~/libraries/providers/cache/object.php',
				'className' => 'CWI_PROVIDER_ObjectCacheProvider',
				)
		)
	),
	'store' => array(
		'shippingModules' => array(
			'UPS' => array(
				'classFile' => '~/store/modules/shipping/ups.php',
				'className' => 'ShippingUPS',
				'applicationName' => 'MyApplication',
			),
			'Standard' => array(
				'classFile' => '~/store/modules/shipping/standard.php',
				'className' => 'ShippingStandard',
				),
			'FedEx' => array(
				'classFile' => '~/store/modules/shipping/fedex.php',
				'className' => 'ShippingFedEx',
				)
		)
	),
	'search' => array(
		'defaultTemplate' => '~/templates/default.tpl',
		'defaultTemplatePlaceholder' => 'ph_main',
		'providers' => array(
			'ControlSearch' => array(
				'classFile' => '~base/providers/search/controlsearch.php',
				'className' => 'CWI_PROVIDER_SEARCH_ControlSearch',
			)
		)
	),
	'serviceManager' => array(
		'factories' => array(
			'ControlManager' => 'ControlManagerFactory',
			'WebImage\ExperienceProfile\ProfileManager' => 'WebImage\ExperienceProfile\ProfileManagerFactory'
		),
		'aliases' => array(
			'ExperienceProfileManager' => 'WebImage\ExperienceProfile\ProfileManager'
		)
	)
);