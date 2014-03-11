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
