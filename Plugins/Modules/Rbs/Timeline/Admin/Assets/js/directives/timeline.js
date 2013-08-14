(function ($) {

	"use strict";

	var app = angular.module('RbsChange');

	app.directive('rbsTimeline', ['$rootScope', '$compile', 'RbsChange.Dialog', 'RbsChange.Utils', 'RbsChange.Actions', 'RbsChange.Breadcrumb', 'RbsChange.Settings', 'RbsChange.Events', 'RbsChange.REST', '$http', 'RbsChange.User', function ($rootScope, $compile, Dialog, Utils, Actions, Breadcrumb, Settings, Events, REST, $http, User) {

		return {
			restrict: 'A',

			templateUrl: 'Rbs/Timeline/js/directives/timeline.twig',

			link : function (scope, elm, attrs) {

				function reloadMessages(){
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
				}

				scope.$watch('document', function (doc, oldDoc){
					if (doc){
						reloadMessages();
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
							reloadMessages();
						});
				};

				//edit and remove
				scope.user = User.get();

				scope.editMessage = function(message){
					message.editMode = true;
				};

				scope.updateMessage = function(message){
					REST.save(message).then(function(){
						reloadMessages();
					});
				};

				scope.removeMessage = function(message){
					REST.delete(message).then(function(){
						reloadMessages();
					});
				};

			}

		};
	}]);

})(window.jQuery);