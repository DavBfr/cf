<script type="text/javascript" src="<?php echo ($this->media("jsoneditor/jsoneditor.js")); ?>"></script>

<style type="text/css">
	.key {
		color: #bd7516;
	}
	.string {
		color: #16600e;
	}
	.string {
		color: #9b15bf;
	}
	.boolean  {
		color: #14c0ba;
	}
	.null  {
		color: #1319c1;
	}
</style>

<h2>Json configuration file</h2>
<div id="json_data">
	
</div>

<script type="text/javascript">
function syntaxHighlight(json) {
    if (typeof json != 'string') {
         json = JSON.stringify(json, undefined, 2);
    }
    json = json.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
    return json.replace(/("(\\u[a-zA-Z0-9]{4}|\\[^u]|[^\\"])*"(\s*:)?|\b(true|false|null)\b|-?\d+(?:\.\d*)?(?:[eE][+\-]?\d+)?)/g, function (match) {
        var cls = 'number';
        if (/^"/.test(match)) {
            if (/:$/.test(match)) {
                cls = 'key';
            } else {
                cls = 'string';
            }
        } else if (/true|false/.test(match)) {
            cls = 'boolean';
        } else if (/null/.test(match)) {
            cls = 'null';
        }
        return '<span class="' + cls + '">' + match + '</span>';
    });
}


window.addEventListener("load", function () {
	var s = <?php echo ($this->get("config")) ?>;
	//$("#json_data").html(s);
	
	// http://jeremydorn.com/json-editor/
	var element = document.getElementById('json_data');
	var editor = new JSONEditor(element, {
  	theme: 'bootstrap3',
		iconlib: "bootstrap3",
		startval: s,
		schema: <?php $this->insert("websetup-json-schema.php") ?>
	});
});
</script>
