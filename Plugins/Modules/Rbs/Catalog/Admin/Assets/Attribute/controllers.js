(function () {
	"use strict";

	var app = angular.module('RbsChange');

	app.directive('rbsAttributeEditor', ['RbsChange.REST', 'RbsChange.Utils', '$timeout', rbsAttributeEditorDirective]);

	function rbsAttributeEditorDirective (REST, Utils, $timeout) {

		return {
			restrict : 'E',
			scope : {attributeValues: "=", attributeEditor: "=" , attributeProductProperties: "="},
			templateUrl : "Rbs/Catalog/Attribute/attributeEditor.twig",

			link : function (scope, elm, attrs) {
				var edtId = null;
				scope.attributes = [];

				scope.$watch('attributeEditor', function (value) {
					var attrId = Utils.isDocument(value) ? value.id : parseInt(value, 10);
					if (!isNaN(attrId))
					{
						if (attrId !== edtId)
						{
							edtId = attrId;
							REST.resource('Rbs_Catalog_Attribute', attrId).then(generateEditor, clearEditor);
						}
					}
					else
					{
						edtId = null;
						clearEditor();
					}
				});

				scope.$watch('attributeValues', function (value, oldvalue) {
					if (angular.isArray(scope.attributes))
					{
						assocValues(scope.attributes);
					}
				}, true);

				function clearEditor() {
					scope.attributes = [];

					$timeout(function () {
						scope.$emit('Change:Editor:UpdateMenu');
					});
				}

				function generateEditor(attribute) {
					var editorDefinition = attribute.editorDefinition;
					if (angular.isObject(editorDefinition))
					{
						scope.attributes = editorDefinition.attributes;
						if (angular.isArray(scope.attributeValues))
						{
							assocValues(scope.attributes);
						}
					}

					$timeout(function () {
						scope.$emit('Change:Editor:UpdateMenu');
					});
				}

				function assocValues(attributes) {
					for (var i = 0; i < attributes.length; i++)
					{
						if (attributes[i].attributes)
						{
							assocValues(attributes[i].attributes)
						}
						else
						{
							attributes[i].value = getAttributeValue(attributes[i]);
						}
					}
				}

				function getAttributeValue(attribute) {
					if (angular.isArray(scope.attributeValues))
					{
						var v = null;
						for (var i = 0; i < scope.attributeValues.length; i++)
						{
							v = scope.attributeValues[i];
							if (v.id == attribute.id)
							{
								if (v.value === null && attribute.valueType == 'Property' && 'propertyName' in attribute)
								{
									v.value = scope.attributeProductProperties[attribute.propertyName];
								}
								return v;
							}
						}
						var defaultValue = attribute.defaultValue;
						if (attribute.valueType == 'Property' && 'propertyName' in attribute)
						{
							defaultValue = scope.attributeProductProperties[attribute.propertyName];
						}

						var v = {id: attribute.id, valueType: attribute.valueType, value: defaultValue};
						scope.attributeValues.push(v);
						return v;
					}
					return null;
				}
			}
		}
	}

	app.directive('rbsAttributeItem', ['RbsChange.Utils', rbsAttributeItemDirective]);

	function rbsAttributeItemDirective (Utils) {

		return {
			restrict : 'E',
			scope : {attribute: "="},
			templateUrl : "Rbs/Catalog/Attribute/attributeItem.twig",
			link : function (scope, elm, attrs) {
			}
		}
	}
})();