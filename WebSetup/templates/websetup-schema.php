<script type="text/javascript" src="<?php echo ($this->media("viz-js/viz.js")); ?>"></script>

<style type="text/css">
</style>
<script type="text/javascript">
	window.addEventListener("load", function () {
		var svg = Viz(<?php echo json_encode($this->get('dot')); ?>);
		document.getElementById("graph").innerHTML = svg;
	});
</script>

<div id="graph"></div>
