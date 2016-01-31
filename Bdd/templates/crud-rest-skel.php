<?php echo "<?php namespace DavBfr\CF;" ?>

//Session::ensureLoggedin();

class <?php echo $this->out("className") ?> extends Crud {

	protected function getModel() {
		return new <?php echo $this->out("modelClass") ?>();
	}

}
