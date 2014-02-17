(function () {

	"use strict";

	function editorRbsGeoAddressFields (EditorManager, REST, ArrayUtils) {

		return {

			restrict : 'EA',
			templateUrl : 'Document/Rbs/Geo/AddressFields/editor.twig',
			replace : false,
			require : 'rbsDocumentEditor',

			link : function (scope, element, attrs, editorCtrl) {

				scope.onReady = function(){
					if (!angular.isArray(scope.document.fields))
					{
						scope.document.fields = [];
					}
				};

				editorCtrl.init('Rbs_Geo_AddressFields');

				scope.moveTop = function(index){
					ArrayUtils.move(scope.document.fields, index, 0);
				};

				scope.moveUp = function(index){
					ArrayUtils.move(scope.document.fields, index, index-1);
				};

				scope.moveBottom = function(index){
					ArrayUtils.move(scope.document.fields, index, scope.document.fields.length-1);
				};

				scope.moveDown = function(index){
					ArrayUtils.move(scope.document.fields, index, index+1);
				};

				scope.remove = function(index){
					scope.document.fields.splice(index, 1);
				};

				scope.deleteField = function(fieldToBeDelete){
					var index = null;
					angular.forEach(scope.document.fields, function (field, i) {
						if (field.id === fieldToBeDelete.id)
						{
							index = i;
						}
					});
					scope.remove(index);
				};

				scope.cascadeCreate = editorCtrl.registerCreateCascade('fields', 'Rbs_Geo_AddressField');
				scope.cascadeEdit = editorCtrl.registerEditCascade('fields');
			}
		};
	}

	editorRbsGeoAddressFields.$inject = ['RbsChange.EditorManager', 'RbsChange.REST', 'RbsChange.ArrayUtils'];
	angular.module('RbsChange').directive('rbsDocumentEditorRbsGeoAddressFields', editorRbsGeoAddressFields);

})();