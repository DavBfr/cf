var app = angular.module('app', []);

if (typeof modules !== 'undefined') {
	for (let id in modules) {
		let module = modules[id];

		(function (module) {
			app.service('Admin' + module + 'Service', function ($http) {
				angular.extend(this, new CrudService($http, 'admin/' + module));
			});
		})(module);

		app.controller('Admin' + module + 'Controller', ['$scope', '$timeout', '$location', '$route', 'Admin' + module + 'Service', 'NotificationFactory',
			function ($scope, $timeout, $location, $route, service, NotificationFactory) {
				angular.extend(this, new CrudController($scope, $timeout, $location, $route, service, NotificationFactory));
				this.init();
				this.get_list();
			}
		]);

		app.controller('Admin' + module + 'DetailController', ['$scope', '$timeout', '$location', '$route', '$routeParams', 'Admin' + module + 'Service', 'NotificationFactory',
			function ($scope, $timeout, $location, $route, $routeParams, service, NotificationFactory) {
				angular.extend(this, new CrudController($scope, $timeout, $location, $route, service, NotificationFactory));
				this.init();
				this.get_fiche($routeParams.id);
			}
		]);

	}

	app.config(function ($routeProvider) {
		$routeProvider.when('/', {});

		$routeProvider.otherwise({
			redirectTo: '/',
		});

		for (let id in modules) {
			let module = modules[id];

			$routeProvider.when('/' + module, {
				controller: 'Admin' + module + 'Controller',
				templateUrl: cf_options.rest_path + '/admin/' + module + '/list',
				menu: module,
				title: module.charAt(0).toUpperCase() + module.slice(1)
			});

			$routeProvider.when('/' + module + '/:id', {
				controller: 'Admin' + module + 'DetailController',
				templateUrl: cf_options.rest_path + '/admin/' + module + '/detail',
				menu: module,
			});
		}
	});
}
