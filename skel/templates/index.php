<!DOCTYPE html>
<html>
<head>
	<title><?php $this->out("title") ?></title>
	<?php foreach($this->get("stylesheets") as $stylesheet): ?>
	<link href="<?php echo $stylesheet ?>" rel="stylesheet" media="screen">
	<?php endforeach; ?>
	<meta charset="UTF-8">
</head>
<body>
	<div class="container">
		<div class="page-header">
			<h1><?php $this->out("title") ?></h1>
		</div>
		
		<?php echo $this->config("description") ?>
	</div>

	<script type="text/javascript">
		window.cf_options = <?php $this->cf_options(); ?>;
	</script>
	<?php foreach($this->get("scripts") as $script): ?>
	<script src="<?php echo $script ?>"></script>
	<?php endforeach; ?>
</body>
</html>
