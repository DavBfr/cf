function CrudService($http, service) {

	var service_url = cf_options.rest_path + "/" + service;

	var onerror = function(data, status) {
		console.log(data, status);
	};

	this.get_list = function (filter, page, onsuccess, onerror) {
		if (page<0) {
			onsuccess && onsuccess([], 200);
			return;
		}
		if (filter == undefined)
			filter = "";
		$http.get(service_url + "?p="+page+"&q="+filter).success(function (data, status) {
			if (data.success)
				onsuccess && onsuccess(data.list, status);
			else
				onerror && onerror(data.error);
		}).error(function (data, status) {
			onerror && onerror(data, status);
		});
	};

	this.get_count = function (filter, onsuccess, onerror) {
		if (filter == undefined)
			filter = "";
		$http.get(service_url + "/count?q="+filter).success(function (data, status) {
			if (data.success)
				onsuccess && onsuccess(data);
			else
				onerror && onerror(data.error);
		}).error(function (data, status) {
			onerror && onerror(data, status);
		});
	};

	this.add = function (data, onsuccess, onerror) {
		$http.put(service_url, data).success(function (data, status) {
			if (data.success)
				onsuccess && onsuccess(data.id);
			else
				onerror && onerror(data.error);
		}).error(function (data, status) {
			onerror && onerror(data);
		});
	};

	this.del = function (id, onsuccess, onerror) {
		$http.delete(service_url + "/" + id).success(function (data, status) {
			if (data.success)
				onsuccess && onsuccess();
			else
				onerror && onerror(data.error);
		}).error(function (data, status) {
			onerror && onerror(data);
		});
	};

	this.save = function (id, data, onsuccess, onerror) {
		$http.post(service_url + "/" + id, data).success(function (data, status) {
			if (data.success)
				onsuccess && onsuccess();
			else
				onerror && onerror(data.error);
		}).error(function (data, status) {
			onerror && onerror(data);
		});
	};

	this.getOne = function (id, onsuccess, onerror) {
		$http.get(service_url + "/" + id).success(function (data, status) {
			if (data.success)
				onsuccess && onsuccess(data.data, status);
			else
				onerror && onerror(data.error);
		}).error(function (data, status) {
			onerror && onerror(data, status);
		});
	};

}


function CrudController($scope, $timeout, $location, $route, CrudService, NotificationFactory) {
	this.init = function () {
		$scope.perpages = null;
		$scope.list = [];
		$scope.item = {};
		$scope.filter = "";
		$scope.pages = 0;
		$scope.count = 0;
		$scope.page = 0;
		$scope.loading = true;
	};

	this.get_list = function() {
		CrudService.get_count($scope.filter, function (count) {
			$scope.count = count.count;
			$scope.pages = count.pages;
			$scope.perpages = count.limit;
			$scope.setPage(CrudService.page || 0);
		});
	};

	$scope.go_list = function() {
		var path = (new RegExp("^/[^/]+")).exec($route.current.originalPath)[0];
		$location.path(path);
	};

	$scope.go_detail = function(id) {
		var path = (new RegExp("^/[^/]+")).exec($route.current.originalPath)[0];
		$location.path(path + "/" + id);
	};

	this.get_fiche = function(id) {
		CrudService.getOne(id, function (data) {
			$scope.loading = false;
			$scope.id = id;
			$scope.item = data;
		}, function (data) {
			$scope.loading = false;
			NotificationFactory.error(data);
		});
	};

	$scope.del = function(id) {
		NotificationFactory.confirm("Delete the record #" + id + " ?", function () {
			CrudService.del(id, function () {
				var path = (new RegExp("^/[^/]+")).exec($route.current.originalPath)[0];
				if (path == $route.current.originalPath)
					this.get_list();
				else
					$scope.go_list();
				NotificationFactory.success("Record #"+ id +" deleted");
			}.bind(this), function (data) {
				NotificationFactory.error(data);
			});
		}.bind(this));
	}.bind(this);


	$scope.save = function(id, data) {
		$scope.loading = true;
		if (id) {
			CrudService.save(id, data, function () {
				$scope.go_list();
				//this.get_fiche(id);
				NotificationFactory.success("Record #"+ id +" deleted");
			}.bind(this), function (data) {
				NotificationFactory.error(data);
			});
		} else {
			CrudService.add(data, function (id) {
				$scope.go_list();
				//this.get_fiche(id);
				NotificationFactory.success("New record #"+ id +" saved");
			}.bind(this), function (data) {
				$scope.loading = false;
				NotificationFactory.error(data);
			});
		}
	}.bind(this);

	$scope.getPages = function() {
		return new Array($scope.pages);
	};

	$scope.setPage = function(num) {
		if (num < 0)
			num = 0;
		if (num > $scope.pages -1)
			num = $scope.pages -1;

		$scope.loading = true;

		$scope.page = num;
		CrudService.get_list($scope.filter, $scope.page, function (data) {
			$('html,body').scrollTop(0);
			$scope.list = data;
			CrudService.page = $scope.page;
			$scope.loading = false;
		}, function (data) {
			$scope.loading = false;
			NotificationFactory.error(data);
		});
	};
	
	$scope.Search = function () {
		this.get_list();
	}.bind(this);
}
