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
	function WebsiteListController ($scope, DocumentList, Breadcrumb, MainMenu) {

		var DL = DocumentList.initScope($scope, 'Change_Users_User');

		DL.viewMode = 'list';
		DL.sort.column = 'modificationDate';
		DL.sort.descending = true;

		Breadcrumb.resetLocation([["Utilisateurs", "Change/Users/User"]]);

		$scope.createActions = [{ 'label': "Utilisateur", 'url': 'Change/Users/User/new' }];

		DL.columns.push(new DocumentList.Column('activated', "Activé", true, 'center', '90px'));

		MainMenu.loadModuleMenu('Change_Users');
	}

	WebsiteListController.$inject = [
		'$scope',
		'RbsChange.DocumentList',
		'RbsChange.Breadcrumb',
		'RbsChange.MainMenu'
	];
	app.controller('Change_Users_User_ListController', WebsiteListController);


	/**
	 * Controller for Website forms.
	 *
	 * @param $scope
	 * @param FormsManager
	 * @constructor
	 * @param Breadcrumb
	 */
	function WebsiteFormController ($scope, FormsManager, Breadcrumb) {

		Breadcrumb.resetLocation([["Utilisateurs", "Change/Users/User"]]);
		// Let FormsManager search for 'id' and 'LCID' parameters in the $routeParams service.
		FormsManager.initResource($scope, 'Change_Users_User');

	}

	WebsiteFormController.$inject = [
		'$scope',
		'RbsChange.FormsManager',
		'RbsChange.Breadcrumb'
	];
	app.controller('Change_Users_User_FormController', WebsiteFormController);

})();