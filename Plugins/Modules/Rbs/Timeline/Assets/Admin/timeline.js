(function ($) {

	"use strict";

	var app = angular.module('RbsChange');

	app.directive('rbsTimeline',
		['$rootScope', '$compile', 'RbsChange.Dialog', 'RbsChange.Utils', 'RbsChange.Actions',
			'RbsChange.Settings', 'RbsChange.Events', 'RbsChange.REST', '$http',
			function ($rootScope, $compile, Dialog, Utils, Actions, Settings, Events, REST, $http) {
				return {
					restrict: 'A',
					templateUrl: 'Rbs/Timeline/directive-timeline.twig',
					require: 'rbsTimeline',

					controller: ['$scope', function ($scope) {
						this.reload = function () {
							$scope.timelineMessages = [];
							REST.query({
								'model': 'Rbs_Timeline_Message',
								'where': {
									'and': [
										{
											'op': 'eq',
											'lexp': { 'property': 'contextId' },
											'rexp': { 'value': $scope.document.id }
										}
									]
								},
								'order': [
									{
										'property': 'creationDate',
										'order': 'desc'
									}
								]
							}).then(function (result) {
								$scope.timelineMessages = result.resources;
							});
						};
					}],

					link: function (scope, elm, attrs, rbsTimeline) {
						scope.$watch('document', function (doc) {
							if (doc) {
								rbsTimeline.reload();
							}
						});

						scope.data = {};
						scope.data.newMessage = { e: 'Markdown', h: null, t: ' ' };

						scope.canSendMessage = function canSendMessage() {
							return scope.data.newMessage.t.length && scope.data.newMessage.t != ' ';
						};

						scope.sendMessage = function sendMessage() {
							var url = REST.getBaseUrl('resources/Rbs/Timeline/Message/');
							$http.post(url, {
								'contextId': scope.document.id,
								'message': scope.data.newMessage.t,
								'label': ' '
							}).success(function () {
								scope.data.newMessage = { e: 'Markdown', h: null, t: ' ' };
								rbsTimeline.reload();
							});
						};
					}
				};
			}]);

	app.directive('rbsTimelineMessage',
		['$rootScope', '$compile', 'RbsChange.Dialog', 'RbsChange.Utils', 'RbsChange.Actions',
			'RbsChange.Settings', 'RbsChange.Events', 'RbsChange.REST', '$http', 'RbsChange.User',
			function ($rootScope, $compile, Dialog, Utils, Actions, Settings, Events, REST, $http, User) {
				return {
					restrict: 'E',

					templateUrl: 'Rbs/Timeline/directive-timeline-message.twig',
					scope: {
						message: '='
					},
					require: '^rbsTimeline',
					replace: true,

					link: function (scope, elm, attrs, rbsTimeline) {
						var contentEl = elm.find('.message-content').first();
						scope.$watch('message', function (message) {
							if (message && contentEl.children().length === 0) {
								$compile(message.message.h)(scope, function (cloneElm) {
									contentEl.append(cloneElm);
								});
							}
						});

						scope.user = User.get();

						// Edit and remove.
						scope.editMessage = function editMessage(message) {
							message.editMode = true;
							elm.find('.message-content').first().hide();
						};

						scope.cancelMessageEdition = function cancelMessageEdition(message) {
							message.editMode = false;
							elm.find('.message-content').first().show();
						};

						scope.updateMessage = function updateMessage(message) {
							REST.save(message).then(function () {
								rbsTimeline.reload();
							});
						};

						scope.removeMessage = function removeMessage(message) {
							REST['delete'](message).then(function () {
								rbsTimeline.reload();
							});
						};
					}
				};
			}]);

	app.controller('RbsChangeTimelineController', ['RbsChange.REST', '$scope', '$filter', '$routeParams',
		function (REST, $scope, $filter, $routeParams) {
			$scope.$watch('model', function (model) {
				if (model) {
					REST.resource(model, $routeParams.id, $routeParams.LCID).then(function (doc) {
						$scope.document = doc;
					});
				}
			});
		}]);

})(window.jQuery);