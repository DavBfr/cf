<?php $this->insert("header.php"); ?>
<?php $this->insert("notifications.php"); ?>
	<div data-ng-cloak class="container loginForm" data-ng-controller="LoginController">
		<?php $this->insert("login-head.php"); ?>
		<form role="form">
			<div class="form-group">
				<label for="username"><?php $this->tr("core.username") ?></label>
				<input id="username" class="form-control" autocomplete="off" type="text" autofocus="" data-ng-model="username" placeholder="<?php $this->tr("core.username") ?>"/>
			</div>
			<div class="form-group">
				<label for="password"><?php $this->tr("core.password") ?></label>
				<input id="password" class="form-control" autocomplete="off" type="password" data-ng-model="password" placeholder="<?php $this->tr("core.password") ?>"/>
			</div>
			<button class="btn btn-primary" data-ng-click="login(username, password)"><?php $this->tr("core.submit") ?></button>
		</form>
	</div>
<?php $this->insert("footer.php"); ?>
