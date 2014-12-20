<?php $field = $this->get("field") ?>
<div
	class="form-group"
	data-ng-class="{'has-error': form.<?php echo $field->getName() ?>.$invalid
		|| form.<?php echo $field->getName() ?>__VERIFY__.$invalid}">
	<label
		class="col-sm-2 control-label"
		for="<?php echo $field->getName() ?>">
		<?php echo $field->getCaption() ?>
	</label>
	<div class="col-sm-10">
		<input
			type="password"
			class="form-control"
			id="<?php echo $field->getName() ?>"
			data-ng-required="<?php echo !$field->hasNull() ?>"
			name="<?php echo $field->getName() ?>"
			data-ng-model="item.<?php echo $field->getName() ?>"
			placeholder="<?php echo $field->getCaption() ?>">
		<p
			class="help-block error"
			data-ng-show="form.<?php echo $field->getName() ?>.$dirty
				&& form.<?php echo $field->getName() ?>.$error.required">
			<?php $this->tr("core.enter_value") ?>
		</p>
		<p
			class="help-block error"
			data-ng-show="form.<?php echo $field->getName() ?>.$error.<?php echo $field->getName() ?>">
			<?php $this->tr("core.enter_valid_value") ?>
		</p>
		<input
			type="password"
			class="form-control"
			id="<?php echo $field->getName() ?>__VERIFY__"
			data-ng-required="<?php echo !$field->hasNull() ?>"
			name="<?php echo $field->getName() ?>__VERIFY__"
			data-ng-model="item.<?php echo $field->getName() ?>__VERIFY__"
			placeholder="<?php echo $field->getCaption() ?>"
			data-password-verify="item.<?php echo $field->getName() ?>">
		<p
			class="help-block error"
			data-ng-show="form.<?php echo $field->getName() ?>__VERIFY__.$dirty
				&& form.<?php echo $field->getName() ?>__VERIFY__.$error.required">
			<?php $this->tr("core.enter_value") ?>
		</p>
		<p
			class="help-block error"
			data-ng-show="form.<?php echo $field->getName() ?>__VERIFY__.$error.<?php echo $field->getName() ?>__VERIFY__">
			<?php $this->tr("core.enter_valid_value") ?>
		</p>
		<p
			class="help-block error"
			data-ng-show="
				 form.<?php echo $field->getName() ?>__VERIFY__.$error.passwordVerify">
			<?php $this->tr("core.fields_not_equal") ?>
		</p>
	</div>
</div>
