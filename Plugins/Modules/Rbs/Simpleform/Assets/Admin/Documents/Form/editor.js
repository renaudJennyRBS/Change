(function ()
{
	"use strict";

	/**
Z	 * @constructor
	 */
	function Editor(REST, EditorManager, ArrayUtils)
	{
		return {
			restrict : 'A',
			templateUrl : 'Document/Rbs/Simpleform/Form/editor.twig',
			replace : false,
			require : 'rbsDocumentEditor',

			link: function (scope, elm, attrs, editorCtrl)
			{
				scope.fieldManager = {};

				scope.fieldManager.cascadeCreate = editorCtrl.registerCreateCascade('fields', 'Rbs_Simpleform_Field');
				scope.fieldManager.cascadeEdit = editorCtrl.registerEditCascade('fields');

				scope.fieldManager.moveTop = function(index){
					ArrayUtils.move(scope.document.fields, index, 0);
				};

				scope.fieldManager.moveUp = function(index){
					ArrayUtils.move(scope.document.fields, index, index-1);
				};

				scope.fieldManager.moveBottom = function(index){
					ArrayUtils.move(scope.document.fields, index, scope.document.fields.length-1);
				};

				scope.fieldManager.moveDown = function(index){
					ArrayUtils.move(scope.document.fields, index, index+1);
				};

				scope.fieldManager.remove = function(index){
					scope.document.fields.splice(index, 1);
				};

				editorCtrl.init('Rbs_Simpleform_Form');
			}
		};
	}

	Editor.$inject = ['RbsChange.REST', 'RbsChange.EditorManager', 'RbsChange.ArrayUtils'];
	angular.module('RbsChange').directive('rbsDocumentEditorRbsSimpleformForm', Editor);
})();