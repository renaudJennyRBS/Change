(function () {

	"use strict";

	function changeEditorWebsiteMenu(REST, $routeParams, $q)
	{
		var I18N_KEY_REGEXP = /^([a-zA-Z0-9]+\.?)+$/,
			ALL_REGEXP = /^.+$/,
			tmpId = -1;
		return {
			restrict    : 'A',
			require     : '^rbsDocumentEditorBase',

			link : function (scope, element, attrs, editorCtrl)
			{
				scope.menuContext = {
					add: {
						titlePattern : ALL_REGEXP,
						id: null
					},
					addItemUIShown: false
				};

				scope.onSaveContext = function (currentContext) {
					currentContext.savedData('menu', {
						menuContext: scope.menuContext
					});
				};

				scope.onRestoreContext = function (currentContext) {
					var toRestoreData = currentContext.savedData('menu');
					scope.menuContext = toRestoreData.menuContext;
				};

				scope.onSelect = function (data) {
					scope.menuContext.addItemUIShown = true;
					scope.menuContext.add.id = data.id;
					if (data.hasOwnProperty('documentId')) {
						REST.resource(data.documentId).then(function (document) {
							scope.menuContext.add.document = {title: data.label, selected: document};
						});
						scope.menuContext.add.url = {title: null, selected: null};
					} else {
						scope.menuContext.add.url = {title: data.label, selected: data.url};
						scope.menuContext.add.document = {title: null, selected: null};
					}
				};

				scope.onLoad = function () {
					if (angular.isArray(scope.document.items)) {
						angular.forEach(scope.document.items, function (item) {
							item.id = tmpId--;
						});
					} else {
						scope.document.items = [];
					}
				};

				scope.onReady = function () {
					scope.onDocumentSelected = function (doc) {
						scope.menuContext.add.document.title = doc.title || doc.label;
					};

					scope.$watch('add.titleI18nKey', function (i18nKey, old) {
						if (i18nKey !== old) {
							scope.menuContext.add.titlePattern = i18nKey ? I18N_KEY_REGEXP : ALL_REGEXP;
						}
					}, true);
				};

				scope.toggleAddItemUI = function ($event) {
					$event.preventDefault();
					scope.menuContext.addItemUIShown = ! scope.menuContext.addItemUIShown;
					if (!scope.menuContext.addItemUIShown) {
						scope.menuContext.add.id = null;
					}
				};

				scope.addDocument = function () {
					var oldId = scope.menuContext.add.id;
					var item = {};
					item[scope.menuContext.add.document.titleI18nKey ? 'titleKey' : 'title'] = scope.menuContext.add.document.title;
					item.label = item.title || item.titleKey;
					item.documentId = scope.menuContext.add.document.selected.id;
					item.id = tmpId--;

					var items = [];
					angular.forEach(scope.document.items, function(it) {
						if (it.id !== oldId) {
							items.push(it);
						} else {
							items.push(item);
							item = null;
						}
					});

					scope.document.items = items;
					if (item) {
						scope.document.items.push(item);
					}

					scope.menuContext.add.document = {title: null, selected: null};
					scope.menuContext.add.id = null;
				};

				scope.addUrl = function () {
					var oldId = scope.menuContext.add.id;
					var item = {};
					item[scope.menuContext.add.url.titleI18nKey ? 'titleKey' : 'title'] = scope.menuContext.add.url.title;
					item.label = item.title || item.titleKey;
					item.url = scope.menuContext.add.url.selected;
					item.id = tmpId--;

					var items = [];
					angular.forEach(scope.document.items, function(it) {
						if (it.id !== oldId) {
							items.push(it);
						} else {
							items.push(item);
							item = null;
						}
					});

					scope.document.items = items;
					if (item) {
						scope.document.items.push(item);
					}

					scope.menuContext.add.url = {title: null, selected: null};
					scope.menuContext.add.id = null;
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
			}
		};
	}
	changeEditorWebsiteMenu.$inject = ['RbsChange.REST', '$routeParams', '$q'];
	angular.module('RbsChange').directive('rbsDocumentEditorRbsWebsiteMenu', changeEditorWebsiteMenu);
})();