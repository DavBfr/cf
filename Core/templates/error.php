<!DOCTYPE html>
<html lang="<?php echo DavBfr\CF\Lang::getLangHtml() ?>">
<head>
	<title><?php $this->out("title", "st", "CF " . CF_VERSION) ?></title>
	<?php if ($this->has("description")): ?>
	<meta name="description" content="<?php $this->out("description", "st") ?>">
	<?php endif ?>
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<?php if ($this->has("favicon")): ?>
	<link rel="shortcut icon" href="<?php echo $this->media($this->get("favicon")) ?>">
	<?php endif ?>
	<meta charset="UTF-8">
	<link href="//maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-BVYiiSIFeK1dGmJRAkycuHAHRg32OmUcww7on3RYdg4Va+PmSTsz/K68vbdEjh4u" crossorigin="anonymous">
	<script type="text/javascript">
		window.cf_options = <?php $this->cf_options(); ?>;
	</script>
	<style type="text/css">
	.code {
		white-space: pre;
	}
	.code span {
		color: #000;
	}
	.code .red {
		font-weight: normal;
		background-color: #aed4e7;
		color: #31708f;
	}
	</style>
</head>
<body>
<?php // $this->insert("header.php"); ?>
<div class="container">
	<div class="page-header">
		<h1><?php echo $this->config("title") ?></h1>
	</div>

	<div class="alert alert-danger">
		<h2><span class="glyphicon glyphicon-wrench"></span> <?php $this->tr("core.error") ?> <?php $this->out("code") ?> <?php $this->out("message") ?></h2>
	</div>
	<?php if (trim($this->get("body")) != ""): ?>
	<p><?php $this->out("body") ?></p>
	<?php endif; ?>
	<?php if (trim($this->get("debug")) != ""): ?>
	<div class="well">
	<h2><?php $this->tr("core.debug_output") ?></h2>
	<pre><?php $this->out("debug") ?></pre>
	</div>
	<?php endif; ?>
<?php
	$bt = $this->get("backtrace");
	if (count($bt) > 0) {
		try {
			$file = file_get_contents($bt[0][0]);
			$file = explode("\n", $file);
			$start = max($bt[0][1] - 4, 0);
			$end = min($bt[0][1] + 3, count($file));
			echo $bt[0][0];
			echo "<div class=\"alert alert-info code\">";
			for($line = $start; $line < $end; $line++) {
				echo "<div" . ($line == $bt[0][1] - 1 ? " class=\"red\"" : "") . "><span>" . ($line + 1) . "</span> " . \DavBfr\CF\System::highlightCode($file[$line]) . "</div>";
			}
			echo "</div>";
		} catch (Exception $e) {
		}
	}
?>
	
	<?php if ($this->has("backtrace") && count($this->get("backtrace")) > 0): ?>
	<div class="well">
		<h2><?php $this->tr("core.backtrace") ?></h2>
		<table class="table table-condensed ">
			<thead>
				<tr>
					<th>#</th>
					<th><?php $this->tr("core.file") ?></th>
					<th><?php $this->tr("core.function") ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach($this->get("backtrace") as $n => $bt): ?>
				<tr>
					<td><?php echo $n ?></td>
					<td><?php echo "${bt[0]} (${bt[1]})" ?></td>
					<td><?php echo (isset($bt[2]) ? $bt[2] . '->' : '') . (isset($bt[3]) ? $bt[3] . '(' . implode(', ', $bt[4]) . ')' : '') ?></td>
				</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	</div>
	<?php endif; ?>
	
	<?php if ($this->has("log") && count($this->get("log")) > 0): ?>
	<div class="well">
		<h2>Log</h2>
		<pre><?php foreach($this->get("log") as $line) echo $line . "\n"; ?></pre>
	</div>
	<?php endif; ?>

	<p class="well well-sm"><?php $this->out("baseline") ?></p>
</div>
<?php // $this->insert("footer.php"); ?>
</body>
</html>
