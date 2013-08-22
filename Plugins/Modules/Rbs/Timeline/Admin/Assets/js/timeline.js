(function ($) {

	"use strict";

	var app = angular.module('RbsChange');

	app.directive('rbsTimeline', ['$rootScope', '$compile', 'RbsChange.Dialog', 'RbsChange.Utils', 'RbsChange.Actions', 'RbsChange.Breadcrumb', 'RbsChange.Settings', 'RbsChange.Events', 'RbsChange.REST', '$http', 'RbsChange.User', '$timeout', function ($rootScope, $compile, Dialog, Utils, Actions, Breadcrumb, Settings, Events, REST, $http, User, $timeout) {

		return {
			restrict: 'A',

			templateUrl: 'Rbs/Timeline/js/timeline.twig',
			require: 'rbsTimeline',
			controller: ['$scope', function ($scope){
				this.reload = function (){
					$scope.timelineMessages = [];
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
										'value': $scope.document.id
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
							$scope.timelineMessages = result.resources;
						});
				}
			}],

			link : function (scope, elm, attrs, rbsTimeline) {

				scope.$watch('document', function (doc, oldDoc){
					if (doc){
						rbsTimeline.reload();
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
							rbsTimeline.reload();
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
				message: '='
			},
			require: '^rbsTimeline',

			link : function (scope, elm, attrs, rbsTimeline) {
				var contentEl = elm.find('.message-content').first();
				scope.$watch('message', function (message){
					if (message && contentEl.children().length === 0){
						$compile(message.message.h)(scope, function (cloneElm){
							contentEl.append(cloneElm);
						})
					}
				});

				//edit and remove
				scope.user = User.get();

				scope.editMessage = function(message){
					message.editMode = true;
					elm.find('.message-content').first().hide();
				};

				scope.updateMessage = function(message){
					REST.save(message).then(function(){
						rbsTimeline.reload();
					});
				};

				scope.removeMessage = function(message){
					REST.delete(message).then(function(){
						rbsTimeline.reload();
					});
				};
			}
		}
	}]);

})(window.jQuery);