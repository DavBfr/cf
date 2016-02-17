	<script type="text/javascript">
		window.cf_options = <?php $this->cf_options(); ?>;
	</script>
	<?php if ($this->has("scripts")) foreach($this->get("scripts") as $script): ?>
	<script src="<?php echo $script ?>"></script>
	<?php endforeach; ?>
	<?php $lang=$this->media("i18n/angular-locale_".strtolower(DavBfr\CF\Lang::getLangHtml()).".js");
	if ($lang): ?>
	<script src="<?php echo $lang; ?>"></script>
	<?php endif; ?>
</body>
</html>
