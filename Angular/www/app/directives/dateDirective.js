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

app.directive("tsToDate", function() {
	return {
		require: "ngModel",
		link: function(scope, element, attrs, ctrl) {
			scope.$watch(attrs.ngModel, function(newValue, oldValue) {
				// console.log("DATE newValue", newValue, "oldValue", oldValue, "model", attrs.ngModel);
				if (Number.isInteger(newValue)) {
					ctrl.$setViewValue(new Date(newValue * 1000).toISOString().slice(0, 10));
					ctrl.$render();
				}
				return newValue;
			}, true);
		}
	};
});

app.directive("tsToTime", function() {
	return {
		require: "ngModel",
		link: function(scope, element, attrs, ctrl) {
			scope.$watch(attrs.ngModel, function(newValue, oldValue) {
				console.log("TIME newValue", newValue, "oldValue", oldValue, "model", attrs.ngModel);
				if (Number.isInteger(newValue)) {
					ctrl.$setViewValue(new Date(newValue * 1000).toISOString().slice(11, 16));
					ctrl.$render();
				}
				return newValue;
			}, true);
		}
	};
});

app.directive("tsToDatetime", function() {
	return {
		require: "ngModel",
		link: function(scope, element, attrs, ctrl) {
			scope.$watch(attrs.ngModel, function(newValue, oldValue) {
				console.log("DATETIME newValue", newValue, "oldValue", oldValue, "model", attrs.ngModel);
				if (Number.isInteger(newValue)) {
					ctrl.$setViewValue(new Date(newValue * 1000).toISOString());
					ctrl.$render();
				}
				return newValue;
			}, true);
		}
	};
});
