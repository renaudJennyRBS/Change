(function ()
{
	"use strict";

	/**
	 * @param REST
	 * @param $q
	 * @param $location
	 * @constructor
	 */
	function Editor(REST, $q, $location)
	{
		return {
			restrict : 'A',
			templateUrl : 'Document/Rbs/Catalog/ProductListItem/editor.twig',
			replace : false,
			require : 'rbsDocumentEditor',

			link: function (scope, elm, attrs, editorCtrl)
			{
				scope.onLoad = function()
				{
					// Load Product document is a product ID is found in the route params.
					var defer = $q.defer(),
						productId,
						params;

					params = $location.search();

					if (params.active === 'true')
					{
						scope.document.active = true;
					}

					if (params.product)
					{
						productId = parseInt(params.product, 10);
						if (! isNaN(productId)) {
							REST.resource(productId).then(function (product) {
								scope.document.product = product;
								defer.resolve();
							});
							return defer.promise;
						}
					}

					return null;
				};

				editorCtrl.init('Rbs_Catalog_ProductListItem');
			}
		};
	}

	Editor.$inject = ['RbsChange.REST', '$q', '$location'];
	angular.module('RbsChange').directive('rbsDocumentEditorRbsCatalogProductListItem', Editor);
})();