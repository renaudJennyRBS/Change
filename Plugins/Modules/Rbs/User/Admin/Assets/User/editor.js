(function () {

	function editorChangeUserUser (Editor) {

		return {
			restrict : 'EC',

			templateUrl : 'Rbs/User/User/editor.twig',

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

	editorChangeUserUser.$inject = ['RbsChange.Editor'];

	angular.module('RbsChange').directive('editorChangeUserUser', editorChangeUserUser);

})();