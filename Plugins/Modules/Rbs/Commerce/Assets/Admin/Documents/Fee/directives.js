(function (jQuery) {

	"use strict";

	var app = angular.module('RbsChange');

	app.directive('rbsCartFilterDefinitions', ['RbsChange.REST', function(REST) {
		return {
			restrict: 'A',
			require: '^rbsDocumentFilterContainer',
			template : '<div></div>',
			replace: true,
			scope: true,
			link: function(scope, element, attrs, filterContainerCtrl) {
				REST.call(REST.getBaseUrl('commerce/cartFiltersDefinition/'), {}).then(function(definitions){
					angular.forEach(definitions, function(definition) {
						if (definition.hasOwnProperty('name') &&
							definition.hasOwnProperty('directiveName') &&
							definition.hasOwnProperty('config')) {
							if (!definition.hasOwnProperty('parameters'))
							{
								definition.parameters = {};
							}
							definition.label = definition.config.listLabel;
							definition.group = definition.config.group;
							filterContainerCtrl.addFilterDefinition(definition);
						}
					});
				});
			}
		};
	}]);
})(window.jQuery);