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
						scope.data.newMessage = "";
						scope.sendMessage = function () {
							var url = REST.getBaseUrl('resources/Rbs/Timeline/Message/');
							$http.post(url, {
								'contextId': scope.document.id,
								'message': scope.data.newMessage,
								'label': ' '
							}).success(function () {
								scope.data.newMessage = "";
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

						//edit and remove
						scope.user = User.get();

						scope.editMessage = function (message) {
							message.editMode = true;
							elm.find('.message-content').first().hide();
						};

						scope.updateMessage = function (message) {
							REST.save(message).then(function () {
								rbsTimeline.reload();
							});
						};

						scope.removeMessage = function (message) {
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