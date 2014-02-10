(function ($) {

	"use strict";

	var app = angular.module('RbsChange');

	/**
	 * Localized version of the editor.
	 */
	function changeEditorWebsitePageTranslate(REST) {
		return {
			restrict: 'A',
			templateUrl: 'Document/Rbs/Website/StaticPage/editor-translate.twig',
			replace: false,
			require: 'rbsDocumentEditor',

			link: function (scope, element, attrs, editorCtrl) {
				scope.onLoad = function () {
					// Load Template Document
					if (scope.document.pageTemplate) {
						REST.resource(scope.document.pageTemplate).then(function (template) {
							scope.pageTemplate = { "html": template.htmlForBackoffice, "data": template.editableContent };
						});
					}
				};
				editorCtrl.init('Rbs_Website_StaticPage');
			}
		};
	}

	changeEditorWebsitePageTranslate.$inject = [
		'RbsChange.REST'
	];

	app.directive('rbsDocumentEditorRbsWebsiteStaticpageTranslate', changeEditorWebsitePageTranslate);

})(window.jQuery);