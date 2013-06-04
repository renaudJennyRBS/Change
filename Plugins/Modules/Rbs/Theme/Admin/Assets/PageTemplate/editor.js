(function () {

	"use strict";

	function editorRbsThemePageTemplate (Editor, Breadcrumb) {

		return {
			restrict : 'EC',

			templateUrl : 'Rbs/Theme/PageTemplate/editor.twig',

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

	editorRbsThemePageTemplate.$inject = ['RbsChange.Editor', 'RbsChange.Breadcrumb'];

	angular.module('RbsChange').directive('editorRbsThemePageTemplate', editorRbsThemePageTemplate);

})();