<nav class="navbar navbar-default navbar-fixed-top" role="navigation" data-ng-controller="RouteController">
	<div class="navbar-header">
		<button type="button" class="navbar-toggle" data-toggle="collapse" data-target=".navbar-ex1-collapse">
			<span class="sr-only">Toggle navigation</span>
			<span class="icon-bar"></span>
			<span class="icon-bar"></span>
			<span class="icon-bar"></span>
		</button>
		<a class="navbar-brand" href="#"><?php $this->out("title") ?></a>
	</div>

	<div class="collapse navbar-collapse navbar-ex1-collapse">
		<ul class="nav navbar-nav">
			<li data-ng-repeat="item in menu" class="{{item.active}}"><a href="#{{item.path}}">{{item.title}}</a></li>
		</ul>

		<button type="button" class="btn btn-default navbar-btn navbar-right" data-ng-click="logout()">Disconnect</button>
	</div>
</nav>
