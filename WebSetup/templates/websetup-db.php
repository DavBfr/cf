<style type="text/css">
</style>
<script type="text/javascript">
	var modules = <?php echo json_encode($this->get("models", "raw")) ?>;
</script>

<div class="row">
	<div class="col-md-2">
		<div class="page-header">
			<h1>Db</h1>
		</div>
		<table class="table table-bordered table-striped table-condensed">
			<thead>
				<tr>
					<th>Table</th>
				</tr>
			</thead>
			<tbody>
				<?php foreach($this->get("models", "raw") as $val): ?>
				<tr>
					<td><a href="#/<?php echo $val ?>"><?php echo $val ?></a></td>
				</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	</div>
  <div class="col-md-10" data-ng-app="app" data-ng-view="">

	</div>
</div>
