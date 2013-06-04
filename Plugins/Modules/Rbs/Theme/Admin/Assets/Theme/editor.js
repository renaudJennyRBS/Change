(function () {

	"use strict";

	function editor (Editor) {

		return {
			restrict : 'EC',

			templateUrl : 'Rbs/Theme/Theme/editor.twig',

			replace : true,

			// Create isolated scope
			scope: {
				original: '=document',
				onSave: '&',
				onCancel: '&',
				section: '='
			},

			link : function (scope, elm) {
				Editor.initScope(scope, elm);
			}
		};

	}

	editor.$inject = ['RbsChange.Editor'];

	angular.module('RbsChange').directive('editorRbsThemeTheme', editor);

})();