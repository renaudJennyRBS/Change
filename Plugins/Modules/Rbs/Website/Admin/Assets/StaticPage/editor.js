(function () {

	"use strict";

	function changeEditorWebsitePage (Editor, $rootScope, $location, Dialog, UrlManager, Breadcrumb, i18n, structureEditorService) {

		return {
			restrict    : 'C',
			templateUrl : 'Rbs/Website/StaticPage/editor.twig',
			replace     : true,

			// Create isolated scope
			scope : {
				original : '=document',
				referenceDocument : '=',
				onSave   : '&',
				onCancel : '&',
				section  : '=',
				language : '='
			},

			link: function (scope, elm) {

				Editor.initScope(scope, elm, function () {
					scope.editableContentInfo = structureEditorService.getContentInfo(scope.document.editableContent);
					if (!scope.document.section && Breadcrumb.getCurrentNode()) {
						scope.document.section = Breadcrumb.getCurrentNode();
					}
				});

				$rootScope.$watch('website', function (website) {
					if (scope.document && ! scope.document.website) {
						scope.document.website = website;
					}
				}, true);

				scope.editPage = function ($event, page) {
					if (scope.isUnchanged()) {
						$location.path(UrlManager.getUrl(page, 'editor'));
					} else {
						Dialog.confirmEmbed(
							elm.find('[data-role="edit-page-contents-confirmation"]'),
							i18n.trans('m.rbs.admin.admin.js.confirm | ucf'),
							i18n.trans('m.rbs.website.admin.js.open-page-editor-warning'),
							scope,
							{
								"pointedElement" : $event.target
							}
						).then(function () {
							scope.onSave = function () {
								$location.path(UrlManager.getUrl(page, 'editor'));
							};
							scope.submit();
						});
					}
				};

			}
		};

	}

	var app = angular.module('RbsChange');

	changeEditorWebsitePage.$inject = [
		'RbsChange.Editor', '$rootScope',
		'$location',
		'RbsChange.Dialog',
		'RbsChange.UrlManager',
		'RbsChange.Breadcrumb',
		'RbsChange.i18n',
		'structureEditorService'
	];
	app.directive('changeEditorWebsitePage', changeEditorWebsitePage);


	/**
	 * Localized version of the editor.
	 */
	function changeEditorWebsitePageLocalized (Editor, $location, Dialog, UrlManager) {
		var directive = changeEditorWebsitePage (Editor, $location, Dialog, UrlManager);
		directive.templateUrl = 'Rbs/Website/StaticPage/editor-localized.twig';
		return directive;
	}

	changeEditorWebsitePageLocalized.$inject = changeEditorWebsitePage.$inject;
	app.directive('changeEditorWebsitePageLocalized', changeEditorWebsitePageLocalized);

})();