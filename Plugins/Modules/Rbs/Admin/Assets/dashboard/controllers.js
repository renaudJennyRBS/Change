(function ($) {

	"use strict";

	var app = angular.module('RbsChange');

	/**
	 * Controller for Dashboard.
	 *
	 * @param $scope
	 * @param Workspace
	 * @param Breadcrumb
	 * @param MainMenu
	 * @param i18n
	 * @param REST
	 * @param Dialog
	 * @param Settings
	 * @param UrlManager
	 * @param User
	 * @constructor
	 */
	function DashboardController($scope, Workspace, Breadcrumb, MainMenu, i18n, REST, Dialog, Settings, UrlManager, User)
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


		var $embedContainer = $('#chgDashboardEmbedContainer');

		//
		// Settings
		//

		var settings;

		function refreshDashboard() {
			settings = angular.copy(Settings.get('dashboard', {}));
			if (settings.tags && settings.tags.length) {
				REST.call(REST.getBaseUrl('admin/tagsInfo/'), {'tags':settings.tags}, REST.collectionTransformer()).then(function (result) {
					settings.tags = result;
					$scope.dashboardSettings = settings;
				});
			} else {
				$scope.dashboardSettings = settings;
			}
		}
		refreshDashboard();

		$scope.showDashboardSettings = function ($event) {
			Dialog.embed(
				$embedContainer,
				'Rbs/Admin/dashboard/settings.twig',
				$scope,
				{ 'pointedElement' : $event.target }
			);
		};

		$scope.saveSettings = function () {
			var dashboard = {
				tags : []
			};
			angular.forEach($scope.dashboardSettings.tags, function (tag) {
				if (angular.isObject(tag) && tag.id) {
					dashboard.tags.push(tag.id);
				}
			});
			Settings.set('dashboard', dashboard, true).then(function () {
				Dialog.closeEmbedded();
				refreshDashboard();
			});
		};


		//
		// Tags
		//

		$scope.showTaggedDocuments = function (tag, $event) {
			$scope.selectedTag = tag;
			$scope.taggedDocumentsUrl = REST.getResourceUrl(tag) + '/documents/';
			Dialog.embed(
				$embedContainer,
				'Rbs/Admin/dashboard/tags.twig',
				$scope,
				{ 'pointedElement' : $event.target }
			);
		};


		//
		// Tasks
		//

		$scope.showTasks = function ($event) {
			Dialog.embed(
				$embedContainer,
				'Rbs/Admin/dashboard/tasks.twig',
				$scope,
				{ 'pointedElement' : $event.target }
			);
		};

		$scope.reloadTasks = function () {
			REST.call(REST.getBaseUrl('admin/currentTasks/'), {'column':['document','taskCode','status']}, REST.collectionTransformer()).then(function (result) {
				$scope.tasks = result;
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

		//
		// Notifications
		//
		$scope.showNotifications = function ($event) {
			Dialog.embed(
				$embedContainer,
				'Rbs/Admin/dashboard/notifications.twig',
				$scope,
				{ 'pointedElement' : $event.target }
			);
		};

		function getNotificationQuery(status)
		{
			return {
				'model': 'Rbs_Notification_Notification',
				'where': {
					'and': [
						{
							'op': 'eq',
							'lexp': {
								'property': 'userId'
							},
							'rexp': {
								'value': User.get().id
							}
						},
						{
							'op': 'eq',
							'lexp': {
								'property': 'status'
							},
							'rexp': {
								'value': status
							}
						}
					]
				}
			};
		}

		$scope.notificationType = 'new';

		function reloadNotificationsQuery()
		{
			REST.query(getNotificationQuery('new')).then(function (data){
				$scope.newNotificationCount = data.pagination.count;
			});
		}
		reloadNotificationsQuery();

		$scope.$watch('notificationType', function (){
			$scope.notificationsQuery = getNotificationQuery($scope.notificationType);
		});

		$scope.notificationList = {
			'readNotification': function (notification) {
				notification.status = 'read';
				REST.save(notification);
				reloadNotificationsQuery();
			},
			'deleteNotification': function (notification) {
				notification.status = 'deleted';
				REST.save(notification);
				reloadNotificationsQuery();
			},
			getNotificationType: function() {
				return $scope.notificationType;
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
		'RbsChange.REST',
		'RbsChange.Dialog',
		'RbsChange.Settings',
		'RbsChange.UrlManager',
		'RbsChange.User'
	];
	app.controller('Rbs_Admin_DashboardController', DashboardController);

})(window.jQuery);