(function() {
	"use strict";

	function Editor($http, REST, Utils) {
		return {
			restrict: 'A',
			require: '^rbsDocumentEditorBase',

			link: function(scope) {
				// FIXME Is this used? I could not find it in the template...
				scope.data = {
					lengthUnit: 'm'
				};

				scope.stockInfoData = {};
				scope.onReady = function() {

					if (!scope.document.isNew())
					{
						// Load stock infos
						var url = Utils.makeUrl('resources/Rbs/Stock/Sku/' + scope.document.id + '/stockInfo/');
						$http.get(REST.getBaseUrl(url)).success(function(data) {
							scope.stockInfoData = data;
						});
					}

				}
			}
		};
	}

	Editor.$inject = ['$http', 'RbsChange.REST', 'RbsChange.Utils'];
	angular.module('RbsChange').directive('rbsDocumentEditorRbsStockSkuNew', Editor);
	angular.module('RbsChange').directive('rbsDocumentEditorRbsStockSkuEdit', Editor);
})();