<?php
/** @var \DavBfr\CF\ModelField $field */
$field = $this->get("field"); ?>
<div class="form-group" data-ng-class="{'has-error': form.<?php echo $field->getName() ?>.$invalid}">
	<label class="col-sm-2 control-label" for="<?php echo $field->getName() ?>"><?php echo $field->getCaption() ?></label>
	<div class="col-sm-10">
		<input type="time"
					 ts-to-time
					 class="form-control"
					 id="<?php echo $field->getName() ?>"
					 name="<?php echo $field->getName() ?>"
					 data-ng-required="<?php echo !$field->hasNull() ?>"
					 data-ng-model="item.<?php echo $field->getName() ?>"
					 placeholder="<?php echo $field->getCaption() ?>"
					 data-autoclose="true"
					 data-placement="top-left"
					 data-bs-timepicker
					 data-time-format="<?php echo $this->config("angular.time") ?>"
					 data-length="1"
					 data-minute-step="1"
					 data-arrow-behavior="picker">
		<p class="help-block error"
			 data-ng-show="form.<?php echo $field->getName() ?>.$dirty && form.<?php echo $field->getName() ?>.$error.required"><?php $this->tr("core.enter_value") ?></p>
		<p class="help-block error"
			 data-ng-show="form.<?php echo $field->getName() ?>.$error.<?php echo $field->getName() ?>"><?php $this->tr("core.enter_valid_value") ?></p>
	</div>
</div>
