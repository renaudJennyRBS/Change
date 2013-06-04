(function () {

	"use strict";

	function changeEditorWebsiteTopic (Editor, Breadcrumb) {

		return {
			restrict    : 'EC',
			templateUrl : 'Rbs/Website/Topic/editor.twig',
			replace     : true,

			// Create isolated scope
			scope : {
				original : '=document',
				onSave   : '&',
				onCancel : '&',
				section  : '='
			},

			link : function (scope, elm) {
				Editor.initScope(scope, elm, function () {
					if (!scope.document.website) {
						scope.document.website = Breadcrumb.getWebsite();
					}
				});
			}
		};

	}

	changeEditorWebsiteTopic.$inject = ['RbsChange.Editor', 'RbsChange.Breadcrumb'];

	angular.module('RbsChange').directive('changeEditorWebsiteTopic', changeEditorWebsiteTopic);

})();