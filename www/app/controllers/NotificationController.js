app.controller('NotificationController', function ($scope, $timeout) {
	$scope.alerts = [];
	
	$scope.$on('notify', function(event, data) {
		//console.log('notify', event, data);
		$scope.alerts.push(data);
		$timeout(function () {
			var index = $scope.alerts.indexOf(data);
			if (index > -1) {
				$scope.alerts.splice(index, 1);
			}
		}, 2000);
	});

});
