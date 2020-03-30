app.directive('ngMarkdown', [function () {
	return {
		restrict: 'EA',
		require: '?ngModel',
		link: function (scope, elem, attrs, ngModel) {
			let smde = new EasyMDE({
				element: elem[0],
				toolbar: attrs.toolbar ? JSON.parse(attrs.toolbar) : ["bold", "italic", "heading", "|", "code", "quote", "unordered-list", "ordered-list", "link", "image", "horizontal-rule", "|", "preview"],
				hideIcons: attrs.hideIcons ? JSON.parse(attrs.hideIcons) : ["side-by-side", "fullscreen", "guide"],
				placeholder: attrs.placeholder || '',
				spellChecker: false
			});

			smde.codemirror.on("change", function (instance) {
				let newValue = instance.getValue();
				if (newValue !== ngModel.$viewValue) {
					scope.$evalAsync(function () {
						ngModel.$setViewValue(newValue);
					});
				}
			});

			ngModel.$render = function () {
				let safeViewValue = ngModel.$viewValue || '';
				scope.$evalAsync(function () {
					smde.value(safeViewValue);
				});
			};
		}
	};
}]);
