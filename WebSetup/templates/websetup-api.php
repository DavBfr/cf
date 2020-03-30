<script type="text/javascript" src="<?php echo ($this->media("swagger/swagger-ui-bundle.js")); ?>"></script>
<script type="text/javascript" src="<?php echo ($this->media("swagger/swagger-ui-standalone-preset.js")); ?>"></script>

<div id="swagger-ui"></div>

<link href="<?php echo ($this->media("swagger/swagger-ui.css")); ?>" rel="stylesheet" media="screen">

<script>
window.onload = function() {

      // Build a system
      const ui = SwaggerUIBundle({
        url: "openapi.json",
        dom_id: '#swagger-ui',
        deepLinking: true,
        presets: [
          SwaggerUIBundle.presets.apis,
          SwaggerUIStandalonePreset
        ],
        plugins: [
          SwaggerUIBundle.plugins.DownloadUrl
        ],
        layout: "StandaloneLayout"
      })

      window.ui = ui
    }
</script>

<pre>
<?php echo $this->get("data"); ?>
</pre>
