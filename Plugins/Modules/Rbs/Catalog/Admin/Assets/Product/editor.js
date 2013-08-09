(function ()
{
	"use strict";

	/**
	 * @param Editor
	 * @param i18n
	 * @param $timeout
	 * @constructor
	 */
	function Editor(Editor, i18n, $timeout, $http, Loading, REST, FormsManager)
	{
		return {
			restrict: 'EC',
			templateUrl: 'Rbs/Catalog/Product/editor.twig',
			replace: true,
			// Create isolated scope
			scope: { original: '=document', onSave: '&', onCancel: '&', section: '=' },
			link: function (scope, elm)
			{
				var loadCategorizations = function(){
					REST.collection(scope.document.META$.links['productcategorizations'].href).then(function(result){
						if (angular.isObject(result) && result.hasOwnProperty('resources'))
						{
							scope.categorizations = result.resources;
							Loading.stop();
						}
					});
				};

				Editor.initScope(scope, elm, function() {
					if (!angular.isArray(scope.document.attributeValues))
					{
						scope.document.attributeValues = [];
					}
					loadCategorizations();
				});

				scope.cascadeEditCategorization = function(cat){
					FormsManager.cascade('Rbs/Catalog/ProductCategorization/form.twig', {id:cat.id}, function(doc){loadCategorizations()}, scope.document.label);
				};

				scope.cascadeCreateCategorization = function(doc){
					var newCat = REST.newResource('Rbs_Catalog_ProductCategorization');
					newCat.product = scope.document;
					scope.cascadeCreate(newCat, scope.document.label, function(doc){loadCategorizations()});
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
						$http.get(url).success(function (data)
						{
							loadCategorizations();
							Loading.stop();
						}).error(function errorCallback(data, status)
						{
							Loading.stop();
						});
					}
				};

				scope.deleteCategorization = function(doc){
					REST.delete(doc).then(function(){
						loadCategorizations();
					});
				};




				scope.$watch('document.attribute', function(newValue, oldValue){
					if (!angular.isUndefined(newValue))
					{
						if (angular.isObject(newValue) && newValue.hasOwnProperty('id'))
						{
							scope.document.attribute = newValue.id;
						}
						else if (newValue == '')
						{
							scope.document.attribute = null;
							$timeout(function () {
								scope.$emit('Change:Editor:UpdateMenu');
							});

						}
					}
				});



				// Prices.
				scope.createActions = [
					{ 'label': i18n.trans('m.rbs.catalog.admin.js.price | ucf'), 'url': 'Rbs/Catalog/Price/new', 'icon': 'file' }
				];
			}
		};
	}

	Editor.$inject = ['RbsChange.Editor', 'RbsChange.i18n', '$timeout', '$http', 'RbsChange.Loading', 'RbsChange.REST', 'RbsChange.FormsManager'];
	angular.module('RbsChange').directive('editorRbsCatalogProduct', Editor);
})();