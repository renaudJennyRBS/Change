(function () {

	function editorChangeCollectionCollection (Editor) {

		return {
			restrict : 'EC',

			templateUrl : 'Rbs/Collection/Collection/editor.twig',

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

	editorChangeCollectionCollection.$inject = ['RbsChange.Editor'];

	angular.module('RbsChange').directive('editorChangeCollectionCollection', editorChangeCollectionCollection);

})();