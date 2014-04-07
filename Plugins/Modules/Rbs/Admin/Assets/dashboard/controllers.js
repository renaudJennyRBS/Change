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