(function () {

	"use strict";

	function changeEditorWebsiteMenu(Editor, Breadcrumb, REST) {

		var I18N_KEY_REGEXP = /^([a-zA-Z0-9]+\.?)+$/,
			ALL_REGEXP = /^.+$/;

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
				scope.add = {};
				scope.add.titlePattern = ALL_REGEXP;
				scope.addItemUIShown = false;

				Editor.initScope(scope, elm, function () {
					if (!scope.document.website && Breadcrumb.getCurrentNode()) {
						scope.document.website = Breadcrumb.getCurrentNode();
					}

					scope.toggleAddItemUI = function ($event) {
						$event.preventDefault();
						scope.addItemUIShown = ! scope.addItemUIShown;
					};

					scope.onDocumentSelected = function (doc) {
						scope.add.title = doc.title || doc.label;
					};

					scope.$watch('add.titleI18nKey', function (i18nKey, old) {
						if (i18nKey !== old) {
							scope.add.titlePattern = i18nKey ? I18N_KEY_REGEXP : ALL_REGEXP;
						}
					}, true);

					scope.addItem = function () {
						var item = {};
						item[scope.add.titleI18nKey ? 'titleKey' : 'title'] = scope.add.title;

						if (scope.add.selectedDocument) {
							item.documentId = scope.add.selectedDocument.id;
						}
						else {
							item.url = scope.add.url;
						}
						scope.document.items.push(item);

						scope.add.selectedDocument = null;
					};

				});
			}
		};
	}

	changeEditorWebsiteMenu.$inject = ['RbsChange.Editor', 'RbsChange.Breadcrumb', 'RbsChange.REST'];

	angular.module('RbsChange').directive('editorChangeWebsiteMenu', changeEditorWebsiteMenu);
})();