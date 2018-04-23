<?php echo "<?php namespace DavBfr\CF;" ?>

class <?php echo $this->out("className") ?> extends Crud {

	protected function preCheck($mp) {
		// Session::ensureLoggedin();
		// Session::ensureXsrfToken();
		// Session::ensureRight("admin");
		return parent::preCheck($mp);
	}

	protected function getModel() {
		return new <?php echo $this->out("modelClass") ?>();
	}

}
