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
					console.log("Editor Website/Topic: beforeSaveFn: website=", Breadcrumb.getWebsite().id);
					if (!doc.website) {
						doc.website = Breadcrumb.getWebsite().id;
					}
				};
			}
		};

	}

	changeEditorWebsiteTopic.$inject = ['RbsChange.Editor', 'RbsChange.Breadcrumb'];

	angular.module('RbsChange').directive('changeEditorWebsiteTopic', changeEditorWebsiteTopic);

})();