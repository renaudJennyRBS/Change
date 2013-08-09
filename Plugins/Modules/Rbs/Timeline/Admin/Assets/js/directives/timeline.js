(function ($) {

	"use strict";

	var app = angular.module('RbsChange');

	app.directive('timeline', ['$rootScope', '$compile', 'RbsChange.Dialog', 'RbsChange.Utils', 'RbsChange.Actions', 'RbsChange.Breadcrumb', 'RbsChange.Settings', 'RbsChange.Events', 'RbsChange.REST', '$http', 'RbsChange.User', function ($rootScope, $compile, Dialog, Utils, Actions, Breadcrumb, Settings, Events, REST, $http, User) {

		return {
			restrict: 'E',

			templateUrl: 'Rbs/Timeline/js/directives/timeline.twig',

			link : function (scope, element, attributes) {

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
										'value': attributes.docid
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

				reloadMessages();

				//new comment
				scope.newComment = "";
				scope.comment = function (){
					var url = REST.getBaseUrl('resources/Rbs/Timeline/Message/');
					$http.post(url, {
						'contextId': attributes.docid,
						'message': this.newComment,
						'label': ' '
					}).success(function (){
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