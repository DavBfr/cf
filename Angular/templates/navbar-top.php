<nav class="navbar navbar-default navbar-fixed-top" role="navigation" data-ng-controller="RouteController">
<div class="container-fluid">
	<div class="navbar-header">
		<button type="button" class="navbar-toggle" data-toggle="collapse" data-target=".navbar-ex1-collapse">
			<span class="sr-only">[[ core.toggle_navigation | tr ]]</span>
			<span class="icon-bar"></span>
			<span class="icon-bar"></span>
			<span class="icon-bar"></span>
		</button>
		<a class="navbar-brand" href="#/">[[ title ]]</a>
	</div>

	<div class="collapse navbar-collapse navbar-ex1-collapse">
		<ul class="nav navbar-nav">
			<li data-ng-cloak data-ng-repeat="item in menu" class="{{ item.active }}"><a href="#{{ item.path }}">{{ item.title }}</a></li>
		</ul>

		<ul class="nav navbar-nav navbar-right">
			<?php $this->insert("navbar-top-right.php"); ?>
		</ul>
	</div>
</div>
</nav>
