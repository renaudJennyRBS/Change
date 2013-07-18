(function () {

	"use strict";

	function changeEditorWebsiteMenu(Editor, Breadcrumb) {
		return {
			restrict: 'EC',
			templateUrl: 'Rbs/Website/Menu/editor.twig',
			replace: true,

			// Create isolated scope
			scope: {
				original: '=document',
				onSave: '&',
				onCancel: '&',
				section: '='
			},

			link: function (scope, elm, attrs) {
				Editor.initScope(scope, elm, function () {
					if (!scope.document.website && Breadcrumb.getCurrentNode()) {
						scope.document.website = Breadcrumb.getCurrentNode();
					}
				});
			}
		};
	}

	changeEditorWebsiteMenu.$inject = ['RbsChange.Editor', 'RbsChange.Breadcrumb'];

	angular.module('RbsChange').directive('editorChangeWebsiteMenu', changeEditorWebsiteMenu);
})();