/**
 * User: fredericbonjour
 * Date: 03/06/13
 * Time: 16:31
 * To change this template use File | Settings | File Templates.
 */

(function ()
{
	"use strict";

	var app = angular.module('RbsChange');

	/**
	 * Controller for list.
	 *
	 * @param $scope
	 * @param Workspace
	 * @param Breadcrumb
	 * @param MainMenu
	 * @param i18n
	 * @param REST
	 * @constructor
	 */
	function DashboardController($scope, Workspace, Breadcrumb, MainMenu, i18n, REST)
	{
		Breadcrumb.resetLocation();

		Workspace.collapseLeftSidebar();
		Workspace.hideBreadcrumb();
		MainMenu.hide();

		$scope.indicators = [
			{
				"label"  : "Commandes passées ce mois",
				"message": "Ne vous inquiétez pas, ça viendra ! <i class='icon-smile'></i>",
				"style"  : "red",
				"value"  : 0,
				"link"   : ""
			},
			{
				"label"  : "Visites uniques aujourd'hui",
				"message": "C'est votre meilleur nombre de visites, bravo !",
				"style"  : "blue",
				"value"  : 348,
				"link"   : ""
			},
			{
				"label"  : "Commentaires sur les articles du blog",
				"message": "Votre site semble vivant, continuez !",
				"style"  : "green",
				"value"  : 15122,
				"link"   : ""
			}
		];

		var taskQuery = {
			'model' : 'Rbs_Workflow_Task',
			'where': {
				'and': [
					/*{
						"op" : "eq",
						"lexp" : {
							"property" : "role"
						},
						"rexp" : {
							"value": "Creator"
						}
					},*/
					{
						"op" : "eq",
						"lexp" : {
							"property" : "status"
						},
						"rexp" : {
							"value": "EN"
						}
					}
				]
			}
		};


		$scope.reloadTasks = function () {
			REST.query(taskQuery, {'column':['document','taskCode','status']}).then(function (tasks) {
				$scope.tasks = tasks;
			});
		};

		$scope.reloadTasks();


		$scope.taskList = {

			'resolveTask' : function (task) {
				REST.executeTask(task);
			},

			'rejectTask' : function (task) {
				REST.executeTask(
					task,
					{
						'reason' : window.prompt("Veuillez indiquer le motif du refus :")
					}
				);
			}

		};

		$scope.$on('$destroy', function () {
			Workspace.restore();
			MainMenu.show();
		});
	}

	DashboardController.$inject = [
		'$scope',
		'RbsChange.Workspace',
		'RbsChange.Breadcrumb',
		'RbsChange.MainMenu',
		'RbsChange.i18n',
		'RbsChange.REST'
	];
	app.controller('Rbs_Admin_DashboardController', DashboardController);

})();