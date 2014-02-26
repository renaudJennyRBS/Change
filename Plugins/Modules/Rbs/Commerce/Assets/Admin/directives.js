(function (jQuery) {
	"use strict";

	var app = angular.module('RbsChange');

	app.directive('rbsCartFilterDefinitions', function() {
		return {
			restrict: 'A',
			require: '^rbsDocumentFilterContainer',
			templateUrl : 'Rbs/Commerce/cartFiltersDefinition.twig',
			replace: true,
			link: function(scope, element, attrs, filterContainerCtrl) {
				if (attrs.hasOwnProperty('definitions')) {
					var definitions = angular.fromJson(attrs['definitions']);
					angular.forEach(definitions, function(definition) {
						if (definition.hasOwnProperty('name') &&
							definition.hasOwnProperty('directiveName') &&
							definition.hasOwnProperty('config')) {
							if (!definition.hasOwnProperty('parameters')) {
								definition.parameters = {};
							}
							definition.label = definition.config.listLabel || definition.config.label;
							definition.group = definition.config.group;
							filterContainerCtrl.addFilterDefinition(definition);
						}
					});
				}
			}
		};
	});
})(window.jQuery);