<!DOCTYPE html>
<html lang="<?php echo Lang::getLangHtml() ?>" data-ng-app="app">
<head>
	<title><?php $this->out("title") ?></title>
	<meta name="description" content="<?php $this->out("title") ?>">
	<meta charset="UTF-8">
	<?php if ($this->has("stylesheets")) foreach($this->get("stylesheets") as $stylesheet): ?>
	<link href="<?php echo $stylesheet ?>" rel="stylesheet" media="screen">
	<?php endforeach; ?>
</head>
<body>
