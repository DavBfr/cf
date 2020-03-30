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

function CrudService($http, service) {

	let service_url = cf_options.rest_path + "/" + service;

	let onerror = function (data, status) {
	};

	this.getServiceUrl = function () {
		return service_url;
	};

	this.get_list = function (filter, page, onsuccess, onerror) {
		if (page < 0) {
			onsuccess && onsuccess([], 200);
			return;
		}
		if (filter === undefined)
			filter = {};

		filter.p = page;
		$http.get(service_url, {params: filter}).then(function (response) {
			if (response.status !== 200) {
				if (!restError(response))
					onerror && onerror(response.data, response.status);
			} else
				onsuccess && onsuccess(response.data.list, response.status);
		}, function (response) {
			if (!restError(response))
				onerror && onerror(response.data, response.status);
		});
	};

	this.get_count = function (filter, onsuccess, onerror) {
		if (filter === undefined)
			filter = {};
		$http.get(service_url + "/count", {params: filter}).then(function (response) {
			if (response.status !== 200) {
				if (!restError(response))
					onerror && onerror(response.data, response.status);
			} else
				onsuccess && onsuccess(response.data);
		}, function (response) {
			if (!restError(response))
				onerror && onerror(response.data, response.status);
		});
	};

	this.add = function (data, onsuccess, onerror) {
		$http.put(service_url, data).then(function (response) {
			if (response.status !== 200) {
				if (!restError(response))
					onerror && onerror(response.data, response.status);
			} else
				onsuccess && onsuccess(data.id);
		}, function (response) {
			if (!restError(response))
				onerror && onerror(response.data);
		});
	};

	this.del = function (id, onsuccess, onerror) {
		$http.delete(service_url + "/" + id).then(function (response) {
			if (response.status !== 200) {
				if (!restError(response))
					onerror && onerror(response.data, response.status);
			} else
				onsuccess && onsuccess();
		}, function (response) {
			if (!restError(response))
				onerror && onerror(response.data);
		});
	};

	this.save = function (id, data, onsuccess, onerror) {
		$http.post(service_url + "/" + id, data).then(function (response) {
			if (response.status !== 200) {
				if (!restError(response))
					onerror && onerror(response.data, response.status);
			} else
				onsuccess && onsuccess();
		}, function (response) {
			if (!restError(response))
				onerror && onerror(response.data);
		});
	};

	this.getOne = function (id, onsuccess, onerror) {
		$http.get(service_url + "/get/" + id).then(function (response) {
			if (response.status !== 200) {
				if (!restError(response))
					onerror && onerror(response.data, response.status);
			} else
				onsuccess && onsuccess(response.data, response.status);
		}, function (response) {
			if (!restError(response))
				onerror && onerror(response.data, response.status);
		});
	};

	this.getNew = function (onsuccess, onerror) {
		$http.get(service_url + "/new").then(function (response) {
			if (response.status !== 200) {
				if (!restError(response))
					onerror && onerror(response.data, response.status);
			} else
				onsuccess && onsuccess(response.data, response.status);
		}, function (response) {
			if (!restError(response))
				onerror && onerror(response.data, response.status);
		});
	};

	this.getForeign = function (field, onsuccess, onerror) {
		$http.get(service_url + "/foreign/" + field).then(function (response) {
			if (response.status !== 200) {
				if (!restError(response))
					onerror && onerror(response.data, response.status);
			} else
				onsuccess && onsuccess(response.data, response.status);
		}, function (response) {
			if (!restError(response))
				onerror && onerror(response.data, response.status);
		});
	};

}


function CrudController($scope, $timeout, $location, $route, CrudService, NotificationFactory) {
	this.init = function () {
		$scope.perpages = null;
		$scope.list = [];
		$scope.item = {};
		$scope.foreign = {};
		$scope.filter = CrudService.filter || {};
		$scope.pages = 0;
		$scope.count = 0;
		$scope.page = null;
		$scope.loading = true;
	};

	this.get_list = function () {
		CrudService.get_count($scope.filter, function (count) {
			$scope.count = count.count;
			$scope.pages = count.pages;
			$scope.perpages = count.limit;
			$scope.setPage(CrudService.page || 0);
		});
	};

	$scope.go_list = function () {
		let path = (new RegExp("^/[^/]+")).exec($route.current.originalPath)[0];
		$location.path(path);
	};

	$scope.go_detail = function (id) {
		let path = (new RegExp("^/[^/]+")).exec($route.current.originalPath)[0];
		$location.path(path + "/" + id);
	};

	this.get_fiche = function (id) {
		if (!id || id === 'new') {
			$scope.id = null;
			CrudService.getNew(function (data) {
				$scope.item = data.data;
				$scope.extra = data.extra;
				if (data.foreigns) {
					for (let item in data.foreigns) {
						if (data.foreigns.hasOwnProperty(item)) {
							let name = data.foreigns[item];
							(function (name_) {
								CrudService.getForeign(name, function (data) {
									$scope.foreign[name_] = data.list;
								});
							})(name);
						}
					}
				}
				$scope.loading = false;
			}, function (data) {
				$scope.loading = false;
				NotificationFactory.error(data);
			});
		} else {
			CrudService.getOne(id, function (data) {
				$scope.id = id;
				$scope.item = data.data;
				$scope.extra = data.extra;
				if (data.foreigns) {
					for (let item in data.foreigns) {
						if (data.foreigns.hasOwnProperty(item)) {
							let name = data.foreigns[item];
							(function (name_) {
								CrudService.getForeign(name, function (data) {
									$scope.foreign[name_] = data.list;
								});
							})(name);
						}
					}
				}
				$scope.loading = false;
			}, function (data) {
				$scope.loading = false;
				NotificationFactory.error(data);
			});
		}
	};

	$scope.del = function (id) {
		NotificationFactory.confirm("Delete the record #" + id + " ?", function () {
			CrudService.del(id, function () {
				let path = (new RegExp("^/[^/]+")).exec($route.current.originalPath)[0];
				if (path === $route.current.originalPath) {
					$scope.page = -1;
					this.get_list();
				} else {
					$scope.go_list();
				}
				NotificationFactory.success("Record #" + id + " deleted");
			}.bind(this), function (data) {
				NotificationFactory.error(data);
			});
		}.bind(this));
	}.bind(this);


	$scope.save = function (id, data) {
		$scope.loading = true;
		for (let item in data) {
			if (data.hasOwnProperty(item)) {
				if (data[item] instanceof Date) {
					data[item] = Math.floor(data[item].getTime() / 1000);
				}
			}
		}
		if (id) {
			CrudService.save(id, data, function () {
				$scope.go_list();
				NotificationFactory.success("Record #" + id + " saved");
			}.bind(this), function (data) {
				NotificationFactory.error(data);
			});
		} else {
			CrudService.add(data, function (id) {
				$scope.go_list();
				NotificationFactory.success("New record #" + id + " saved");
			}.bind(this), function (data) {
				$scope.loading = false;
				NotificationFactory.error(data);
			});
		}
	}.bind(this);

	$scope.getPages = function () {
		const max = 7;
		const hmax = Math.floor(max / 2);

		if ($scope.pages < max || $scope.page < hmax) {
			let list = new Array(Math.min($scope.pages, max));
			for (let i = 0; i < list.length; i++) list[i] = i;
			return list;
		}

		let list = new Array(max);

		if ($scope.page + hmax > $scope.pages) {
			for (let i = 0; i < list.length; i++) list[i] = $scope.pages - max + i;
			return list;
		}

		for (let i = 0; i < list.length; i++) list[i] = i + $scope.page - hmax;
		return list;
	};

	$scope.setPage = function (num) {
		if (num < 0)
			num = 0;
		if (num > $scope.pages - 1)
			num = $scope.pages - 1;

		if (num === $scope.page) return;

		$scope.loading = true;

		$scope.page = num;
		CrudService.get_list($scope.filter, $scope.page, function (data) {
			$('html,body').scrollTop(0);
			$scope.list = data;
			CrudService.page = $scope.page;
			CrudService.filter = $scope.filter;
			$scope.loading = false;
		}, function (data) {
			$scope.loading = false;
			NotificationFactory.error(data);
		});
	};

	$scope.Search = function () {
		$scope.page = -1;
		this.get_list();
	}.bind(this);
}
