(function () {

	"use strict";

	function editorChangeThemePageTemplate (Editor, Breadcrumb) {

		return {
			restrict : 'EC',

			templateUrl : 'Change/Theme/PageTemplate/editor.twig',

			replace : true,

			// Create isolated scope
			scope: {
				original: '=document',
				onSave: '&',
				onCancel: '&',
				section: '='
			},

			link : function (scope, elm) {
				Editor.initScope(scope, elm, function () {
					scope.document.theme = Breadcrumb.getCurrentNode();
				});
			}
		};

	}

	editorChangeThemePageTemplate.$inject = ['RbsChange.Editor', 'RbsChange.Breadcrumb'];

	angular.module('RbsChange').directive('editorChangeThemePageTemplate', editorChangeThemePageTemplate);

})();