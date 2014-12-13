<?php $field = $this->get("field") ?>
<div class="form-group" data-ng-class="{'has-error': form.<?php echo $field->getName() ?>.$invalid}">
	<label class="col-sm-2 control-label" for="<?php echo $field->getName() ?>"><?php echo $field->getCaption() ?></label>
	<div class="col-sm-10">
		<p class="form-control-static">{{item.<?php echo $field->getName() ?>}}</p>
	</div>
</div>
