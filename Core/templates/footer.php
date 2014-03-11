	<script type="text/javascript">
		window.cf_options = <?php $this->cf_options(); ?>;
	</script>
	<?php if ($this->has("scripts")) foreach($this->get("scripts") as $script): ?>
	<script src="<?php echo $script ?>"></script>
	<?php endforeach; ?>
</body>
</html>
