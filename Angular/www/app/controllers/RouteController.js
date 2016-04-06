/**
* Copyright (C) 2013-2015 David PHAM-VAN
*
* This library is free software; you can redistribute it and/or
* modify it under the terms of the GNU Lesser General Public
* License as published by the Free Software Foundation; either
* version 2.1 of the License, or (at your option) any later version.
*
* This library is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
* Lesser General Public License for more details.
*
* You should have received a copy of the GNU Lesser General Public
* License along with this library; if not, write to the Free Software
* Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
**/

app.controller('RouteController', function($scope, $route, $location, $http, $timeout, LoginService) {
	var timeout = 20000;
	var timeoutHandler = null;
	init();

	function init() {
		$scope.route=$route;
		$scope.menu = [];
		$scope.lastcheck = new Date();
		timeoutHandler = $timeout(check, timeout);

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

		$scope.$on('$routeChangeStart', check);

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

	function check() {
		var self = this;
		if ((new Date()).getTime() - $scope.lastcheck.getTime() >= timeout) {
			LoginService.check(function(next) {
				if (next === false) {
					window.location.reload();
				} else {
					timeout = next * 1000 + 1000;
				}
			});
		}
		if (timeoutHandler) {
			$timeout.cancel(timeoutHandler);
			timeoutHandler = $timeout(check, timeout);
		}
		$scope.lastcheck = new Date();
	}

	$scope.logout = function() {
		LoginService.logout(function() {
			window.location.reload()
		});
	}

});
