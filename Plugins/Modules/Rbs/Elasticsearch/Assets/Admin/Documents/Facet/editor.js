/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
(function (jQuery)
{
	"use strict";

	function Editor($compile)
	{
		function redrawConfigurationOptions($compile, scope, configurationType) {
			var container = jQuery('#RbsElasticsearchFacetConfigurationOptions');
			var collection = container.children();
			collection.each(function() {
				angular.element(jQuery(this)).isolateScope().$destroy();
			});
			collection.remove();

			if (configurationType) {
				var directiveName = 'rbs-elasticsearch-facet-' + configurationType.toLowerCase();
				var html = '<div ' + directiveName + '="" facet="document"></div>'
				container.html(html);
				$compile(container.children())(scope);
			}
		}

		return {
			restrict: 'A',
			require: '^rbsDocumentEditorBase',

			link : function (scope, elm, attrs) {
				scope.$watch('document.configurationType', function(configurationType) {
					redrawConfigurationOptions($compile, scope, configurationType);
				});
			}
		};
	}

	Editor.$inject = ['$compile'];
	angular.module('RbsChange').directive('rbsDocumentEditorRbsElasticsearchFacetEdit', Editor);
	angular.module('RbsChange').directive('rbsDocumentEditorRbsElasticsearchFacetNew', Editor);
})(window.jQuery);