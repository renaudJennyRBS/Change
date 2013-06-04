(function () {

	"use strict";

	var app = angular.module('RbsChange');

	app.run(['RbsChange.Actions', 'RbsChange.Clipboard', function (Actions, Clipboard) {

		Actions.register({
			name        : 'clipboard.clear',
			models      : '*',
			label       : "Effacer",
			description : "Effacer",
			icon        : "icon-remove-circle",
			display     : "icon+label",

			execute : [function () {
				Clipboard.clear();
			}],

			isEnabled : function () {
				return ! Clipboard.isEmpty();
			}
		});

	}]);

	app.config(['$routeProvider', function ($routeProvider)
	{
		$routeProvider
		. when(
			'/clipboard',
			{
				templateUrl : 'Rbs/Admin/clipboard/list.twig',
				reloadOnSearch : false
			})
		;

	}]);

	/**
	 * @name ChangeAdminClipboardController
	 */
	function ChangeAdminClipboardController ($scope, DocumentList, Breadcrumb, Clipboard, MainMenu) {

		var DL = DocumentList.initScope($scope);

		DL.addActions([['clipboard.clear']]);

		Breadcrumb.resetLocation([["Presse-papier", "clipboard"]]);

		DL.documents = Clipboard.values;

		$scope.removeFromSelection = function (doc) {
			Clipboard.remove(doc);
		};

		MainMenu.loadModuleMenu("Rbs_Admin_clipboard");
	}

	ChangeAdminClipboardController.$inject = [
		'$scope',
		'RbsChange.DocumentList',
		'RbsChange.Breadcrumb',
		'RbsChange.Clipboard',
		'RbsChange.MainMenu'
	];
	app.controller('Rbs_Admin_ClipboardController', ChangeAdminClipboardController);


})();