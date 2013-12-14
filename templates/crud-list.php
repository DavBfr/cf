<div class="page-header">
	<h1>Liste</h1>
</div>

<div data-ng-hide="!loading" class="panel panel-default">
	<div class="panel-body">
		<h4 class="text-center">
			Chargement<br>
			<br>
			<img src="<?php echo $this->media("ajax-loader.gif") ?>"/>
		</h4>
	</div>
</div>

<div data-ng-hide="count > 0 || loading" class="well">
	Aucun trouvé.
</div>

<table data-ng-hide="count == 0" class="table table-hover">
	<thead>
		<tr>
			<?php foreach($this->get("model") as $field): ?>
			<?php if($field->inList()): ?>
			<th><?php echo $field->getCaption() ?></th>
			<?php endif; ?>
			<?php endforeach; ?>
			<th class="table-actions">Actions</th>
		</tr>
	</thead>
	<tbody>
		<tr data-ng-repeat="item in list" data-ng-click="go_detail(item.<?php echo Crud::ID ?>)">
			<?php foreach($this->get("model") as $field): ?>
			<?php if($field->inList()): ?>
			<td>
				<?php if($field->isBool()): ?>
				<span data-ng-show="{{item.<?php echo $field->getName() ?>}}" class="glyphicon glyphicon-ok"></span>
				<?php else: ?>
				{{item.<?php echo $field->getName() ?>}}
				<?php endif; ?>
			</td>
			<?php endif; ?>
			<?php endforeach; ?>
			<td>
				<button data-ng-click="del(item.<?php echo Crud::ID ?>);$event.stopPropagation();" type="button" class="btn btn-danger btn-xs">
					<span class="glyphicon glyphicon-remove"></span> del
				</button>
				<button data-ng-click="go_detail(item.<?php echo Crud::ID ?>)" type="button" class="btn btn-primary btn-xs">
					<span class="glyphicon glyphicon-info-sign"></span> details
				</button>
			</td>
		</tr>
	</tbody>
</table>

<button data-ng-click="go_detail('new')" type="button" class="btn btn-default">
	<span class="glyphicon glyphicon-plus"></span> Nouveau
</button>

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
