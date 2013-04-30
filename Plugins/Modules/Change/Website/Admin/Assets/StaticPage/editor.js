(function () {

	function changeEditorWebsitePage (Editor, $location, Dialog, UrlManager, ArrayUtils, Breadcrumb, structureEditorService) {

		return {
			restrict : 'C',

			templateUrl : 'Change/Website/StaticPage/editor.twig',

			replace : true,

			// Create isolated scope
			scope : {
				original : '=document',
				referenceDocument : '=',
				onSave   : '&',
				onCancel : '&',
				section  : '=',
				language : '='
			},

			link: function (scope, elm, attrs) {

				Editor.initScope(scope, elm, function () {
					scope.editableContentInfo = structureEditorService.getContentInfo(scope.document.editableContent);
					if (!scope.document.website && Breadcrumb.getWebsite()) {
						scope.document.website = Breadcrumb.getWebsite();
					}
				});

				scope.beforeSave = function beforeSaveFn (doc) {
					if (!doc.website) {
						doc.website = Breadcrumb.getWebsite();
					}
				};

				scope.editPage = function ($event, page) {
					if (scope.isUnchanged()) {
						$location.path(UrlManager.getUrl(page, 'editor'));
					} else {
						Dialog.confirmEmbed(
								elm.find('[data-role="edit-page-contents-confirmation"]'),
								"Confirmation",
								"<strong>Ce formulaire contient des données qui ne sont pas encore enregistrées.</strong><ul><li>Si vous choisissez <strong>Oui</strong>, les données de ce formulaire seront enregistrées puis vous serez redirigé vers l'éditeur de page.</li><li>Si vous choisissez <strong>Non</strong>, cette fenêtre sera fermée et... il ne se passera rien :)</li></ul>",
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
		'RbsChange.Editor',
		'$location',
		'RbsChange.Dialog',
		'RbsChange.UrlManager',
		'RbsChange.ArrayUtils',
		'RbsChange.Breadcrumb',
		'structureEditorService'
	];
	app.directive('changeEditorWebsitePage', changeEditorWebsitePage);


	/**
	 * Localized version of the editor.
	 */
	function changeEditorWebsitePageLocalized (Editor, $location, Dialog, UrlManager) {
		var directive = changeEditorWebsitePage (Editor, $location, Dialog, UrlManager);
		directive.templateUrl = 'Change/Website/StaticPage/editor-localized.twig';
		return directive;
	}

	changeEditorWebsitePageLocalized.$inject = changeEditorWebsitePage.$inject;
	app.directive('changeEditorWebsitePageLocalized', changeEditorWebsitePageLocalized);

})();