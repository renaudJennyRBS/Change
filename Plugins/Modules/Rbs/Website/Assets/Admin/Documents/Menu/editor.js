(function () {

	"use strict";

	function changeEditorWebsiteMenu(Breadcrumb, REST, $routeParams, $q, UrlManager) {

		var I18N_KEY_REGEXP = /^([a-zA-Z0-9]+\.?)+$/,
			ALL_REGEXP = /^.+$/;

		return {
			restrict    : 'EA',
			templateUrl : 'Document/Rbs/Website/Menu/editor.twig',
			replace     : false,
			require     : 'rbsDocumentEditor',

			link : function (scope, element, attrs, editorCtrl) {
				scope.add = {};
				scope.add.titlePattern = ALL_REGEXP;
				scope.addItemUIShown = false;

				scope.onReady = function () {
					var website = scope.document.website;
					if (website) {
						console.log("website=", website);
						Breadcrumb.setPath([[website.label, UrlManager.getUrl(website, null, 'menus')]]);
					}
					else {
						scope.$watch('document.website', function (website, old) {
							if (website && website !== old) {
								Breadcrumb.setPath([[website.label, UrlManager.getUrl(website, null, 'menus')]]);
							}
						}, true);
					}

					scope.toggleAddItemUI = function ($event) {
						$event.preventDefault();
						scope.addItemUIShown = ! scope.addItemUIShown;
					};

					scope.onDocumentSelected = function (doc) {
						scope.add.document.title = doc.title || doc.label;
					};

					scope.$watch('add.titleI18nKey', function (i18nKey, old) {
						if (i18nKey !== old) {
							scope.add.titlePattern = i18nKey ? I18N_KEY_REGEXP : ALL_REGEXP;
						}
					}, true);

					scope.addDocument = function () {
						var item = {};
						item[scope.add.document.titleI18nKey ? 'titleKey' : 'title'] = scope.add.document.title;
						item.documentId = scope.add.document.selected.id;
						if (! scope.document.items) {
							scope.document.items = [];
						}
						scope.document.items.push(item);
						scope.add.document.selected = null;
						scope.add.document.title = null;
					};

					scope.addUrl = function () {
						var item = {};
						item[scope.add.url.titleI18nKey ? 'titleKey' : 'title'] = scope.add.url.title;
						item.url = scope.add.url.selected;
						if (! scope.document.items) {
							scope.document.items = [];
						}
						scope.document.items.push(item);
						scope.add.url.selected = null;
						scope.add.url.title = null;
					};
				};

				scope.initDocument = function () {
					// Edition ('id' is present): let the Editor does his job and load the Document!
					if ($routeParams.hasOwnProperty('id')) {
						return null;
					}

					// Creation: we need to load the 'parent' Website to init the new Menu with it.
					var	defer = $q.defer(),
						menu = REST.newResource('Rbs_Website_Menu');

					if ($routeParams.hasOwnProperty('website')) {
						REST.resource('Rbs_Website_Website', $routeParams.website).then(function (website) {
							menu.website = website;
							defer.resolve(menu);
						});
					}
					else {
						defer.resolve(menu);
					}
					return defer.promise;
				};

				editorCtrl.init('Rbs_Website_Menu');

			}
		};
	}

	changeEditorWebsiteMenu.$inject = ['RbsChange.Breadcrumb', 'RbsChange.REST', '$routeParams', '$q', 'RbsChange.UrlManager'];

	angular.module('RbsChange').directive('rbsDocumentEditorRbsWebsiteMenu', changeEditorWebsiteMenu);
})();