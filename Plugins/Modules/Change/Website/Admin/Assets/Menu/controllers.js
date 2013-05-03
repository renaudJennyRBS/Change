(function () {

	"use strict";

	var app = angular.module('RbsChange');



	function MenuListController ($scope, $routeParams, DocumentList, Breadcrumb, Loading, ArrayUtils, MainMenu, REST) {

		DocumentList.initScope($scope, "Change_Website_Menu");

		Breadcrumb.resetLocation([["Sites et pages", "Change/Website"], ["Menus de navigation", "Change/Website/Menu"]]);

		$scope.createActions = [{ 'label': "Menu", 'url': 'Change/Website/Menu/new' }];

		MainMenu.loadModuleMenu('Change_Website');

	}

	MenuListController.$inject = [
		'$scope', '$routeParams',
		'RbsChange.DocumentList',
		'RbsChange.Breadcrumb',
		'RbsChange.Loading',
		'RbsChange.ArrayUtils',
		'RbsChange.MainMenu',
		'RbsChange.REST'
	];
	app.controller('Change_Website_Menu_ListController', MenuListController);

	//-------------------------------------------------------------------------

	/**
	 * @name website.MenuFormController
	 * @description Handles the menu form.
	 */
	function MenuFormController ($scope, FormsManager, Breadcrumb) {

		Breadcrumb.setLocation([["Sites et pages", "Change/Website"], ["Menus de navigation", "Change/Website/Menu"]]);
		FormsManager.initResource($scope, 'Change_Website_Menu');

	}

	MenuFormController.$inject = [
		'$scope',
		'RbsChange.FormsManager',
		'RbsChange.Breadcrumb'
	];
	app.controller('Change_Website_Menu_FormController', MenuFormController);


})();