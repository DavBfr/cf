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
