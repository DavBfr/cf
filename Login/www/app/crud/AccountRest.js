if (typeof app !== 'undefined') {

	app.controller('AccountController', function ($scope, $http, NotificationFactory) {
		let service_url = cf_options.rest_path + "/account";

		this.defaultError = (response) => {
			if (!restError(response)) {
				NotificationFactory.error(response.data.error || response.data);
			}
		};

		this.init = () => {
			$scope.account = {};
			$scope.changingPassword = false;
			$scope.changingEmail = false;
			$scope.changingUsername = false;
			$http.get(service_url).then((response) => {
				if (response.status !== 200) {
					restError(response);
				} else {
					$scope.account = response.data;
					if ($scope.account.email === null) {
						$scope.changingEmail = true;
					}
				}
			}, (response) => restError(response));
		};

		$scope.changePassword = () => {
			$scope.changingPassword = true;
		};

		$scope.cancelChangePassword = () => {
			$scope.changingPassword = false;
			$scope.new_pass = null;
			$scope.new_pass2 = null;
			$scope.old_pass = null;
		};

		$scope.savePassword = () => {
			$http.post(service_url + '/password', {new_pass: $scope.new_pass, old_pass: $scope.old_pass}).then((response) => {
				if (response.status !== 200) {
					this.defaultError();
				} else {
					$scope.changingPassword = false;
					NotificationFactory.success("Password successfully changed");
					$scope.new_pass = null;
					$scope.new_pass2 = null;
					$scope.old_pass = null;
				}
			}, this.defaultError);
		};

		$scope.editUsername = () => {
			$scope.changingUsername = true;
		};

		$scope.saveUsername = () => {
			$http.post(service_url + '/username', {username: $scope.account.name}).then((response) => {
				if (response.status !== 200) {
					this.defaultError();
				} else {
					$scope.changingUsername = false;
					NotificationFactory.success("User name successfully changed");
					this.init();
				}
			}, this.defaultError);
		};

		$scope.editEmail = () => {
			$scope.changingEmail = true;
		};

		$scope.saveEmail = () => {
			console.log($scope.account.email);
			$http.post(service_url + '/email', {email: $scope.account.email}).then((response) => {
				if (response.status !== 200) {
					this.defaultError();
				} else {
					$scope.changingEmail = false;
					NotificationFactory.success("Email address successfully changed");
					this.init();
				}
			}, this.defaultError);
		};

		$scope.editImage = () => {
			window.open("https://www.gravatar.com", 'gravatar');
		};

		this.init();
	});

	function AddAccountRoutes($routeProvider) {
		$routeProvider.when('/account', {
			controller: 'AccountController',
			templateUrl: cf_options.rest_path + '/account/partial',
		});
	}
}
