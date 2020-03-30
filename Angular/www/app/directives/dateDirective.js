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

app.directive("tsToDate", function () {
	return {
		require: "ngModel",
		link: function (scope, element, attrs, ctrl) {
			ctrl.$parsers.push(function (inputValue) {
				return new Date(inputValue + new Date().toISOString().substr(10));
			});
			scope.$watch(attrs.ngModel, function (newValue, oldValue) {
				if (Number.isInteger(newValue)) {
					const dv = new Date(newValue * 1000);
					let lz = function (n) {
						return n > 9 ? n.toString() : '0' + n.toString();
					};
					ctrl.$setViewValue(dv.getFullYear() + "-" + lz(dv.getMonth() + 1) + "-" + lz(dv.getDate()));
					ctrl.$render();
				}
				return newValue;
			}, true);
		}
	};
});

app.directive("tsToTime", function () {
	return {
		require: "ngModel",
		link: function (scope, element, attrs, ctrl) {
			ctrl.$parsers.push(function (inputValue) {
				return new Date('1970-01-01T' + inputValue);
			});
			scope.$watch(attrs.ngModel, function (newValue, oldValue) {
				if (Number.isInteger(newValue)) {
					const dv = new Date(newValue * 1000);
					let lz = function (n) {
						return n > 9 ? n.toString() : '0' + n.toString();
					};
					ctrl.$setViewValue(lz(dv.getHours()) + ":" + lz(dv.getMinutes()));

					ctrl.$render();
				}
				return newValue;
			}, true);
		}
	};
});

app.directive("tsToDatetime", function () {
	return {
		require: "ngModel",
		link: function (scope, element, attrs, ctrl) {
			ctrl.$parsers.push(function (inputValue) {
				return new Date(inputValue);
			});
			scope.$watch(attrs.ngModel, function (newValue, oldValue) {
				if (Number.isInteger(newValue)) {
					const dv = new Date(newValue * 1000);
					let lz = function (n) {
						return n > 9 ? n.toString() : '0' + n.toString();
					};
					ctrl.$setViewValue(dv.getFullYear() + "-" + lz(dv.getMonth() + 1) + "-" + lz(dv.getDate()) + "T" +
						lz(dv.getHours()) + ":" + lz(dv.getMinutes()));
					ctrl.$render();
				}
				return newValue;
			}, true);
		}
	};
});
