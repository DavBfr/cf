<!DOCTYPE html>
<html>
<head>
	<title><?php echo $this->config("title") ?></title>
	<base href="<?php echo dirname($_SERVER["SCRIPT_NAME"]) ?>/">
	<link href="<?php echo CF_VENDOR_PATH ?>/bootstrap/bootstrap.css" rel="stylesheet" media="screen">
	<link href="<?php echo CF_VENDOR_PATH ?>/bootstrap/bootstrap-theme.css" rel="stylesheet" media="screen">
	<meta charset="UTF-8">
</head>
<body>
	<div class="container">
		<div class="page-header">
			<h1><?php echo $this->config("title") ?></h1>
		</div>

		<div class="alert alert-danger">
			<h2><span class="glyphicon glyphicon-wrench"></span> Error <?php $this->out("code") ?> <?php $this->out("message") ?></h2>
			<p><?php $this->out("body") ?></p>
		</div>
		<?php if ($this->has("backtrace") && count($this->get("backtrace"))>0): ?>
		<div class="well">
			<h2>Backtrace</h2>
			<table class="table table-condensed ">
				<thead>
					<tr>
						<th>#</th>
						<th>File</th>
						<th>Function</th>
					</tr>
				</thead>
				<tbody>
					<?php foreach($this->get("backtrace") as $n=>$bt): ?>
					<tr>
						<td><?php echo $n ?></td>
						<td><?php echo "${bt[0]} (${bt[1]})" ?></td>
						<td><?php echo (isset($bt[2]) ? $bt[2] . '->' : '').(isset($bt[3]) ? $bt[3] . '(' . implode(', ', $bt[4]) . ')' : '') ?></td>
					</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
		<?php endif; ?>

		<p class="well well-sm"><?php $this->out("baseline") ?></p>
	</div>

	<script type="text/javascript">
		window.cf_options = <?php $this->cf_options(); ?>;
	</script>
	<?php if ($this->has("stylesheets")) foreach($this->get("scripts") as $script): ?>
	<script src="<?php echo $script ?>"></script>
	<?php endforeach; ?>
</body>
</html>
