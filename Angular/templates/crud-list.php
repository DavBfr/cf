<div class="page-header">
	<h1><?php $this->out("list_title") ?></h1>
</div>

<?php $this->insert('pagination.php'); ?>

<form class="form-inline" data-role="form">
	<?php
	if ($this->get("can_filter")) {
		if (self::findTemplate("crud-list-filter-" . $this->get("table") . ".php")) {
			$this->insertNew("crud-list-filter-" . $this->get("table") . ".php");
		} else {
			$this->insertNew("crud-list-filter.php");
		}
	}
	?>
	<?php if ($this->get("can_create")): ?>
	<button data-ng-click="go_detail('new')" type="button" class="btn btn-default">
		<span class="glyphicon glyphicon-plus"></span> <?php $this->tr("core.new") ?>
	</button>
	<?php endif ?>
	<?php if ($this->has("buttons_top")) $this->out("buttons_top"); ?>
</form>


<div class="clearfix"></div>

<div data-ng-hide="!loading" class="panel panel-default">
	<div class="panel-body">
		<h4 class="text-center">
			<?php $this->tr("core.loading") ?><br>
			<br>
			<img src="<?php echo $this->media("ajax-loader.gif") ?>"/>
		</h4>
	</div>
</div>

<div data-ng-cloak data-ng-hide="count > 0 || loading" class="well">
	<?php $this->tr("core.none_found") ?>
</div>

<div class="table-responsive">
<table data-ng-cloak data-ng-hide="count == 0 || loading" class="table table-hover">
	<thead>
		<tr>
			<?php foreach($this->get("model") as $field): ?>
			<?php if($field->inList()): ?>
			<th><?php echo $field->getCaption() ?></th>
			<?php endif; ?>
			<?php endforeach; ?>
			<th class="table-actions col-sm-1"></th>
		</tr>
	</thead>
	<tbody>
		<tr data-ng-repeat="item in list">
			<?php foreach($this->get("model") as $field): ?>
			<?php if($field->inList()): ?>
			<td <?php if ($this->get("can_view")): ?> data-ng-click="go_detail(item.<?php echo DavBfr\CF\Crud::ID ?>)" <?php endif; ?>>
				<?php	if (self::findTemplate("field-list-" . $field->getTableName() . "." . $field->getName() . ".php")):
					$this->insertNew("field-list-" . $field->getTableName() . "." . $field->getName() . ".php", array("field" => $field)); ?>
				<?php elseif($field->isBool()): ?>
					<span data-ng-show="{{item.<?php echo $field->getName() ?>}}" class="glyphicon glyphicon-ok"></span>
				<?php elseif($field->isTime()): ?>
					{{item.<?php echo $field->getName() ?>*1000|date:'<?php echo $this->config('angular.time') ?>':'UTC'}}
				<?php elseif($field->isDate()): ?>
					{{item.<?php echo $field->getName() ?>*1000|date:'<?php echo $this->config("angular.date") ?>':'UTC'}}
				<?php elseif($field->isDateTime() || $field->isTimestamp()): ?>
					{{item.<?php echo $field->getName() ?>*1000|date:'<?php echo $this->config("angular.datetime") ?>':'UTC'}}
				<?php else: ?>
					{{item.<?php echo $field->getName() ?>}}
				<?php endif; ?>
			</td>
			<?php endif; ?>
			<?php endforeach; ?>
			<td class="text-nowrap">
				<?php if ($this->get("can_delete")): ?>
				<button data-ng-click="del(item.<?php echo DavBfr\CF\Crud::ID ?>);$event.stopPropagation();" type="button" class="btn btn-danger btn-sm" title="<?php $this->tr("core.delete") ?>">
					<span class="glyphicon glyphicon-trash"></span>
				</button>
				<?php endif ?>
				<?php if ($this->get("can_view")): ?>
				<button data-ng-click="go_detail(item.<?php echo DavBfr\CF\Crud::ID ?>)" type="button" class="btn btn-primary btn-sm" title="<?php $this->tr("core.details") ?>">
					<span class="glyphicon glyphicon-pencil"></span>
				</button>
				<?php endif ?>
				<?php if ($this->has("buttons_list")) $this->out("buttons_list"); ?>
			</td>
		</tr>
	</tbody>
</table>
</div>


<?php $this->insert('pagination.php'); ?>

<div data-ng-hide="count == 0" >
	<?php $this->tr("core.total_records") ?> {{count}}
</div>
