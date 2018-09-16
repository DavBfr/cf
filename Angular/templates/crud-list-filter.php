<div class="form-group">
	<label class="sr-only" for="filter"><?php $this->tr("core.filter") ?></label>
	<div class="input-group">
		<div class="input-group-addon"><span class="glyphicon glyphicon-search"></span></div>
		<input type="search" class="form-control" id="filter" placeholder="<?php $this->tr("core.filter") ?>" data-ng-model="filter.q" data-ng-change="Search()">
	</div>
</div>
