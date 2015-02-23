<?php $field = $this->get("field") ?>
<div class="form-group" data-ng-class="{'has-error': form.<?php echo $field->getName() ?>.$invalid}">
	<label class="col-sm-2 control-label" for="<?php echo $field->getName() ?>"><?php echo $field->getCaption() ?></label>
	<div class="col-sm-10">
			<button
				class="btn btn-default"
				id="<?php echo $field->getName() ?>" name="<?php echo $field->getName() ?>"
				data-ng-required="<?php echo !$field->hasNull() ?>"
				data-ng-model="item.<?php echo $field->getName() ?>"
				data-ng-multiple="false"
				data-ng-file-change="upload(files)"
				data-ng-model="files"
				data-ng-file-select=""
				multiple="multiple"
				accept="image/*,*.pdf"
				style="overflow: hidden;">
				Attach Any File
			</button>
				
			<span class="progress" ng-show="f.progress >= 0">
				<div class="ng-binding" style="width:100%">100%</div>
			</span>
		<p class="help-block error" data-ng-show="form.<?php echo $field->getName() ?>.$dirty && form.<?php echo $field->getName() ?>.$error.required"><?php $this->tr("core.enter_value") ?></p>
		<p class="help-block error" data-ng-show="form.<?php echo $field->getName() ?>.$error.<?php echo $field->getName() ?>"><?php $this->tr("core.enter_valid_value") ?></p>
	</div>
</div>
