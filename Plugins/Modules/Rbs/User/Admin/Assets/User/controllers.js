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
			[i18n.trans('m.rbs.user.admin.js.module-name | ucf'), "Rbs/User"],
			[i18n.trans('m.rbs.user.admin.js.user-list | ucf'), "Rbs/User/User/"]
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
			[i18n.trans('m.rbs.user.admin.js.module-name | ucf'), "Rbs/User"],
			[i18n.trans('m.rbs.user.admin.js.user-list | ucf'), "Rbs/User/User/"]
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

		$scope.revokeToken = function(token){
			if (confirm(i18n.trans('m.rbs.user.admin.js.confirm-revoke-token | ucf', { 'token': token.token })))
			{
				var url = REST.getBaseUrl('user/revokeToken/');
				$http.post(url, { 'token': token.token }).success(function (){
						ArrayUtils.removeValue($scope.tokens, token);
					}
				);
			}
		};

		$scope.displayToken = function(token){
			$scope.tokenToDisplay = token.token;
		};

		//sort
		$scope.predicate = 'application';
		$scope.reverse = false;
		$scope.isSortedOn = function (column) { return column == $scope.predicate; };

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
	 * @param MainMenu
	 * @param Breadcrumb
	 * @param $q
	 * @constructor
	 */
	function PermissionController($scope, $routeParams, $location, REST, i18n, $http, ArrayUtils, MainMenu, Breadcrumb, $q)
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

		$scope.removePermissionRule = function (permissionRuleToRemove){
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
		};

		$scope.removeAllPermissionRules = function () {
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

		//sort
		$scope.predicate = 'role';
		$scope.reverse = false;
		$scope.isSortedOn = function (column) { return column == $scope.predicate; };

		MainMenu.loadModuleMenu('Rbs_User');
		Breadcrumb.resetLocation([
			[i18n.trans('m.rbs.user.admin.js.module-name | ucf'), "Rbs/User/User"]
		]);
	}

	PermissionController.$inject = ['$scope', '$routeParams', '$location', 'RbsChange.REST', 'RbsChange.i18n', '$http', 'RbsChange.ArrayUtils', 'RbsChange.MainMenu', 'RbsChange.Breadcrumb', '$q'];
	app.controller('Rbs_User_User_PermissionController', PermissionController);

	/**
	 * Public Profile for user identifier popover (on @)
	 *
	 * @param $scope
	 * @param $routeParams
	 * @param REST
	 * @param i18n
	 * @param $http
	 * @constructor
	 */
	function PublicProfileController($scope, $routeParams, REST, i18n, $http)
	{
		Workspace.collapseLeftSidebar();

		Breadcrumb.setLocation([
			[i18n.trans('m.rbs.user.admin.js.module-name | ucf'), "Rbs/User"]
		]);

		$scope.$on('$destroy', function () {
			Workspace.restore();
		});

		REST.resource($routeParams.id).then(function (user){
			$scope.document = user;
			var url = user.META$.links.profiles.href;
			$http.get(url).success(function (profiles){
				$scope.profile = profiles.Rbs_Admin;
			});
		});
	}

	PublicProfileController.$inject = ['$scope', '$routeParams', 'RbsChange.REST', 'RbsChange.i18n', '$http'];
	app.controller('Rbs_User_User_PublicProfileController', PublicProfileController);

	/**
	 * Popover preview
	 *
	 * @param $scope
	 * @param REST
	 * @param i18n
	 * @param $http
	 * @constructor
	 */
	function PopoverPreviewController($scope, REST, i18n, $http)
	{
		/*
		REST.resource($routeParams.id).then(function (user){
			$scope.document = user;
			var url = user.META$.links.profiles.href;
			$http.get(url).success(function (profiles){
				$scope.profile = profiles.Rbs_Admin;
			});
		});
		*/
	}

	PopoverPreviewController.$inject = ['$scope', 'RbsChange.REST', 'RbsChange.i18n', '$http'];
	app.controller('Rbs_User_User_PopoverPreviewController', PopoverPreviewController);
})();