function restError(response) {
	if (cf_options.debug) {
		if (response.status === 500) {
			var div = document.createElement('div');
			var iframe = document.createElement('iframe');
			var btn = document.createElement('div');
			btn.innerHTML = "X";
			btn.style="position:absolute;border-radius:20px;font-size:12px;height:40px;line-height:1.42;padding:11px 0;text-align: center;width:40px;background-color:#768f62;color:white;z-index:100001;cursor:pointer;right:30px;";
			btn.addEventListener("click", function () {
				document.body.removeChild(div);
			});
			div.style = "background-color:white;position:absolute;left:10px;top:10px;bottom:10px;right:10px;border:1px solid red;z-index:100000;padding:10px;";
			iframe.style = "position:absolute;left:0;top:0;width:100%;height:100%;border:none;";
			div.appendChild(btn);
			div.appendChild(iframe);
			document.body.appendChild(div);
			iframe.contentWindow.document.open();
			iframe.contentWindow.document.write(response.data);
			iframe.contentWindow.document.close();
			return true;
		}
	}
	return false;
}
