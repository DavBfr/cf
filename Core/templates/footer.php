	<?php if ($this->has("scripts") && count($this->get("scripts")) == 1): ?>
	<script async src="<?php $scripts = $this->get("scripts"); echo $scripts[0] ?>"></script>
	<?php else: ?>
	<?php if ($this->has("scripts")) foreach($this->get("scripts") as $script): ?>
	<script src="<?php echo $script ?>"></script>
	<?php endforeach; ?>
	<?php endif; ?>
</body>
</html>
