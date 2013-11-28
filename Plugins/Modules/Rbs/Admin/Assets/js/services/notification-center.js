(function ()
{

	var app = angular.module('RbsChange');

	app.provider('RbsChange.NotificationCenter', function RbsChangeNotificationCenterProvider()
	{

		this.$get = ['$rootScope', 'RbsChange.ArrayUtils', function ($rootScope, ArrayUtils)
		{

			var notificationCenter = {

				notifications: [],

				push: function (notification)
				{
					notification.level = notification.level || "info";

					if (notification.body !== null && notification.body !== undefined && angular.isArray(notification.body))
					{
						notification.body = this.constructBodyHtml(notification.body);
					}

					if (notification.id === null || notification.id === undefined)
					{
						notification.id = 'auto-' + (new Date()).getTime();
					}

					var existingNotificationIndex = this.getIndexOfNotificationById(notification.id);

					if (existingNotificationIndex !== null)
					{
						this.remove(existingNotificationIndex);
					}

					this.notifications.push(notification);
				},

				info: function (title, body, id, timeout)
				{
					this.push({
						'title': title,
						'body': body,
						'level': 'info',
						'id': id,
						'timeout' : timeout
					});
				},

				warning: function (title, body, id, context)
				{
					this.push({
						'title': title,
						'body': body,
						'level': 'warning',
						'context': context,
						'id': id
					});
				},

				error: function (title, body, id, context)
				{
					this.push({
						'title': title,
						'body': body,
						'level': 'error',
						'context': context,
						'id': id
					});
				},

				remove: function (index)
				{
					ArrayUtils.remove(this.notifications, index, index);
				},

				clear: function ()
				{
					ArrayUtils.clear(this.notifications);
				},

				constructBodyHtml : function (array)
				{
					return '<ul><li>' + array.join('</li><li>') + '</li></ul>'
				},

				getIndexOfNotificationById : function (id)
				{
					for (var i = 0; i < this.notifications.length; i++)
					{
						if (this.notifications[i].id === id)
						{
							return i;
						}
					}
					return null;
				}

			};

			/**
			 * When the route changes, we need to clean up any cascading process.
			 */
			$rootScope.$on('$routeChangeSuccess', function ()
			{
				notificationCenter.clear();
			});

			return notificationCenter;

		}];

	});

})();