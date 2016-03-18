<?php $this->insert("header.php"); ?>
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
<div class="container">
	<div class="page-header">
		<h1><?php echo $this->config("title") ?></h1>
	</div>

	<div class="alert alert-danger">
		<h2><span class="glyphicon glyphicon-wrench"></span> <?php $this->tr("core.error") ?> <?php $this->out("code") ?> <?php $this->out("message") ?></h2>
	</div>
	<p><?php $this->out("body") ?></p>
	
<?php
	$bt = $this->get("backtrace");
	if (count($bt)>0) {
		try {
			$file = file_get_contents($bt[0][0]);
			$file = explode("\n", $file);
			$start = max($bt[0][1] - 4, 0);
			$end = min($bt[0][1] + 3, count($file));
			echo $bt[0][0];
			echo "<div class=\"alert alert-info code\">";
			for($line = $start; $line < $end; $line++) {
				echo "<div".($line == $bt[0][1] - 1 ? " class=\"red\"" : "")."><span>".($line+1)."</span> ".\DavBfr\CF\System::highlightCode($file[$line])."</div>";
			}
			echo "</div>";
		} catch (Exception $e) {
		}
	}
?>
	
	<?php if ($this->has("backtrace") && count($this->get("backtrace"))>0): ?>
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
	
	<?php if ($this->has("log") && count($this->get("log"))>0): ?>
	<div class="well">
		<h2>Log</h2>
		<pre><?php foreach($this->get("log") as $line) echo $line . "\n"; ?></pre>
	</div>
	<?php endif; ?>

	<p class="well well-sm"><?php $this->out("baseline") ?></p>
</div>
<?php $this->insert("footer.php"); ?>
