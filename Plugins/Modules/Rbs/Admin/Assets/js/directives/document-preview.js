(function() {
	"use strict";

	var app = angular.module('RbsChange');

	function snakeCase(name, separator) {
		separator = separator || '-';
		return name.replace(/[A-Z]/g, function(letter, pos) {
			return (pos ? separator : '') + letter.toLowerCase();
		});
	}

	app.directive('rbsDocumentPreview', ['RbsChange.REST', '$injector', '$compile', function(REST, $injector, $compile) {
		return {
			restrict: 'E',
			scope: {
				document: '='
			},

			link: function(scope, iElement) {
				scope.$watch('document', function(doc) {
					var html, tag, directiveName;
					if (doc && doc.model) {
						directiveName = 'rbsDocumentPreview' + doc.model.replace(/_/g, '');
						if ($injector.has(directiveName + 'Directive')) {
							tag = snakeCase(directiveName);
							html = '<' + tag + ' document="document"></' + tag + '>';
						}
						else {
							html = '<div data-ng-bind="document.label"></div>';
						}

						$compile(html)(scope, function(el) {
							iElement.empty().append(el);
						});

						REST.ensureLoaded(doc).then(function(doc) {
							angular.extend(scope.document, doc);
						});
					}
				});
			}
		};
	}]);
})();
