(function () {

	function editorChangeUserGroup (Editor) {

		return {
			restrict : 'EC',

			templateUrl : 'Rbs/User/Group/editor.twig',

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

	editorChangeUserGroup.$inject = ['RbsChange.Editor'];

	angular.module('RbsChange').directive('editorChangeUserGroup', editorChangeUserGroup);

})();