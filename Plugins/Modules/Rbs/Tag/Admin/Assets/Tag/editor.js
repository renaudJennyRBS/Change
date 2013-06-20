(function () {

	function editorFn (Editor) {

		return {
			restrict : 'EC',

			templateUrl : 'Rbs/Tag/Tag/editor.twig',

			replace : true,

			// Create isolated scope
			scope : {
				original: '=document',
				onSave: '&',
				onCancel: '&',
				section: '='
			},

			link : function (scope, elm) {
				Editor.initScope(scope, elm, function () {
					scope.saveAsChildOf('auto', 'children');
				});
			}
		};

	}

	editorFn.$inject = ['RbsChange.Editor'];

	angular.module('RbsChange').directive('editorChangeTagTag', editorFn);

})();