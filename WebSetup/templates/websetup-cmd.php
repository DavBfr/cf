<h2>CF Commands</h2>
<table class="table table-bordered table-striped table-condensed">
	<thead>
		<tr>
			<th>Command</th>
			<th>Description</th>
		</tr>
	</thead>
	<tbody>
		<?php foreach($this->get("commands", "raw") as $key => $val): ?>
		<tr>
			<th><?php echo($key) ?></th>
			<td><?php echo($val[1]) ?></td>
		</tr>
		<?php endforeach; ?>
	</tbody>
</table>
