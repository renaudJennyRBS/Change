(function () {

	"use strict";

	var app = angular.module('RbsChange');


	/**
	 * Controller for Website list.
	 *
	 * @param $scope
	 * @param DocumentList
	 * @param Breadcrumb
	 * @param MainMenu
	 * @constructor
	 */
	function WebsiteListController ($scope, DocumentList, Breadcrumb, MainMenu, i18n) {

		var DL = DocumentList.initScope($scope, 'Change_Website_Website');

		DL.viewMode = 'list';
		DL.sort.column = 'modificationDate';
		DL.sort.descending = true;

		Breadcrumb.resetLocation([["Sites et pages", "Change/Website"], ["Sites web", "Change/Website/Website"]]);

		$scope.createActions = [{ 'label': "Site web", 'url': 'Change/Website/Website/new' }];

		DL.columns.push({ id: 'hostName', label: i18n.trans("m.change.website.admin.js.host-name|ucf") });
		DL.columns.push({ id: 'modificationDate', label: i18n.trans("m.change.admin.admin.js.last-modification-date|ucf") });
		DL.columns.push(new DocumentList.Column('activated', "Activé", true, 'center', '90px'));

		MainMenu.loadModuleMenu('Change_Website');
	}

	WebsiteListController.$inject = [
		'$scope',
		'RbsChange.DocumentList',
		'RbsChange.Breadcrumb',
		'RbsChange.MainMenu',
		'RbsChange.i18n'
	];
	app.controller('Change_Website_Website_ListController', WebsiteListController);


	/**
	 * Controller for Website forms.
	 *
	 * @param $scope
	 * @param FormsManager
	 * @constructor
	 * @param Breadcrumb
	 */
	function WebsiteFormController ($scope, FormsManager, Breadcrumb) {

		Breadcrumb.setLocation([["Sites et pages", "Change/Website"], ["Sites web", "Change/Website/Website"]]);
		// Let FormsManager search for 'id' and 'LCID' parameters in the $routeParams service.
		FormsManager.initResource($scope, 'Change_Website_Website');

	}

	WebsiteFormController.$inject = [
		'$scope',
		'RbsChange.FormsManager',
		'RbsChange.Breadcrumb'
	];
	app.controller('Change_Website_Website_FormController', WebsiteFormController);

})();