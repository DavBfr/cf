<?php $this->insert("header.php"); ?>
<div class="container">
	<div class="page-header">
		<h1><?php $this->out("title", "tr") ?></h1>
	</div>
	
	<?php echo $this->config("description", "tr") ?>
</div>
<?php $this->insert("footer.php"); ?>
