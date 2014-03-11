app.factory('NotificationFactory', function($window, $rootScope) {
	return {
		error: function(msg) {
			//$window.alert(msg);
			$rootScope.$broadcast('notify', {'type':'danger', 'message':msg});
		},
		success: function(msg) {
			//$window.alert(msg);
			$rootScope.$broadcast('notify', {'type':'success', 'message':msg});
		},
		confirm: function(msg, onYes, onNo) {
			$rootScope.$broadcast('confirm', {'message':msg, onYes:onYes, onNo:onNo});
/*
			if ($window.confirm(msg)) {
				onYes && onYes();
			} else {
				onNo && onNo();
			}
*/
		}
	};
});
