<div class="page-header">
	<h1 data-ng-show="id"><?php $this->out("detail_title") ?></h1>
	<h1 data-ng-hide="id || loading"><?php $this->out("new_title") ?></h1>
</div>

<div style="margin-bottom:20px;">
	<div class="btn-group">
	</div>
</div>

<div data-ng-hide="!loading" class="panel panel-default">
	<div class="panel-body">
		<h4 class="text-center">
			<?php $this->tr("core.loading") ?><br>
			<br>
			<img src="<?php echo $this->media("ajax-loader.gif") ?>"/>
		</h4>
	</div>
</div>

<form data-ng-cloak data-ng-hide="loading" name="form" class="form-horizontal" data-role="form">
	<?php foreach($this->get("model") as $field): ?>
			<div class="form-group">
				<label class="col-sm-2 control-label"><?php echo $field->getCaption() ?></label>
				<div class="col-sm-10">
						<p class="form-control-static">{{item.<?php echo $field->getName() ?>}}</p>
				</div>
			</div>
	<?php endforeach; ?>
	
	<div class="form-group">
		<div class="col-sm-offset-2 col-sm-10">
			<button type="button" class="btn btn-default" data-ng-click="go_list()"><?php $this->tr("core.close") ?></button>
		</div>
	</div>
	
</form>
