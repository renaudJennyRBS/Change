(function ($) {

	"use strict";

	var app = angular.module('RbsChange');

	app.directive('rbsTimeline', ['$rootScope', '$compile', 'RbsChange.Dialog', 'RbsChange.Utils', 'RbsChange.Actions', 'RbsChange.Breadcrumb', 'RbsChange.Settings', 'RbsChange.Events', 'RbsChange.REST', '$http', 'RbsChange.User', '$timeout', function ($rootScope, $compile, Dialog, Utils, Actions, Breadcrumb, Settings, Events, REST, $http, User, $timeout) {

		return {
			restrict: 'A',

			templateUrl: 'Rbs/Timeline/js/timeline.twig',

			link : function (scope, elm, attrs) {

				scope.reloadMessages = function reloadMessages(){
					console.log('reload !');
					scope.timelineMessages = [];
					REST.query({
						'model': 'Rbs_Timeline_Message',
						'where': {
							'and': [
								{
									'op': 'eq',
									'lexp': {
										'property': 'contextId'
									},
									'rexp': {
										'value': scope.document.id
									}
								}
							]
						},
						'order': [
							{
								'property': 'creationDate',
								'order': 'desc'
							}
						]
					}).then(function (result){
							scope.timelineMessages = result.resources;
						});
				};

				scope.$watch('document', function (doc, oldDoc){
					if (doc){
						scope.reloadMessages();
					}
				});

				//new message
				scope.newMessage = "";
				scope.sendMessage = function (){
					var url = REST.getBaseUrl('resources/Rbs/Timeline/Message/');
					$http.post(url, {
						'contextId': scope.document.id,
						'message': scope.newMessage,
						'label': ' '
					}).success(function (){
							scope.newMessage = "";
							scope.reloadMessages();
						});
				};

			}

		};
	}]);

	app.directive('rbsTimelineMessage', ['$rootScope', '$compile', 'RbsChange.Dialog', 'RbsChange.Utils', 'RbsChange.Actions', 'RbsChange.Breadcrumb', 'RbsChange.Settings', 'RbsChange.Events', 'RbsChange.REST', '$http', 'RbsChange.User', '$timeout', function ($rootScope, $compile, Dialog, Utils, Actions, Breadcrumb, Settings, Events, REST, $http, User, $timeout) {

		return {
			restrict: 'E',

			templateUrl: 'Rbs/Timeline/js/timeline-message.twig',
			scope: {
				message: '=',
				//FIXME reload messages doesn't work!
				reloadMessages: '&'
			},

			link : function (scope, elm, attrs) {
				var contentEl = elm.find('.message-content').first();
				scope.$watch('message', function (message){
					if (message){
						$compile(message.message.h)(scope, function (cloneElm){
							contentEl.append(cloneElm);
						})
					}
				}, true);

				//edit and remove
				scope.user = User.get();

				scope.editMessage = function(message){
					message.editMode = true;
					elm.find('.message-content').first().hide();
				};

				scope.updateMessage = function(message){
					REST.save(message).then(function(){
						scope.reloadMessages();
					});
				};

				scope.removeMessage = function(message){
					REST.delete(message).then(function(){
						scope.reloadMessages();
					});
				};
			}
		}
	}]);

})(window.jQuery);