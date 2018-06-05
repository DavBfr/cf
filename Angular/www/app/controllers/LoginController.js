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

app.service('LoginService', function ($http, NotificationFactory) {
	let userData = null;

	this.getRights = function () {
		return cf_options.rights;
	};

	this.setRights = function (rights) {
		cf_options.rights = rights;
	};

	this.hasRight = function (right) {
		return jQuery.inArray(right, cf_options.rights) >= 0;
	};

	this.getUser = function () {
		return cf_options.user;
	};

	this.logout = function (onSuccess) {
		$http.get(cf_options.rest_path + "/login/logout").then(function (response) {
			cf_options.rights = [];
			cf_options.user = null;
			onSuccess && onSuccess(response.data.message);
		}, function (response) {
			if (!restError(response))
				NotificationFactory.error(response.data);
		})
	};

	this.login = function (username, password, onSuccess) {
		$http.post(cf_options.rest_path + "/login", {
			username: username,
			password: password
		}).then(function (response) {
			cf_options.rights = response.data.rights;
			cf_options.user = response.data.user;
			onSuccess && onSuccess(response.data.rights, response.data.message);
		}, function (response) {
			if (!restError(response))
				NotificationFactory.error(response.data);
		})
	};

	this.check = function (callback) {
		$http.get(cf_options.rest_path + "/login/check").then(function (data, status) {
			cf_options.rights = data.rights;
			cf_options.user = data.user;
			callback && callback(data.next, data.rights, data.user);
		}, function (response) {
			cf_options.rights = [];
			cf_options.user = null;
			callback && callback(false, [], null);
		})
	};

	this.getUserInfos = function (onSuccess) {
		if (userData == null || userData.user !== cf_options.user) {
			$http.get(cf_options.rest_path + "/login/user").then(function (response) {
				cf_options.rights = response.data.rights;
				cf_options.user = response.data.user;
				userData = response.data;
				onSuccess && onSuccess(response.data);
			}, restError);
		} else {
			onSuccess && onSuccess(userData);
		}
	};

});


app.controller('LoginController', function ($scope, $location, $http, LoginService) {
	init();

	function init() {
	}

	$scope.login = function (username, password) {
		LoginService.login(username, password, function () {
			window.location.reload();
		});
	};

});
