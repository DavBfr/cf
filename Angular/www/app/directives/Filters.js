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

app.filter('telephone', function () {
	return function (input) {
		let number = input || '';

		// Clean up input by removing whitespaces and unnessary chars
		number = number.trim().replace(/[-\s()]/g, '');

		if (number.length === 10) {
			let area = number.substr(0, 2) + ")";
			let local = "#{number[3..5]}-#{number[6...]}";

			// (111) 111-1111
			number = number.substr(0, 2) + " " + number.substr(2, 2) + " " + number.substr(4, 2) + " " + number.substr(6, 2) + " " + number.substr(8, 2);
		}

		return number;
	};
});

app.filter('unsafehtml', function ($sce) {
	return $sce.trustAsHtml;
});

app.filter('ouinon', function () {
	return function (input) {
		if (parseInt(input)) {
			return "Oui";
		}

		return "Non";
	};
});
