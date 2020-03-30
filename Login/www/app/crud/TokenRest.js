if (typeof app !== 'undefined') {

	app.service('TokenService', function ($http) {
		angular.extend(this, new CrudService($http, 'Token'));
	});

	app.controller('TokenController', function ($scope, $timeout, $location, $route, TokenService, NotificationFactory) {
		angular.extend(this, new CrudController($scope, $timeout, $location, $route, TokenService, NotificationFactory));
		this.init();
		this.get_list();
	});

	app.controller('TokenDetailController', function ($scope, $timeout, $location, $route, $routeParams, TokenService, NotificationFactory) {
		angular.extend(this, new CrudController($scope, $timeout, $location, $route, TokenService, NotificationFactory));
		this.init();
		this.get_fiche($routeParams.id);
	});

	function AddTokenRoutes($routeProvider) {
		if (cf_options.rights.indexOf("admin") < 0) {
			return;
		}

		$routeProvider.when('/Token', {
			controller: 'TokenController',
			templateUrl: cf_options.rest_path + '/Token/list',
			menu: 'Token',
			title: 'Tokens'
		});

		$routeProvider.when('/Token/:id', {
			controller: 'TokenDetailController',
			templateUrl: cf_options.rest_path + '/Token/detail',
			menu: 'Token',
		});
	}

}
