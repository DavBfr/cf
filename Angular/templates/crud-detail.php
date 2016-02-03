<div class="page-header">
	<h1 data-ng-show="id"><?php $this->out("detail_title") ?></h1>
	<h1 data-ng-hide="id || loading"><?php $this->out("new_title") ?></h1>
</div>

<div style="margin-bottom:20px;">
	<div class="btn-group">
	</div>
</div>

<div data-ng-hide="!loading" class="panel panel-default">
	<div class="panel-body">
		<h4 class="text-center">
			<?php $this->tr("core.loading") ?><br>
			<br>
			<img src="<?php echo $this->media("ajax-loader.gif") ?>"/>
		</h4>
	</div>
</div>

<form data-ng-cloak data-ng-hide="loading" name="form" class="form-horizontal" data-role="form">
<?php
foreach($this->get("model") as $field) {
	if($field->isEditable()) {
		if (self::findTemplate("field-".$field->getTableName().".".$field->getName().".php"))
			$this->insertNew("field-".$field->getTableName().".".$field->getName().".php", array("field"=>$field));
		elseif($field->getEditor() !== NULL && self::findTemplate("field-".$field->getEditor().".php"))
			$this->insertNew("field-".$field->getEditor().".php", array("field"=>$field));
		elseif($field->isAutoincrement())
			$this->insertNew("field-auto.php", array("field"=>$field));
		elseif ($field->isSelect())
			$this->insertNew("field-select.php", array("field"=>$field));
		elseif($field->isBool())
			$this->insertNew("field-bool.php", array("field"=>$field));
		elseif($field->isInt())
			$this->insertNew("field-int.php", array("field"=>$field));
		elseif($field->isDate())
			$this->insertNew("field-date.php", array("field"=>$field));
		elseif($field->isEmail())
			$this->insertNew("field-email.php", array("field"=>$field));
		elseif($field->isPassword())
			$this->insertNew("field-passwd.php", array("field"=>$field));
		else
			$this->insertNew("field-text.php", array("field"=>$field));
	}
}
?>

	<div class="form-group">
		<div class="col-sm-offset-2 col-sm-10">
			<button type="submit" data-loading-text="<?php $this->tr("core.saving") ?> ..." class="btn btn-primary" data-ng-disabled="form.$invalid" data-ng-click="save(id, item)"><?php $this->tr("core.submit") ?></button>
			<button type="button" class="btn btn-danger" data-ng-show="id" data-ng-click="del(id)"><?php $this->tr("core.delete") ?></button>
			<button type="button" class="btn btn-default" data-ng-click="go_list()"><?php $this->tr("core.cancel") ?></button>
			<?php if ($this->has("buttons_detail")) $this->out("buttons_detail"); ?>
		</div>
	</div>

</form>
