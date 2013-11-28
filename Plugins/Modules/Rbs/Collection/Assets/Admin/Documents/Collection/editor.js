(function () {

	"use strict";

	function editorChangeCollectionCollection (EditorManager, REST, ArrayUtils) {

		return {

			restrict : 'EC',
			templateUrl : 'Document/Rbs/Collection/Collection/editor.twig',
			replace : false,
			require : 'rbsDocumentEditor',

			link : function (scope, element, attrs, editorCtrl) {
				scope.onReady = function(){
					if (!angular.isArray(scope.document.items))
					{
						scope.document.items = [];
					}
				};

				editorCtrl.init('Rbs_Collection_Collection');

				scope.moveTop = function(index){
					if (angular.isArray(scope.document.items))
					{
						ArrayUtils.move(scope.document.items, index, 0);
					}
				};

				scope.moveUp = function(index){
					if (angular.isArray(scope.document.items))
					{
						ArrayUtils.move(scope.document.items, index, index-1);
					}
				};

				scope.moveBottom = function(index){
					if (angular.isArray(scope.document.items))
					{
						ArrayUtils.move(scope.document.items, index, scope.document.items.length-1);
					}
				};

				scope.moveDown = function(index){
					if (angular.isArray(scope.document.items))
					{
						ArrayUtils.move(scope.document.items, index, index+1);
					}
				};

				scope.remove = function(index){
					if (angular.isArray(scope.document.items))
					{
						scope.document.items.splice(index, 1);
					}
				};


				scope.cascadeCreateItem = editorCtrl.registerCreateCascade('items', 'Rbs_Collection_Item');
				scope.cascadeEditItem = editorCtrl.registerEditCascade('items');
			}
		};

	}

	editorChangeCollectionCollection.$inject = ['RbsChange.EditorManager', 'RbsChange.REST', 'RbsChange.ArrayUtils'];

	angular.module('RbsChange').directive('rbsDocumentEditorRbsCollectionCollection', editorChangeCollectionCollection);

})();