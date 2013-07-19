(function ()
{
	"use strict";

	var app = angular.module('RbsChange');

	/**
	 * Controller for list.
	 *
	 * @param $scope
	 * @param DocumentList
	 * @param Breadcrumb
	 * @param MainMenu
	 * @constructor
	 */
	function ListController($scope, DocumentList, Breadcrumb, MainMenu, i18n)
	{
		Breadcrumb.resetLocation([
			[i18n.trans('m.rbs.user.admin.js.module-name | ucf'), "Rbs/User/User"]
		]);

		var DL = DocumentList.initScope($scope, 'Rbs_User_User');
		DL.viewMode = 'list';
		DL.sort.column = 'modificationDate';
		DL.sort.descending = true;

		// Configure DataTable columns
		DL.columns.push({ id: 'activated', label: i18n.trans('m.rbs.admin.admin.js.activated | ucf'), width: "90px", align: "center", sortable: true });

		MainMenu.loadModuleMenu('Rbs_User');
	}

	ListController.$inject = ['$scope', 'RbsChange.DocumentList', 'RbsChange.Breadcrumb', 'RbsChange.MainMenu', 'RbsChange.i18n'];
	app.controller('Rbs_User_User_ListController', ListController);

	/**
	 * Controller for form.
	 *
	 * @param $scope
	 * @param Breadcrumb
	 * @param FormsManager
	 * @param i18n
	 * @constructor
	 */
	function FormController($scope, FormsManager, Breadcrumb, i18n)
	{
		Breadcrumb.resetLocation([
			[i18n.trans('m.rbs.user.admin.js.module-name | ucf'), "Rbs/User/User"]
		]);
		FormsManager.initResource($scope, 'Rbs_User_User');
	}

	FormController.$inject = ['$scope', 'RbsChange.FormsManager', 'RbsChange.Breadcrumb', 'RbsChange.i18n'];
	app.controller('Rbs_User_User_FormController', FormController);

	function TokensController($scope, $routeParams, $location, REST, i18n, $http, ArrayUtils)
	{
		$scope.userId = $routeParams.id;
		var url = REST.getBaseUrl('admin/userTokens/?userId=' + $routeParams.id);
		$http.get(url).success(function (data){
				var tokens = data.properties;
				angular.forEach(tokens, function (token){
					token.creation_date = moment(token.creation_date.date).unix() * 1000;
					token.validity_date = moment(token.validity_date.date).unix() * 1000;
				})
				$scope.tokens = data.properties;
			}
		);

		$scope.revokeToken = function(token){
			if (confirm(i18n.trans('m.rbs.user.admin.js.confirm-revoke-token | ucf', { 'token': token.token })))
			{
				var url = REST.getBaseUrl('admin/revokeToken/');
				$http.post(url, { 'token': token.token }).success(function (){
						console.log(token);
						ArrayUtils.removeValue($scope.tokens, token);
					}
				);
			}
		}

		//sort
		$scope.predicate = 'validity_date';
		$scope.reverse = false;
		$scope.isSortedOn = function (column) { return column == $scope.predicate; };
	}

	TokensController.$inject = ['$scope', '$routeParams', '$location', 'RbsChange.REST', 'RbsChange.i18n', '$http', 'RbsChange.ArrayUtils'];
	app.controller('Rbs_User_User_TokensController', TokensController);
})();