<!DOCTYPE html>
<html>
<head>
<cms:Script file="%DIR_WS_GASSETS_JS%bootstrap.min.js" />
<cms:PageHeader debug="true" /></head>
<body>
	
	<div class="navbar navbar-default navbar-admin">
		<div class="container-fluid">
			<div class="navbar-header">
				<a href="<cms:ConfigValue name="DIR_WS_ADMIN" />" class="navbar-brand">AthenaCMS</a>
				<?php /* <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#navbar-collapse-1">
		            <span class="sr-only">Toggle navigation</span>
		            <span class="icon-bar"></span>
		            <span class="icon-bar"></span>
		            <span class="icon-bar"></span>
		          </button> */ ?>
			</div>
			<div id="navbar-collapse-1" class="collapse navbar-collapse">
				<cms:AdminNav id="admin-menu"/>
				<ul class="nav navbar-nav navbar-right">
					<li><a href="#"><?php echo ConfigurationManager::get('DOMAIN') ?></a></li>
					<li><a href="mailto:support@corporatewebimage.com"><i class="icon-envelope"></i> Email Support</a></li>
				</ul>
			</div>
		</div>
	</div>
	
      <cms:BreadCrumb id="breadcrumb" class="breadcrumb" />
	
	<div id="admin-body-container">
		<div id="admin-body">
			<cms:Error class="alert alert-danger header-alert" />
			<cms:Notification class="alert alert-info header-alert" />
			
			<cms:PlaceHolder id="ph_admin_main" />
		</div>
	</div>
</div>
<div id="footer-bottom">&copy; <?php echo date('Y') ?> Corporate Web Image, Inc.</div>
<script type="text/javascript">
	//$('input:submit, a.button').button();
	$('.dropdown-toggle').dropdown();
</script>
</body>
</html>