(function () {
	"use strict";

	var app = angular.module('RbsChange');

	/**
	 * Public Profile for group identifier popover (on @)
	 *
	 * @param $scope
	 * @param $routeParams
	 * @param REST
	 * @constructor
	 */
	function PublicProfileController($scope, $routeParams, REST) {
		REST.resource($routeParams.id).then(function (group) {
			$scope.document = group;

			$scope.query = {
				'model': 'Rbs_User_User',
				'join': [
					{
						'model': 'Rbs_User_Group',
						'name': 'jgroup',
						'parentProperty': 'groups'
					}
				],
				'where': {
					'and': [
						{
							'op': 'eq',
							'lexp': {
								'property': 'id',
								'join': 'jgroup'
							},
							'rexp': {
								'value': group.id
							}
						}
					]
				}
			};
		});
	}

	PublicProfileController.$inject = ['$scope', '$routeParams', 'RbsChange.REST'];
	app.controller('Rbs_User_Group_PublicProfileController', PublicProfileController);

	/**
	 * Public Profile for group identifier popover (on @)
	 *
	 * @param $scope
	 * @param $routeParams
	 * @param REST
	 * @param $http
	 * @constructor
	 */
	function GroupUsersController($scope, $routeParams, REST, $http) {
		$scope.data = {
			usersToAdd: []
		};
		$scope.disableAdd = true;

		REST.resource($routeParams.id).then(function (group) {
			$scope.document = group;

			$scope.groupUsersQuery = {
				'model': 'Rbs_User_User',
				'join': [
					{
						'model': 'Rbs_User_Group',
						'name': 'jgroup',
						'parentProperty': 'groups'
					}
				],
				'where': {
					'and': [
						{
							'op': 'eq',
							'lexp': {
								'property': 'id',
								'join': 'jgroup'
							},
							'rexp': {
								'value': group.id
							}
						}
					]
				}
			};

			function reload() {
				$scope.$broadcast('Change:DocumentList:DLGroupUsers:call', {method: 'reload'});
			}

			$scope.groupUsersList = {
				removeFromGroup: function (users) {
					var userIds = [];
					angular.forEach(users, function (user) {
						userIds.push(user.id);
					});
					var url = REST.getBaseUrl('user/removeUsersFromGroup');
					$http.post(url, {userIds: userIds, groupId: $scope.document.id})
						.success(function () {
							reload();
						});
				}
			};

			$scope.$watch('data.usersToAdd', function (usersToAdd) {
				$scope.disableAdd = usersToAdd.length > 0;
			});

			$scope.addUsersFromPicker = function () {
				if ($scope.data.usersToAdd.length > 0) {
					var userIds = [];
					angular.forEach($scope.data.usersToAdd, function (user) {
						userIds.push(user.id);
					});
					var url = REST.getBaseUrl('user/addUsersInGroup');
					$http.post(url, {userIds: userIds, groupId: $scope.document.id})
						.success(function () {
							reload();
							$scope.data.usersToAdd = [];
						});
				}
			};
		});
	}

	GroupUsersController.$inject = ['$scope', '$routeParams', 'RbsChange.REST', '$http'];
	app.controller('Rbs_User_Group_GroupUsersController', GroupUsersController);
})();