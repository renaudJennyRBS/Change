(function ()
{
	"use strict";

	/**
	 * @param $timeout
	 * @param $http
	 * @param Loading
	 * @param REST
	 * @constructor
	 */
	function Editor($timeout, $http, Loading, REST)
	{
		return {
			restrict : 'C',
			templateUrl : 'Rbs/Catalog/Product/editor.twig',
			replace : false,
			require : 'rbsDocumentEditor',

			link: function (scope, elm, attrs, editorCtrl)
			{
				scope.onLoad = function() {
					if (!angular.isArray(scope.document.attributeValues))
					{
						scope.document.attributeValues = [];
					}
				}

				scope.onReady = function() {

					loadCategorizations();
				};

				scope.attributeGroupId = null;

				editorCtrl.init('Rbs_Catalog_Product');

				function loadCategorizations () {
					if (scope.document.META$.links.hasOwnProperty('productcategorizations')) {
						REST.collection(scope.document.META$.links['productcategorizations'].href).then(function(result){
							if (angular.isObject(result) && result.hasOwnProperty('resources'))
							{
								scope.categorizations = result.resources;
							}
						});
					}
					else {
						scope.categorizations = [];
					}
				}

				scope.cascadeEditCategorization = function(cat){
					scope.cascade(cat, scope.document.label, function(doc){loadCategorizations();});
				};

				scope.cascadeCreateCategorization = function(doc){
					var newCat = REST.newResource('Rbs_Catalog_ProductCategorization');
					newCat.product = scope.document;
					scope.cascade(newCat, scope.document.label, function(doc){loadCategorizations()});
				};

				scope.toggleHighlight = function(doc){
					var url = null;
					if (!doc.isHighlighted)
					{
						url = doc.META$.actions['downplay'].href;
					}
					else
					{
						url = doc.META$.actions['highlight'].href;
					}
					if (url)
					{
						Loading.start();
						$http.get(url)
							.success(function (data) {
								loadCategorizations();
								Loading.stop();
							})
							.error(function errorCallback(data, status) {
								Loading.stop();
							}
						);
					}
				};

				scope.deleteCategorization = function(doc){
					REST['delete'](doc).then(function(){
						loadCategorizations();
					});
				};

				scope.$watch('document.attribute', function(newValue){
					if (newValue)
					{
						if (angular.isObject(newValue) && newValue.hasOwnProperty('id'))
						{
							scope.attributeGroupId = newValue.id;
						}
						else
						{
							var groupId = parseInt(newValue, 10);
							if (isNaN(groupId))
							{
								scope.attributeGroupId = null;
							}
							else
							{
								scope.attributeGroupId = groupId;
							}
						}
					}
					else
					{
						scope.attributeGroupId = null;
					}
				});
			}
		};
	}

	Editor.$inject = ['$timeout', '$http', 'RbsChange.Loading', 'RbsChange.REST'];
	angular.module('RbsChange').directive('rbsDocumentEditorRbsCatalogProduct', Editor);
})();