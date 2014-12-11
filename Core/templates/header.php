<!DOCTYPE html>
<html lang="<?php echo Lang::getLangHtml() ?>" data-ng-app="app">
<head>
	<title><?php $this->out("title", "st", "CF " . CF_VERSION) ?></title>
	<?php if ($this->has("description")): ?>
	<meta name="description" content="<?php $this->out("description", "st") ?>">
	<?php endif ?>
	<?php if ($this->has("favicon")): ?>
	<link rel="shortcut icon" href="<?php echo $this->media($this->get("favicon")) ?>">
	<?php endif ?>
	<meta charset="UTF-8">
	<?php if ($this->has("stylesheets")) foreach($this->get("stylesheets") as $stylesheet): ?>
	<link href="<?php echo $stylesheet ?>" rel="stylesheet" media="screen">
	<?php endforeach; ?>
	<style type="text/css">
	[ng\:cloak], [ng-cloak], [data-ng-cloak], [x-ng-cloak], .ng-cloak, .x-ng-cloak {
		display: none !important;
	}
	</style>
</head>
<body>
