/**
* Copyright (C) 2013-2014 David PHAM-VAN
*
* This program is free software; you can redistribute it and/or
* modify it under the terms of the GNU General Public License
* as published by the Free Software Foundation; version 2
* of the License.
*
* This program is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
* GNU General Public License for more details.
*
* You should have received a copy of the GNU General Public License
* along with this program; if not, write to the Free Software
* Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
**/

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
