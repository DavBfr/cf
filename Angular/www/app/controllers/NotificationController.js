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

app.controller('NotificationController', function ($scope, $timeout) {
	$scope.alerts = [];

	$scope.$on('notify', function(event, data) {
		$scope.alerts.push(data);
		$timeout(function () {
			var index = $scope.alerts.indexOf(data);
			if (index > -1) {
				$scope.alerts.splice(index, 1);
			}
		}, 5000);
	});

	$scope.$on('confirm', function(event, data) {
		$scope.confirm = data;
		$('#confirm').modal();
	});

});
