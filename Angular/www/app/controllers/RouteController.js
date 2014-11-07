/**
* Copyright (C) 2013-2014 David PHAM-VAN
*
* This program is free software; you can redistribute it and/or
* modify it under the terms of the GNU General Public License
* as published by the Free Software Foundation; version 2
* of the License.
*
* This program is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
* GNU General Public License for more details.
*
* You should have received a copy of the GNU General Public License
* along with this program; if not, write to the Free Software
* Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
**/

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
