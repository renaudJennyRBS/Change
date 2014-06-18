(function(jQuery) {
	"use strict";

	function Editor($compile, $routeParams, REST) {
		function redrawDiscountParameterize($compile, scope, directiveName) {
			var container = jQuery('#RbsDiscountDiscountParametersData');
			var collection = container.children();
			collection.each(function() {
				angular.element(jQuery(this)).isolateScope().$destroy();
			});
			collection.remove();

			if (directiveName) {
				var html = '<div ' + directiveName + '="" parameters="document.parametersData" discount="document"></div>'
				container.html(html);
				$compile(container.children())(scope);
			}
		}

		return {
			restrict: 'A',
			require: '^rbsDocumentEditorBase',

			link: function(scope, element, attrs, editorCtrl) {
				scope.onLoad = function() {
					if (scope.document.isNew() && $routeParams.hasOwnProperty('orderProcessId') && !scope.document.orderProcess) {
						REST.resource('Rbs_Commerce_Process', $routeParams['orderProcessId']).then(function(process) {
							scope.document.orderProcess = process;
							scope.document.orderProcessId = process.id;
						})
					}

					if (!angular.isObject(scope.document.parametersData) || angular.isArray(scope.document.parametersData)) {
						scope.document.parametersData = {};
					}

					if (!angular.isObject(scope.document.cartFilterData) || angular.isArray(scope.document.cartFilterData)) {
						scope.document.cartFilterData = {};
					}
				};

				scope.$watch('document.discountType', function(directiveName) {
					redrawDiscountParameterize($compile, scope, directiveName);
				});
			}
		}
	}

	Editor.$inject = ['$compile', '$routeParams', 'RbsChange.REST'];
	angular.module('RbsChange').directive('rbsDocumentEditorRbsDiscountDiscountNew', Editor);
	angular.module('RbsChange').directive('rbsDocumentEditorRbsDiscountDiscountEdit', Editor);
})(window.jQuery);