(function ()
{
	"use strict";

	var app = angular.module('RbsChange');

	/**
	 * Controller for list.
	 *
	 * @param $scope
	 * @param Breadcrumb
	 * @param MainMenu
	 * @param i18n
	 * @constructor
	 */
	function ListController($scope, Breadcrumb, MainMenu, i18n)
	{
		Breadcrumb.resetLocation([
			[i18n.trans('m.rbs.user.admin.js.module-name | ucf'), "Rbs/User/User"]
		]);

		MainMenu.loadModuleMenu('Rbs_User');
	}

	ListController.$inject = ['$scope', 'RbsChange.Breadcrumb', 'RbsChange.MainMenu', 'RbsChange.i18n'];
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

	/**
	 * Controller for applications.
	 *
	 * @param $scope
	 * @param $routeParams
	 * @param REST
	 * @param i18n
	 * @param $http
	 * @param ArrayUtils
	 * @param MainMenu
	 * @param Breadcrumb
	 * @constructor
	 */
	function ApplicationsController($scope, $routeParams, REST, i18n, $http, ArrayUtils, MainMenu, Breadcrumb)
	{
		REST.resource($routeParams.id).then(function (user){
			$scope.document = user;
		});

		$scope.reloadTokens = function (){
			var url = REST.getBaseUrl('user/userTokens/?userId=' + $routeParams.id);
			$http.get(url).success(function (data){
				$scope.tokens = data;
			});
		};

		$scope.reloadTokens();

		$scope.tokenList = {
			'revokeToken': function(token){
				if (confirm(i18n.trans('m.rbs.user.admin.js.confirm-revoke-token | ucf', { 'token': token.token })))
				{
					var url = REST.getBaseUrl('user/revokeToken/');
					$http.post(url, { 'token': token.token }).success(function (){
							ArrayUtils.removeValue($scope.tokens, token);
						}
					);
				}
			}
		};

		MainMenu.loadModuleMenu('Rbs_User');
		Breadcrumb.resetLocation([
			[i18n.trans('m.rbs.user.admin.js.module-name | ucf'), "Rbs/User/User"]
		]);
	}

	ApplicationsController.$inject = ['$scope', '$routeParams', 'RbsChange.REST', 'RbsChange.i18n', '$http', 'RbsChange.ArrayUtils', 'RbsChange.MainMenu', 'RbsChange.Breadcrumb'];
	app.controller('Rbs_User_User_ApplicationsController', ApplicationsController);

	/**
	 * Controller for permission.
	 *
	 * @param $scope
	 * @param $routeParams
	 * @param $location
	 * @param REST
	 * @param i18n
	 * @param $http
	 * @param ArrayUtils
	 * @constructor
	 */
	function PermissionController($scope, $routeParams, $location, REST, i18n, $http, ArrayUtils, MainMenu, Breadcrumb)
	{
		REST.resource($routeParams.id).then(function (user){
			$scope.document = user;
		});

		$scope.reloadPermissions = function (){
			var url = REST.getBaseUrl('user/permissionRules/?accessorId=' + $routeParams.id);
			$http.get(url).success(function (data){
					$scope.permissionRules = data;
				}
			);
		};

		$scope.reloadPermissions();

		$scope.addPermissionRules = function (){
			if ($scope.newPermissionRules.roles && $scope.newPermissionRules.privileges && $scope.newPermissionRules.resources)
			{
				var url = REST.getBaseUrl('user/addPermissionRules/');
				$http.post(url, { 'permissionRules': $scope.newPermissionRules }).success(function(){
					var url = REST.getBaseUrl('user/permissionRules/?accessorId=' + $routeParams.id);
					$http.get(url).success(function (data){
							$scope.permissionRules = data;
						}
					);
				});
			}
		};

		$scope.permissionList = {
			'removePermissionRule': function (permissionRuleToRemove){
				if (permissionRuleToRemove.rule_id)
				{
					if (confirm(i18n.trans('m.rbs.user.admin.js.confirm-remove-permission-rule | ucf', permissionRuleToRemove)))
					{
						var url = REST.getBaseUrl('user/removePermissionRule/');
						$http.post(url, { 'rule_id': permissionRuleToRemove.rule_id }).success(function (){
								ArrayUtils.removeValue($scope.permissionRules, permissionRuleToRemove);
							}
						);
					}
				}
			}
		};

		//TODO will be replaced by the action (in List actions) coded below
		$scope.removeAllPermissionRules = function () {
			if (confirm(i18n.trans('m.rbs.user.admin.js.confirm-remove-all-permission-rules | ucf', { 'user': $scope.document.label})))
			{
				var url = REST.getBaseUrl('user/removePermissionRule/');
				angular.forEach($scope.permissionRules, function (permissionRule){
					$http.post(url, { 'rule_id': permissionRule.rule_id }).success(function (){
							ArrayUtils.removeValue($scope.permissionRules, permissionRule);
						}
					);
				});
			}
		};

		$scope.newPermissionRules = { 'accessor_id': $routeParams.id, 'roles': ['*'], 'privileges': ['*'], 'resources': ['0'] };

		//get the permission roles collection
		$scope.permissionRoles = {};
		REST.action('collectionItems', { code: 'Rbs_Generic_Collection_PermissionRoles' }).then(function (data) {
			$scope.permissionRoles = data.items;
			delete($scope.permissionRoles['*']);
		});
		$scope.showRoles = false;

		//get the permission privileges collection
		$scope.permissionPrivileges = {};
		REST.action('collectionItems', { code: 'Rbs_Generic_Collection_PermissionPrivileges' }).then(function (data) {
			$scope.permissionPrivileges = data.items;
			delete($scope.permissionPrivileges['*']);
		});
		$scope.showPrivileges = false;

		MainMenu.loadModuleMenu('Rbs_User');
		Breadcrumb.resetLocation([
			[i18n.trans('m.rbs.user.admin.js.module-name | ucf'), "Rbs/User/User"]
		]);
	}

	PermissionController.$inject = ['$scope', '$routeParams', '$location', 'RbsChange.REST', 'RbsChange.i18n', '$http', 'RbsChange.ArrayUtils', 'RbsChange.MainMenu', 'RbsChange.Breadcrumb'];
	app.controller('Rbs_User_User_PermissionController', PermissionController);

	/**
	 * List actions.
	 * TODO keep this code for a future usage
	 */
	app.config(['$provide', function ($provide) {
		$provide.decorator('RbsChange.Actions', ['$delegate', 'RbsChange.REST', '$http', 'RbsChange.i18n', '$q', function (Actions, REST, $http, i18n, $q) {
			Actions.register({
				name: 'Rbs_User_RemoveAllPermissions',
				models: '',
				label: i18n.trans('m.rbs.user.admin.js.remove-all-permission-rules'),
				selection: 0,
				execute: ['$scope', function ($scope) {
					if (confirm(i18n.trans('m.rbs.user.admin.js.confirm-remove-all-permission-rules | ucf', { 'user': $scope.document.label})))
					{
						var url = REST.getBaseUrl('user/removePermissionRule/');
						var promises = [];
						angular.forEach($scope.permissionRules, function (permissionRule){
							promises.push($http.post(url, { 'rule_id': permissionRule.rule_id }));
						});
						$q.all(promises).then(function (){
							$scope.reloadPermissions();
						});
					}
				}]
			});
			return Actions;
		}]);
	}]);
})();