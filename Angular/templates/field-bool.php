<?php $field = $this->get("field") ?>
<div class="form-group" data-ng-class="{'has-error': form.<?php echo $field->getName() ?>.$invalid}">
	<label class="col-sm-2 control-label" for="<?php echo $field->getName() ?>"><?php echo $field->getCaption() ?></label>
	<div class="col-sm-10">
		<div class="btn-group" data-toggle="buttons">
			<label class="btn btn-default" data-ng-class="{active:item.<?php echo $field->getName() ?>==1}" data-ng-click="item.<?php echo $field->getName() ?>=1"><input type="radio" data-ng-model="item.<?php echo $field->getName() ?>" data-ng-value="1"/> <?php $this->tr("core.yes") ?></label> &nbsp;
			<label class="btn btn-default" data-ng-class="{active:item.<?php echo $field->getName() ?>==0}" data-ng-click="item.<?php echo $field->getName() ?>=0"><input type="radio" data-ng-model="item.<?php echo $field->getName() ?>" data-ng-value="0"/> <?php $this->tr("core.no") ?></label>
		</div>
		<p class="help-block error" data-ng-show="form.<?php echo $field->getName() ?>.$dirty && form.<?php echo $field->getName() ?>.$error.required"><?php $this->tr("core.enter_value") ?></p>
		<p class="help-block error" data-ng-show="form.<?php echo $field->getName() ?>.$error.<?php echo $field->getName() ?>"><?php $this->tr("core.enter_valid_value") ?></p>
	</div>
</div>
