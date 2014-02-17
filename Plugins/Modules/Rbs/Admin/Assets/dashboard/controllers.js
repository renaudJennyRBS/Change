(function ($) {

	"use strict";

	var app = angular.module('RbsChange');

	/**
	 * Controller for Dashboard.
	 *
	 * @param $scope
	 * @param REST
	 * @param Dialog
	 * @param Settings
	 * @param User
	 * @param $location
	 * @constructor
	 */
	function DashboardController($scope, REST, Dialog, Settings, User, $location) {

		var $embedContainer = $('#chgDashboardEmbedContainer');

		//
		// Settings
		//

		var settings;

		function refreshDashboard() {
			settings = angular.copy(Settings.get('dashboard', {}));
			if (settings.tags && settings.tags.length) {
				REST.call(REST.getBaseUrl('admin/tagsInfo/'), {'tags': settings.tags},
					REST.collectionTransformer()).then(function (result) {
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
				{ 'pointedElement': $event.target }
			);
		};

		$scope.saveSettings = function () {
			var dashboard = {
				tags: []
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

		$scope.goToUserProfile = function () {
			$location.path('/Rbs/User/Profile');
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
				{ 'pointedElement': $event.target }
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
				{ 'pointedElement': $event.target }
			);
		};

		$scope.reloadTasks = function () {
			REST.call(REST.getBaseUrl('admin/currentTasks/'), {'column': ['document', 'taskCode', 'status']},
				REST.collectionTransformer()).then(function (result) {
				$scope.tasks = result;
			});
		};

		$scope.reloadTasks();

		$scope.taskList = {

			'resolveTask': function (task) {
				REST.executeTask(task);
			},

			'rejectTask': function (task) {
				REST.executeTask(
					task,
					{
						'reason': window.prompt("Veuillez indiquer le motif du refus :")
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
				{ 'pointedElement': $event.target }
			);
		};

		function getNotificationQuery(status) {
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

		//initiate with 'new' notifications
		$scope.notificationType = 'new';
		$scope.notificationQuery = getNotificationQuery('new');

		function reloadNotificationsQuery() {
			REST.query(getNotificationQuery('new')).then(function (data) {
				$scope.newNotificationCount = data.pagination.count;
			});
		}

		reloadNotificationsQuery();

		$scope.$watch('notificationType', function (type) {
			$scope.notificationQuery = getNotificationQuery(type);
		});

		$scope.notificationList = {
			'readNotification': function (notification) {
				notification.status = 'read';
				notification.disableButton = true;
				REST.save(notification).then(function () {
					reloadNotificationsQuery();
					$scope.$broadcast('Change:DocumentList:DLRbsDashboardNotificationsList:call', { method: 'reload' });
				});
			},
			'deleteNotification': function (notification) {
				notification.status = 'deleted';
				notification.disableButton = true;
				REST.save(notification).then(function () {
					reloadNotificationsQuery();
					$scope.$broadcast('Change:DocumentList:DLRbsDashboardNotificationsList:call', { method: 'reload' });
				});
			},
			getNotificationType: function () {
				return $scope.notificationType;
			}
		};
	}

	DashboardController.$inject = [
		'$scope',
		'RbsChange.REST',
		'RbsChange.Dialog',
		'RbsChange.Settings',
		'RbsChange.User',
		'$location'
	];
	app.controller('Rbs_Admin_DashboardController', DashboardController);

})(window.jQuery);