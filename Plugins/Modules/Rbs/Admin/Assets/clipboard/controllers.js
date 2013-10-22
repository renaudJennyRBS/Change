(function () {

	"use strict";

	var app = angular.module('RbsChange');

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
	function ChangeAdminClipboardController ($scope, Breadcrumb, Clipboard, MainMenu, i18n) {
		Breadcrumb.resetLocation([[i18n.trans('m.rbs.admin.admin.js.clipboard', ['ucf']), "clipboard"]]);

		$scope.clipboardItems = Clipboard.values;

		$scope.removeFromSelection = function (doc) {
			Clipboard.remove(doc);
		};

		$scope.clipboardList = {
			'removeFromClipboard' : function ($docs) {
				angular.forEach($docs, function (doc) {
					Clipboard.remove(doc);
				});
			},
			'clearClipboard' : function () {
				Clipboard.clear();
			}
		};

		MainMenu.loadModuleMenu("Rbs_Admin_clipboard");
	}

	ChangeAdminClipboardController.$inject = [
		'$scope',
		'RbsChange.Breadcrumb',
		'RbsChange.Clipboard',
		'RbsChange.MainMenu',
		'RbsChange.i18n'
	];
	app.controller('Rbs_Admin_ClipboardController', ChangeAdminClipboardController);


})();