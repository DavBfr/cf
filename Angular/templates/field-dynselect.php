<?php $field = $this->get("field") ?>
<div class="form-group" data-ng-class="{'has-error': form.<?php echo $field->getName() ?>.$invalid}">
	<label class="col-sm-2 control-label" for="<?php echo $field->getName() ?>"><?php echo $field->getCaption() ?></label>
	<div class="col-sm-10">
		<ui-select
				theme="bootstrap"
				id="<?php echo $field->getName() ?>"
				name="<?php echo $field->getName() ?>"
				data-ng-required="<?php echo !$field->hasNull() ?>"
				data-ng-model="item.<?php echo $field->getName() ?>">
			<ui-select-match placeholder="<?php echo $field->getCaption() ?>">{{$select.selected.value}}</ui-select-match>
			<ui-select-choices repeat="item in foreign.<?php echo $field->getName() ?> | filter: $select.search">
				<div ng-bind-html="item.value | highlight: $select.search"></div>
			</ui-select-choices>
		</ui-select>
		<p class="help-block error" data-ng-show="form.<?php echo $field->getName() ?>.$dirty && form.<?php echo $field->getName() ?>.$error.required"><?php $this->tr("core.enter_value") ?></p>
		<p class="help-block error" data-ng-show="form.<?php echo $field->getName() ?>.$error.<?php echo $field->getName() ?>"><?php $this->tr("core.enter_valid_value") ?></p>
	</div>
</div>
