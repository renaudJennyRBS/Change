(function () {

	function editorChangeCollectionItem (Editor) {

		return {
			restrict : 'EC',

			templateUrl : 'Rbs/Collection/Item/editor.twig',

			replace: true,

			// Create isolated scope
			scope: {
				original: '=document',
				onSave: '&',
				onCancel: '&',
				section: '='
			},

			link : function (scope, elm, attrs) {
				Editor.initScope(scope, elm);
			}
		};

	}

	editorChangeCollectionItem.$inject = ['RbsChange.Editor'];

	angular.module('RbsChange').directive('editorChangeCollectionItem', editorChangeCollectionItem);

})();