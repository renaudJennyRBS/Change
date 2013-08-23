(function () {

	"use strict";

	function changeEditorWebsiteMenu(Breadcrumb, REST) {

		var I18N_KEY_REGEXP = /^([a-zA-Z0-9]+\.?)+$/,
			ALL_REGEXP = /^.+$/;

		return {
			restrict    : 'EC',
			templateUrl : 'Rbs/Website/Menu/editor.twig',
			replace     : true,
			require     : 'rbsDocumentEditor',

			link: function (scope, element, attrs, editorCtrl) {
				scope.add = {};
				scope.add.titlePattern = ALL_REGEXP;
				scope.addItemUIShown = false;

				scope.onReady = function () {
					if (!scope.document.website && Breadcrumb.getCurrentNode()) {
						scope.document.website = Breadcrumb.getCurrentNode();
					}

					var website = scope.document.website;
					Breadcrumb.setPath([[website.label, website.url('menus')]]);


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
						if (! scope.document.items) {
							scope.document.items = [];
						}
						scope.document.items.push(item);

						scope.add.selectedDocument = null;
					};

				};

				editorCtrl.init('Rbs_Website_Menu');
			}
		};
	}

	changeEditorWebsiteMenu.$inject = ['RbsChange.Breadcrumb', 'RbsChange.REST'];

	angular.module('RbsChange').directive('rbsDocumentEditorRbsWebsiteMenu', changeEditorWebsiteMenu);
})();