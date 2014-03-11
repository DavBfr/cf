<?php $this->insert("header.php"); ?>
<div class="container">
	<div class="page-header">
		<h1><?php $this->out("title") ?></h1>
	</div>
	
	<?php echo $this->config("description") ?>
</div>
<?php $this->insert("footer.php"); ?>
