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
//			templateUrl : 'Document/Rbs/Geo/Address/editor.twig',
			replace : false,
			require : 'rbsDocumentEditor',

			link: function (scope, elm, attrs, editorCtrl)
			{

				scope.addressFieldsId = null;

				scope.fieldsDef = [];

				scope.$watch('document.addressFields', function(newValue){
					var fieldsId = null;
					if (newValue)
					{
						if (angular.isObject(newValue) && newValue.hasOwnProperty('id'))
						{
							fieldsId = newValue.id;
						}
						else
						{
							fieldsId = parseInt(newValue, 10);
							if (isNaN(fieldsId))
							{
								fieldsId = null;
							}
						}
					}

					if (fieldsId != scope.addressFieldsId)
					{
						scope.clearFieldsEditor();
						scope.addressFieldsId = fieldsId;
					}
				});

				scope.$watch('document.fieldValues', function(newValue) {
					if (newValue === null)
					{
						scope.document.fieldValues = {};
					}

					if (newValue !== undefined)
					{
						scope.assocValues(scope.fieldsDef);
					}
				});


				scope.$watch('addressFieldsId', function(newValue) {
					if (newValue)
					{
						REST.resource('Rbs_Geo_AddressFields', newValue).then(scope.generateFieldsEditor);
					}
				});

				scope.clearFieldsEditor = function (){
					scope.fieldsDef = [];
					$timeout(function () {
						scope.$emit('Change:Editor:UpdateMenu');
					});
				};

				scope.generateFieldsEditor = function (addressFields) {
					var editorDefinition = addressFields.editorDefinition;
					if (angular.isObject(editorDefinition))
					{
						if (!angular.isObject(scope.document.fieldValues))
						{
							scope.document.fieldValues = {};
						}

						scope.fieldsDef = editorDefinition.fields;
						scope.assocValues(scope.fieldsDef);
					}

					$timeout(function () {
						scope.$emit('Change:Editor:UpdateMenu');
					});
				};

				scope.assocValues = function (fields) {
					var fieldValues = scope.document.fieldValues;
					var field;
					for (var i = 0; i < fields.length; i++)
					{
						field = fields[i];
						var v = null;
						if(fieldValues.hasOwnProperty(field.code)) {
							v = fieldValues[field.code];
						}
						if(v === null) {
							v = field.defaultValue;
							fieldValues[field.code] = v;
						}
					}
				};

				editorCtrl.init('Rbs_Geo_Address');
			}
		};
	}

	Editor.$inject = ['$timeout', '$http', 'RbsChange.REST', 'RbsChange.EditorManager'];
	angular.module('RbsChange').directive('rbsDocumentEditorRbsGeoAddress', Editor);
})();