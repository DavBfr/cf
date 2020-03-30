<?php $this->insert("header.php"); ?>

<style type="text/css">
	body {
		padding-bottom: 70px;
	}
</style>

<nav class="navbar navbar-default navbar-top" role="navigation">
	<div class="navbar-header">
		<button type="button" class="navbar-toggle" data-toggle="collapse" data-target=".navbar-ex1-collapse">
		<span class="sr-only"><?php $this->tr("core.toggle_navigation") ?></span>
		<span class="icon-bar"></span>
		<span class="icon-bar"></span>
		<span class="icon-bar"></span>
	</button>
		<a class="navbar-brand" href="<?php echo REST_PATH . "/setup/" ?>"><?php $this->out("title") ?></a>
	</div>

	<div data-ng-cloak class="collapse navbar-collapse navbar-ex1-collapse">
		<ul class="nav navbar-nav">
			<li><a href="<?php echo WWW_PATH . '/' ?>">Site</a></li>
			<?php foreach($this->get("menu", "raw") as $key => $val): ?>
			<li class="<?php if ($key == $this->get("active", "raw")) echo "active " ?>">
				<a href="<?php echo REST_PATH . "/setup/" . $key ?>">
					<?php echo $val ?>
				</a>
			</li>
			<?php endforeach; ?>
		</ul>
	</div>
</nav>

<?php $this->insert("notifications.php"); ?>

<div class="container-fluid">
	<?php $this->insert("websetup-" . $this->get("tpt", "raw") . ".php") ?>
</div>

<?php $this->insert("navbar-bottom.php"); ?>
<?php $this->insert("footer.php"); ?>
