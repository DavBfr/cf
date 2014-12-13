if (typeof app != 'undefined') {

app.service('<?php echo $this->out("umodel") ?>Service', function ($http) {
	 angular.extend(this, new CrudService($http, '<?php echo $this->out("model") ?>'));
});

app.controller('<?php echo $this->out("umodel") ?>Controller', function ($scope, $timeout, $location, $route, <?php echo $this->out("umodel") ?>Service, NotificationFactory) {
	angular.extend(this, new CrudController($scope, $timeout, $location, $route, <?php echo $this->out("umodel") ?>Service, NotificationFactory));
	this.init();
	this.get_list();
});

app.controller('<?php echo $this->out("umodel") ?>DetailController', function ($scope, $timeout, $location, $route, $routeParams, <?php echo $this->out("umodel") ?>Service, NotificationFactory) {
	angular.extend(this, new CrudController($scope, $timeout, $location, $route, <?php echo $this->out("umodel") ?>Service, NotificationFactory));
	this.init();
	this.get_fiche(parseInt($routeParams.id));
});

app.config(function ($routeProvider) {
	$routeProvider.when('/<?php echo $this->out("model") ?>', {
		controller: '<?php echo $this->out("umodel") ?>Controller',
		templateUrl: cf_options.rest_path + '/<?php echo $this->out("model") ?>/list',
		menu: '<?php echo $this->out("model") ?>',
		title: '<?php echo $this->out("umodel") ?>s'
	}).when('/<?php echo $this->out("model") ?>/:id', {
		controller: '<?php echo $this->out("umodel") ?>DetailController',
		templateUrl: cf_options.rest_path + '/<?php echo $this->out("model") ?>/detail',
		menu: '<?php echo $this->out("model") ?>',
	});
});

}
