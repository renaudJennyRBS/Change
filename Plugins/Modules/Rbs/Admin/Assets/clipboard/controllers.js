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
	 * @name Rbs_Admin_ClipboardController
	 */
	function ChangeAdminClipboardController ($scope, Breadcrumb, Clipboard, MainMenu) {
		Breadcrumb.resetLocation([["Presse-papier", "clipboard"]]);

		$scope.clipboardItems = Clipboard.values;

		$scope.removeFromSelection = function (doc) {
			Clipboard.remove(doc);
		};

		$scope.clipboardList = {
			'removeFromClipboard' : function ($docs) {
				angular.forEach($docs, function (doc) {
					Clipboard.remove(doc);
				});
			}
		};

		MainMenu.loadModuleMenu("Rbs_Admin_clipboard");
	}

	ChangeAdminClipboardController.$inject = [
		'$scope',
		'RbsChange.Breadcrumb',
		'RbsChange.Clipboard',
		'RbsChange.MainMenu'
	];
	app.controller('Rbs_Admin_ClipboardController', ChangeAdminClipboardController);


})();