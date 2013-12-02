(function ()
{
	"use strict";

	var app = angular.module('RbsChange');

	/**
	 * Controller for applications.
	 *
	 * @param $scope
	 * @param $routeParams
	 * @param REST
	 * @param i18n
	 * @param $http
	 * @param ArrayUtils
	 * @param Breadcrumb
	 * @constructor
	 */
	function ApplicationsController($scope, $routeParams, REST, i18n, $http, ArrayUtils, Breadcrumb)
	{
		REST.resource($routeParams.id).then(function (user){
			$scope.document = user;
			Breadcrumb.setPath([[user.label, user.url()], 'Applications']);
			Breadcrumb.setResource(null);
		});

		$scope.reloadTokens = function (){
			var url = REST.getBaseUrl('user/userTokens/?userId=' + $routeParams.id);
			$http.get(url).success(function (data){
				$scope.tokens = data;
			});
		};

		$scope.reloadTokens();

		$scope.revokeToken = function(token){
			if (confirm(i18n.trans('m.rbs.user.adminjs.confirm_revoke_token | ucf', { 'token': token.token })))
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
	}

	ApplicationsController.$inject = ['$scope', '$routeParams', 'RbsChange.REST', 'RbsChange.i18n', '$http', 'RbsChange.ArrayUtils', 'RbsChange.Breadcrumb'];
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
			Breadcrumb.setPath([[user.label, user.url()], 'Permissions']);
			Breadcrumb.setResource(null);
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
				if (confirm(i18n.trans('m.rbs.user.admin.confirm_remove_permission_rule | ucf', permissionRuleToRemove)))
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
			if (confirm(i18n.trans('m.rbs.user.admin.confirm_remove_all_permission_rules | ucf', { 'user': $scope.document.label})))
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
	 * @param Breadcrumb
	 * @constructor
	 */
	function PublicProfileController($scope, $routeParams, REST, i18n, $http, Breadcrumb)
	{
		Breadcrumb.setLocation([
			[i18n.trans('m.rbs.user.admin.module_name | ucf'), "Rbs/User"]
		]);

		REST.resource($routeParams.id).then(function (user){
			$scope.document = user;
			//Groups
			$scope.query = {
				'model': 'Rbs_User_Group',
				'join': [
					{
						'model': 'Rbs_User_User',
						'name': 'juser',
						'property': 'groups'
					}
				],
				'where': {
					'and': [
						{
							'op': 'eq',
							'lexp': {
								'property': 'id',
								'join': 'juser'
							},
							'rexp': {
								'value': user.id
							}
						}
					]
				}
			};
			//Profiles
			var url = user.META$.links.profiles.href;
			$http.get(url).success(function (profiles){
				$scope.profile = profiles.Rbs_Admin;
			});
		});
	}

	PublicProfileController.$inject = ['$scope', '$routeParams', 'RbsChange.REST', 'RbsChange.i18n', '$http', 'RbsChange.Breadcrumb'];
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
		$scope.$watch('message', function (message){
			REST.resource(message.authorId).then(function(user){
				$scope.document = user;
				var url = user.META$.links.profiles.href;
				$http.get(url).success(function (profiles){
					$scope.profile = profiles.Rbs_Admin;
				});
			});
		});
	}

	PopoverPreviewController.$inject = ['$scope', 'RbsChange.REST', 'RbsChange.i18n', '$http'];
	app.controller('Rbs_User_User_PopoverPreviewController', PopoverPreviewController);
})();