(function () {

	"use strict";

	function changeEditorWebsiteTopic (Editor, Breadcrumb) {

		return {
			restrict : 'EC',

			templateUrl : 'Change/Website/Topic/editor.twig',

			replace: true,

			// Create isolated scope
			scope: {
				original: '=document',
				onSave: '&',
				onCancel: '&',
				section: '='
			},

			link : function (scope, elm) {
				Editor.initScope(scope, elm);

				scope.beforeSave = function beforeSaveFn (doc) {
					if (!doc.website) {
						doc.website = Breadcrumb.getWebsite();
					}
				};
			}
		};

	}

	changeEditorWebsiteTopic.$inject = ['RbsChange.Editor', 'RbsChange.Breadcrumb'];

	angular.module('RbsChange').directive('changeEditorWebsiteTopic', changeEditorWebsiteTopic);

})();