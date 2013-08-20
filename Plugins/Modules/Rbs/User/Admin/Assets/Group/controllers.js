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
			[i18n.trans('m.rbs.user.admin.js.group-list | ucf'), "Rbs/User/Group"]
		]);

		var DL = DocumentList.initScope($scope, 'Rbs_User_Group');
		DL.viewMode = 'list';
		DL.sort.column = 'modificationDate';
		DL.sort.descending = true;

		// Configure DataTable columns
		DL.columns.push({ id: 'activated', label: i18n.trans('m.rbs.admin.admin.js.activated | ucf'), width: "90px", align: "center", sortable: true });

		MainMenu.loadModuleMenu('Rbs_User');
	}

	ListController.$inject = ['$scope', 'RbsChange.DocumentList', 'RbsChange.Breadcrumb', 'RbsChange.MainMenu', 'RbsChange.i18n'];
	app.controller('Rbs_User_Group_ListController', ListController);

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
			[i18n.trans('m.rbs.user.admin.js.group-list | ucf'), "Rbs/User/Group"]
		]);
		FormsManager.initResource($scope, 'Rbs_User_Group');
	}

	FormController.$inject = ['$scope', 'RbsChange.FormsManager', 'RbsChange.Breadcrumb', 'RbsChange.i18n'];
	app.controller('Rbs_User_Group_FormController', FormController);

	/**
	 * Public Profile for group identifier popover (on @)
	 *
	 * @param $scope
	 * @param $routeParams
	 * @param REST
	 * @param i18n
	 * @param Workspace
	 * @param Breadcrumb
	 * @constructor
	 */
	function PublicProfileController($scope, $routeParams, REST, i18n, Workspace, Breadcrumb)
	{
		Workspace.collapseLeftSidebar();

		Breadcrumb.setLocation([
			[i18n.trans('m.rbs.user.admin.js.module-name | ucf'), "Rbs/User"]
		]);

		$scope.$on('$destroy', function () {
			Workspace.restore();
		});

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

	PublicProfileController.$inject = ['$scope', '$routeParams', 'RbsChange.REST', 'RbsChange.i18n', 'RbsChange.Workspace', 'RbsChange.Breadcrumb'];
	app.controller('Rbs_User_Group_PublicProfileController', PublicProfileController);
})();