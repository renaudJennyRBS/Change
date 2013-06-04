(function () {

	function editorChangeUsersUser (Editor) {

		return {
			restrict : 'EC',

			templateUrl : 'Rbs/Users/User/editor.twig',

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

	editorChangeUsersUser.$inject = ['RbsChange.Editor'];

	angular.module('RbsChange').directive('editorChangeUsersUser', editorChangeUsersUser);

})();