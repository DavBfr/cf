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

app.service('LoginService', function($http, NotificationFactory) {
	var userdata = null;

	this.getRights = function() {
		return cf_options.rights;
	}

	this.setRights = function(rights) {
		cf_options.rights = rights;
	}

	this.hasRight = function(right) {
		return jQuery.inArray(right, cf_options.rights) >= 0;
	};

	this.getUser = function() {
		return cf_options.user;
	}

	this.logout = function(onsuccess) {
		$http.get(cf_options.rest_path + "/login/logout").success(function(data, status) {
			cf_options.rights = [];
			cf_options.user = null;
			onsuccess && onsuccess(data.message);
		}).error(function(data, status) {
			NotificationFactory.error(data);
		})
	};

	this.login = function(username, password, onsuccess) {
		$http.post(cf_options.rest_path + "/login", {
			username: username,
			password: password
		}).success(function(data, status) {
			cf_options.rights = data.rights;
			cf_options.user = data.user;
			onsuccess && onsuccess(data.rights, data.message);
		}).error(function(data, status) {
			NotificationFactory.error(data);
		})
	};

	this.check = function(callback) {
		$http.get(cf_options.rest_path + "/login/check").success(function(data, status) {
			if (!data.success) {
				cf_options.rights = [];
				cf_options.user = null;
				callback && callback(false, [], null);
			} else {
				cf_options.rights = data.rights;
				cf_options.user = data.user;
				callback && callback(data.next, data.rights, data.user);
			}
		}).error(function(data, status) {
			cf_options.rights = [];
			cf_options.user = null;
			callback && callback(false, [], null);
		})
	};

	this.getUserInfos = function(onsuccess) {
		if (userdata == null || userdata.user != cf_options.user) {
			$http.get(cf_options.rest_path + "/login/user").success(function(data, status) {
				cf_options.rights = data.rights;
				cf_options.user = data.user;
				userdata = data;
				onsuccess && onsuccess(data);
			});
		} else {
			onsuccess && onsuccess(userdata);
		}
	};

});


app.controller('LoginController', function($scope, $location, $http, LoginService, NotificationFactory) {
	init();

	function init() {}

	$scope.login = function(username, password) {
		LoginService.login(username, password, function() {
			window.location.reload();
		});
	};

});
