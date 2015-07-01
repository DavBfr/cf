<nav class="navbar navbar-default navbar-fixed-top" role="navigation" data-ng-controller="RouteController">
<div class="container-fluid">
	<div class="navbar-header">
		<button type="button" class="navbar-toggle" data-toggle="collapse" data-target=".navbar-ex1-collapse">
			<span class="sr-only"><?php $this->tr("core.toggle_navigation") ?></span>
			<span class="icon-bar"></span>
			<span class="icon-bar"></span>
			<span class="icon-bar"></span>
		</button>
		<a class="navbar-brand" href="#"><?php $this->out("title") ?></a>
	</div>

	<div data-ng-cloak class="collapse navbar-collapse navbar-ex1-collapse">
		<ul class="nav navbar-nav">
			<li data-ng-repeat="item in menu" class="{{item.active}}"><a href="#{{item.path}}">{{item.title}}</a></li>
		</ul>

		<ul class="nav navbar-nav navbar-right">
			<li>
				<a href="javascript:void(0)" data-ng-click="logout()"><?php $this->tr("core.logout") ?></a>
			</li>
		</ul>
	</div>
</div>
</nav>
