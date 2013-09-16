(function () {

	"use strict";

	function editorRbsGeoAddressFields (EditorManager, REST, ArrayUtils) {

		return {

			restrict : 'EC',
			templateUrl : 'Rbs/Geo/AddressFields/editor.twig',
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

				scope.cascadeCreateField = function(){
					EditorManager.cascade(REST.newResource('Rbs_Geo_AddressField'), scope.document.label, function(doc){scope.document.fields.push(doc);});
				};

				scope.cascadeEditField = function(index){
					REST.resource(scope.document.fields[index]).then(
						function(doc) {
							scope.cascadeEdit(doc, scope.document.label, function(doc){scope.document.fields[index] = doc;});
						}
					)
				};
			}
		};
	}

	editorRbsGeoAddressFields.$inject = ['RbsChange.EditorManager', 'RbsChange.REST', 'RbsChange.ArrayUtils'];
	angular.module('RbsChange').directive('rbsDocumentEditorRbsGeoAddressFields', editorRbsGeoAddressFields);
})();