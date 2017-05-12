<div class="page-header">
	<h1><?php $this->out("list_title") ?></h1>
</div>

<form class="form-inline" data-role="form">
	<?php if ($this->get("can_filter")): ?>
	<div class="form-group">
		<label class="sr-only" for="filter"><?php $this->tr("core.filter") ?></label>
		<input type="search" class="form-control" id="filter" placeholder="<?php $this->tr("core.filter") ?>" data-ng-model="filter" data-ng-change="Search()">
	</div>
	<?php endif ?>
	<?php if ($this->get("can_create")): ?>
	<button data-ng-click="go_detail('new')" type="button" class="btn btn-default">
		<span class="glyphicon glyphicon-plus"></span> <?php $this->tr("core.new") ?>
	</button>
	<?php endif ?>
	<?php if ($this->has("buttons_top")) $this->out("buttons_top"); ?>
</form>

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

<table data-ng-cloak data-ng-hide="count == 0 || loading" class="table table-hover">
	<thead>
		<tr>
			<?php foreach($this->get("model") as $field): ?>
			<?php if($field->inList()): ?>
			<th><?php echo $field->getCaption() ?></th>
			<?php endif; ?>
			<?php endforeach; ?>
			<th class="table-actions"><?php $this->tr("core.actions") ?></th>
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
					{{item.<?php echo $field->getName() ?>*1000|date:'mediumTime'}}
				<?php elseif($field->isDate()): ?>
					{{item.<?php echo $field->getName() ?>*1000|date:'mediumDate'}}
				<?php elseif($field->isDateTime()): ?>
					{{item.<?php echo $field->getName() ?>*1000|date:'medium'}}
				<?php elseif($field->isTimestamp()): ?>
					{{item.<?php echo $field->getName() ?>*1000|date:'medium'}}
				<?php else: ?>
					{{item.<?php echo $field->getName() ?>}}
				<?php endif; ?>
			</td>
			<?php endif; ?>
			<?php endforeach; ?>
			<td>
				<?php if ($this->get("can_delete")): ?>
				<button data-ng-click="del(item.<?php echo DavBfr\CF\Crud::ID ?>);$event.stopPropagation();" type="button" class="btn btn-danger btn-xs">
					<span class="glyphicon glyphicon-remove"></span> <?php $this->tr("core.del") ?>
				</button>
				<?php endif ?>
				<?php if ($this->get("can_view")): ?>
				<button data-ng-click="go_detail(item.<?php echo DavBfr\CF\Crud::ID ?>)" type="button" class="btn btn-primary btn-xs">
					<span class="glyphicon glyphicon-info-sign"></span> <?php $this->tr("core.details") ?>
				</button>
				<?php endif ?>
				<?php if ($this->has("buttons_list")) $this->out("buttons_list"); ?>
			</td>
		</tr>
	</tbody>
</table>

<ul data-ng-hide="pages <= 1" class="pagination pull-right">
	<li data-ng-class="page == 0?'disabled':''">
		<a data-ng-click="setPage(page - 1)" href="">&laquo;</a>
	</li>
	<li data-ng-class="$index == page?'active':''" data-ng-repeat="i in getPages() track by $index">
		<a data-ng-click="setPage($index)" href="">{{$index+1}}</a>
	</li>
	<li data-ng-class="page == pages -1?'disabled':''">
		<a data-ng-click="setPage(page + 1)" href="">&raquo;</a>
	</li>
</ul>
