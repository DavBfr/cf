if (typeof app !== 'undefined') {

	app.service('UserService', function ($http) {
		angular.extend(this, new CrudService($http, 'user'));
	});

	app.controller('UserController', function ($scope, $timeout, $location, $route, UserService, NotificationFactory) {
		angular.extend(this, new CrudController($scope, $timeout, $location, $route, UserService, NotificationFactory));
		this.init();
		this.get_list();
	});

	app.controller('UserDetailController', function ($scope, $timeout, $location, $route, $routeParams, UserService, NotificationFactory) {
		angular.extend(this, new CrudController($scope, $timeout, $location, $route, UserService, NotificationFactory));
		this.init();
		this.get_fiche($routeParams.id);
	});

	function AddUserRoutes($routeProvider) {
		if (cf_options.rights.indexOf("admin") < 0) {
			return;
		}

		$routeProvider.when('/user', {
			controller: 'UserController',
			templateUrl: cf_options.rest_path + '/user/list',
			menu: 'user',
			title: 'Users'
		});

		$routeProvider.when('/user/:id', {
			controller: 'UserDetailController',
			templateUrl: cf_options.rest_path + '/user/detail',
			menu: 'user',
		});
	}

}
