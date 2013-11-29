app.controller('RouteController', function ($scope, $route, $location, $http) {
	init();

	function init() {
		$scope.route=$route;
		$scope.menu = [];

		for (path in $route.routes) {
			var route = $route.routes[path];
			if (route.title) {
				$scope.menu.push({
					path: path,
					menu: route.menu,
					title: route.title,
					active: ""
				});
			}
		}

		$scope.$on('$routeChangeSuccess', function(event, data) {
			for (menuid in $scope.menu) {
				if ($scope.menu[menuid].menu == data.menu) {
					$scope.menu[menuid].active = "active";
				} else {
					$scope.menu[menuid].active = "";
				}
			}
		});
	}
	
	$scope.logout = function() {
		$http.get(cf_options.rest_path + "/login/logout").success(function (data, status) {
			window.location.reload()
		}).error(function (data, status) {
			//console.log(data);
		})
	}

});
