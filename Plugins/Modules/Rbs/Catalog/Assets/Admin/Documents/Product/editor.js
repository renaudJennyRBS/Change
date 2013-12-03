(function ()
{
	"use strict";

	/**
	 * @param $timeout
	 * @param $http
	 * @param REST
	 * @param EditorManager
	 * @constructor
	 */
	function Editor($timeout, $http, REST, EditorManager)
	{
		return {
			restrict : 'C',
			templateUrl : 'Document/Rbs/Catalog/Product/editor.twig',
			replace : false,
			require : 'rbsDocumentEditor',

			link: function (scope, elm, attrs, editorCtrl)
			{
				scope.onReady = function() {
					scope.loadItems();
				};

				scope.loadItems = function() {
					if (scope.document.META$.links.hasOwnProperty('productListItems')) {
						REST.collection(scope.document.META$.links['productListItems'].href).then(function(result){
							if (angular.isObject(result) && result.hasOwnProperty('resources'))
							{
								scope.productListItems = result.resources;
							}
						});
					}
					else {
						scope.productListItems = [];
					}
				};



				scope.cascadeEditProductListItem = editorCtrl.registerEditCascade('items');



				scope.cascadeCreateProductListItem = function ()
				{
					EditorManager.cascade(
						{
							'model' : 'Rbs_Catalog_ProductListItem',
							'values' : {
								'product': scope.document.id,
								'active' : true,
							}
						},
						'<i class="icon-pencil"></i> ' + scope.document.label
					);
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
						$http.get(url)
							.success(function (data) {
								scope.loadItems();
							}
						);
					}
				};

				scope.deleteProductListItem = function(doc){
					REST['delete'](doc).then(function(){
						scope.loadItems();
					});
				};

				scope.attributeGroupId = null;

				scope.attributesDef = [];
				scope.propAttr = {};

				scope.$watch('document.attribute', function(newValue){
					var attrGrpId = null;
					if (newValue)
					{
						if (angular.isObject(newValue) && newValue.hasOwnProperty('id'))
						{
							attrGrpId = newValue.id;
						}
						else
						{
							attrGrpId = parseInt(newValue, 10);
							if (isNaN(attrGrpId))
							{
								attrGrpId = null;
							}
						}
					}

					if (attrGrpId != scope.attributeGroupId)
					{
						scope.clearAttributesEditor();
						scope.attributeGroupId = attrGrpId;
					}
				});

				scope.$watch('document.attributeValues', function(newValue) {

					if (newValue === null)
					{
						scope.document.attributeValues = [];
					}

					if (newValue !== undefined)
					{
						scope.assocValues(scope.attributesDef);
					}
				});

				scope.$watch('attributeGroupId', function(newValue) {
					if (newValue)
					{
						REST.resource('Rbs_Catalog_Attribute', newValue).then(scope.generateAttributesEditor);
					}
				});

				scope.clearAttributesEditor = function (){
					scope.attributesDef = [];
					scope.propAttr = {};
					$timeout(function () {
						scope.$emit('Change:Editor:UpdateMenu');
					});
				};

				scope.generateAttributesEditor = function (attribute) {
					var editorDefinition = attribute.editorDefinition;
					if (angular.isObject(editorDefinition))
					{
						if (!angular.isArray(scope.document.attributeValues))
						{
							scope.document.attributeValues = [];
						}

						scope.attributesDef = editorDefinition.attributes;
						scope.assocValues(scope.attributesDef);
					}

					$timeout(function () {
						scope.$emit('Change:Editor:UpdateMenu');
					});
				};

				scope.assocValues = function (attributes) {
					var attributeValues = scope.document.attributeValues;
					for (var i = 0; i < attributes.length; i++)
					{
						if (attributes[i].attributes)
						{
							scope.assocValues(attributes[i].attributes)
						}
						else
						{
							scope.setAttributeValue(attributes[i], attributeValues);
						}
					}
				};

				scope.getAttributeValueById = function (id, attributeValues) {
					var v, i;
					for (i = 0; i < attributeValues.length; i++) {
						v = attributeValues[i];
						if (v.id == id) {
							return v;
						}
					}
					return null;
				};

				scope.setAttributeValue = function (attribute, attributeValues) {
					var v = {value: attribute.defaultValue};
					var valIndex = scope.getAttributeValueById(attribute.id, attributeValues);

					if (attribute.valueType == 'Property') {
						var av = scope.document[attribute.propertyName];
						if (valIndex == null)
						{
							valIndex = {id: attribute.id, valueType:attribute.valueType};
							attributeValues.push(valIndex);
							if (av !== null)
							{
								v.value = av;
							}
						}
						else
						{
							v.value = av;
						}
						scope.propAttr[attribute.propertyName] = v;
					}
					else
					{
						if (valIndex == null)
						{
							v.id = attribute.id;
							v.valueType = attribute.valueType;
							attributeValues.push(v);
						}
						else
						{
							v = valIndex;
						}
					}
					attribute.value = v;
				};

				scope.$watch('propAttr', function(newValue) {
					if (newValue)
					{
						angular.forEach(scope.propAttr, function(value, key) {
							if (scope.document.hasOwnProperty(key))
							{
								scope.document[key] = value.value;
							}
						})
					}
				}, true);

				editorCtrl.init('Rbs_Catalog_Product');
			}
		};
	}

	Editor.$inject = ['$timeout', '$http', 'RbsChange.REST', 'RbsChange.EditorManager'];
	angular.module('RbsChange').directive('rbsDocumentEditorRbsCatalogProduct', Editor);
})();