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

app.filter('telephone', function () {
  return function (input) {
      var number = input || '';

      // Clean up input by removing whitespaces and unnessary chars
      number = number.trim().replace(/[-\s\(\)]/g, '');

      if (number.length == 10) {
        var area = number.substr(0,2) + ")";
        var local = "#{number[3..5]}-#{number[6...]}";

        // (111) 111-1111
        number = number.substr(0,2) + " " + number.substr(2,2) + " " + number.substr(4,2) + " " + number.substr(6,2) + " " + number.substr(8,2);
      }

      return number;
  };
});

app.filter('ouinon', function () {
  return function (input) {
      if (parseInt(input)) {
				return "Oui";
			}
			
			return "Non";
  };
});
