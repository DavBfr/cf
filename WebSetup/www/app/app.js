$(function () {
	const div = $("<div style=\"color:red;position:fixed;bottom:0;left:0;z-index:10000;margin:15px 0 15px 15px;cursor:pointer;\">WebSetup</div>");
	$(div[0]).click(function () {
		document.location = cf_options.rest_path + "/setup";
	});
	$("body").append(div);
});
