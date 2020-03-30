<style type="text/css">
tr.updated {
	color: #0e9042 !important;
}
</style>
<h2>CF configuration</h2>
<table class="table table-bordered table-striped table-condensed">
	<thead>
		<tr>
			<th>Option</th>
			<th>Value</th>
			<th>Description</th>
		</tr>
	</thead>
	<tbody>
		<?php foreach($this->get("options", "raw") as $key => $val): ?>
		<tr<?php echo($val[2] ? " class=\"updated\"" : "") ?>>
			<th><?php echo($key) ?></th>
			<td><?php echo($val[0]) ?></td>
			<td><?php echo($val[1]) ?></td>
		</tr>
		<?php endforeach; ?>
	</tbody>
</table>
