<?php $this->insert("header.php"); ?>
<div class="container">
	<div class="page-header">
		<h1><?php echo $this->config("title") ?></h1>
	</div>

	<div class="alert alert-danger">
		<h2><span class="glyphicon glyphicon-wrench"></span> Error <?php $this->out("code") ?> <?php $this->out("message") ?></h2>
	</div>
	<p><?php $this->out("body") ?></p>
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
	
	<?php if ($this->has("log") && count($this->get("log"))>0): ?>
	<div class="well">
		<h2>Log</h2>
		<pre><?php foreach($this->get("log") as $line) echo $line . "\n"; ?></pre>
	</div>
	<?php endif; ?>

	<p class="well well-sm"><?php $this->out("baseline") ?></p>
</div>
<?php $this->insert("footer.php"); ?>
