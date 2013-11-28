(function ()
{
	"use strict";

	var app = angular.module('RbsChange');

	/**
	 * Public Profile for group identifier popover (on @)
	 *
	 * @param $scope
	 * @param $routeParams
	 * @param REST
	 * @param i18n
	 * @param Breadcrumb
	 * @constructor
	 */
	function PublicProfileController($scope, $routeParams, REST, i18n, Breadcrumb)
	{
		Breadcrumb.setLocation([
			[i18n.trans('m.rbs.user.admin.module_name | ucf'), "Rbs/User"]
		]);

		REST.resource($routeParams.id).then(function (group){
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

	PublicProfileController.$inject = ['$scope', '$routeParams', 'RbsChange.REST', 'RbsChange.i18n', 'RbsChange.Breadcrumb'];
	app.controller('Rbs_User_Group_PublicProfileController', PublicProfileController);
})();