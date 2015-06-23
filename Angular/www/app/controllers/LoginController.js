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

app.controller('LoginController', function ($scope, $location, $http, NotificationFactory) {
	init();
	
	function init() {
	}
	
	$scope.login = function(username, password) {
		$http.post(cf_options.rest_path + "/login", {username:username, password:password}).success(function (data, status) {
			window.location.reload();
		}).error(function (data, status) {
			NotificationFactory.error(data);
		})
	};

});
