(function () {

	function changeEditorWebsiteWebsite (Editor) {

		return {
			restrict : 'EC',

			templateUrl : 'Rbs/Website/Website/editor.twig',

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

	changeEditorWebsiteWebsite.$inject = ['RbsChange.Editor'];

	angular.module('RbsChange').directive('changeEditorWebsiteWebsite', changeEditorWebsiteWebsite);

})();