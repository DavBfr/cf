<div class="page-header">
	<h1 data-ng-show="id">Fiche #{{id}}</h1>
	<h1 data-ng-hide="id || loading">Nouvelle fiche</h1>
</div>

<div style="margin-bottom:20px;">
	<div class="btn-group">
	</div>
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

<form data-ng-hide="loading" name="form" class="form-horizontal" data-role="form">
	<?php foreach($this->get("model") as $field): ?>
		<?php if($field->isEditable()): ?>
		<div class="form-group" data-ng-class="{'has-error': form.<?php echo $field->getName() ?>.$invalid}">
			<label class="col-sm-2 control-label" for="<?php echo $field->getName() ?>"><?php echo $field->getCaption() ?></label>
			<div class="col-sm-10">
				<?php if($field->isAutoincrement()): ?>
					<p class="form-control-static">{{item.<?php echo $field->getName() ?>}}</p>
				<?php elseif($field->isBool()): ?>
					<div class="btn-group" data-toggle="buttons">
					<label class="btn btn-default" data-ng-class="{active:item.<?php echo $field->getName() ?>==1}" data-ng-click="item.<?php echo $field->getName() ?>=1"><input type="radio" data-ng-model="item.<?php echo $field->getName() ?>" data-ng-value="1"/> Oui</label> &nbsp;
					<label class="btn btn-default" data-ng-class="{active:item.<?php echo $field->getName() ?>==0}" data-ng-click="item.<?php echo $field->getName() ?>=0"><input type="radio" data-ng-model="item.<?php echo $field->getName() ?>" data-ng-value="0"/> Non</label>
					</div>
				<?php elseif($field->isInt()): ?>
					<input type="number" class="form-control" id="<?php echo $field->getName() ?>" name="<?php echo $field->getName() ?>" data-ng-required="<?php echo !$field->hasNull() ?>" data-ng-model="item.<?php echo $field->getName() ?>" placeholder="<?php echo $field->getCaption() ?>">
				<?php elseif($field->isDate()): ?>
					<input type="date" class="form-control" id="<?php echo $field->getName() ?>" name="<?php echo $field->getName() ?>" data-ng-required="<?php echo !$field->hasNull() ?>" data-ng-model="item.<?php echo $field->getName() ?>" placeholder="<?php echo $field->getCaption() ?>">
				<?php elseif($field->isPassword()): ?>
					<input type="password" class="form-control" id="<?php echo $field->getName() ?>" data-ng-required="<?php echo !$field->hasNull() ?>" name="<?php echo $field->getName() ?>" data-ng-model="item.<?php echo $field->getName() ?>" placeholder="<?php echo $field->getCaption() ?>">
				<?php else: ?>
					<input type="text" class="form-control" id="<?php echo $field->getName() ?>" data-ng-required="<?php echo !$field->hasNull() ?>" name="<?php echo $field->getName() ?>" data-ng-model="item.<?php echo $field->getName() ?>" placeholder="<?php echo $field->getCaption() ?>">
				<?php endif; ?>
				<p class="help-block error" data-ng-show="form.<?php echo $field->getName() ?>.$dirty && form.<?php echo $field->getName() ?>.$error.required">veuillez entrer une valeur</p>
				<p class="help-block error" data-ng-show="form.<?php echo $field->getName() ?>.$error.<?php echo $field->getName() ?>">veuillez entrer une valeur valide</p>
			</div>
		</div>
		<?php endif; ?>
	<?php endforeach; ?>

	<div class="form-group">
		<div class="col-sm-offset-2 col-sm-10">
			<button type="submit" data-loading-text="Sauvegarde ..." class="btn btn-primary" data-ng-disabled="form.$invalid" data-ng-click="save(id, item)">Valider</button>
			<button type="button" class="btn btn-danger" data-ng-show="id" data-ng-click="del(id)">Supprimer</button>
			<button type="button" class="btn btn-default" data-ng-click="go_list()">Annuler</button>
		</div>
	</div>

</form>
