<!DOCTYPE html 
     PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
     "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head><cms:Stylesheet file="%DIR_WS_ADMIN_ASSETS_CSS%default.css" /><cms:PageHeader debug="true" /></head>
<body>
<div id="admin-site-container">
	<cms:AdminNav id="admin-menu" />
	
	<cms:WrapOutput wrapClassId="page-title-container">
		<cms:WrapOutput wrapClassId="page-title"><?php echo Page::getTitle() ?></cms:WrapOutput>
		<cms:PlaceHolder id="ph_page_actions" />
	</cms:WrapOutput>
	
      <cms:BreadCrumb id="breadcrumb" class="breadcrumb" />

	<div id="admin-body-container">
		<div id="admin-body">
			<cms:Error />
			<cms:Notification />
			<div style="padding:20px;">
				<div style="border:2px dashed #ccc;padding:10px;">
					<span style="color:red;font-weight:bold;">IMPORTANT NOTICE:</span><br />
					<p>You are working with an outdated version of this page.  Although this page will probably continue to function correctly, you are advised to contact support as soon as possible with the following information:</p>
					<p style="color:red;">Error: masterPageFile used instead of templateId<br />
					File: <?php echo Page::getRequestedPath() ?></p>
				</p>
			</div>
			<div class="module-container">
				<cms:PlaceHolder id="ph_admin_main">
					<?php
					if ($meta_description = Page::getMetaTag('description') && isset($meta_description) && strlen($meta_description) > 0) 
					echo '<div class="meta_description">' . $meta_description . '</div>';
					?>
				</cms:PlaceHolder>
			</div>
		</div>
	</div>
</div>
<div id="footerbottom"><div><div>&copy; <?php echo date('Y') ?> Corporate Web Image, Inc.</div></div></div>
<div id="header-bar">
	<div id="admin-logo"><a href="<?php echo ConfigurationManager::get('DIR_WS_ADMIN') ?>"><img src="<?php echo ConfigurationManager::get('DIR_WS_ADMIN_ASSETS_IMG') ?>logo.png" width="235" height="37" alt="AthenaCMS - Currently editing <?php echo ConfigurationManager::get('SITE_NAME') ?>" border="0" /></a></div>
	<div id="site-info">
		<div id="info-icon">
			<img src="<?php echo ConfigurationManager::get('DIR_WS_ADMIN_ASSETS_IMG') ?>tpl/i_info.png" width="20" height="20" border="0" />
		</div>
		<div>
			<span id="siteid-editing">Editing: <?php echo ConfigurationManager::get('SITE_NAME') ?></span><br />
			<span id="siteid-domain"><?php echo ConfigurationManager::get('DOMAIN') ?></span>
		</div>
		<div id="comment-icon">
			<a href="mailto:support@athenacms.com"><img src="<?php echo ConfigurationManager::get('DIR_WS_ADMIN_ASSETS_IMG') ?>tpl/i_comment.png" width="28" height="24" border="0" /></a>
		</div>
	</div>
</div>

</body>
</html>