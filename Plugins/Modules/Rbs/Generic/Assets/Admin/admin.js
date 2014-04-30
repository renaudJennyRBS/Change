(function () {
	"use strict";

	var app = angular.module('RbsChange');

	function RbsGenericInitializeWebsiteCtrl ($scope, $http, REST, i18n, NotificationCenter, ErrorFormatter)
	{
		$scope.data = {websiteId: null, sidebarTemplateId: null, noSidebarTemplateId: null, LCID: null, userAccountTopicId: null};
		$scope.onInitialization = false;

		$scope.initializeWebsiteStructure = function () {
			$scope.onInitialization = true;
			$http.post(REST.getBaseUrl('commands/rbs_generic/initialize-website'), {
				websiteId: $scope.data.websiteId,
				sidebarTemplateId: $scope.data.sidebarTemplateId,
				noSidebarTemplateId: $scope.data.noSidebarTemplateId,
				LCID: $scope.data.LCID,
				userAccountTopicId: $scope.data.userAccountTopicId
			}).success(function (){
				$scope.onInitialization = false;
				$scope.alreadyInitialized = true;
				NotificationCenter.info(null, i18n.trans('m.rbs.generic.admin.initialize_website_success | ucf'));
			}).error(function (error){
				console.error(error);
				$scope.onInitialization = false;
				NotificationCenter.error(i18n.trans('m.rbs.generic.admin.initialize_website_error | ucf'),
					ErrorFormatter.format(error));
			});
		};

		function checkAlreadyInitialized(websiteId) {
			$http.post(REST.getBaseUrl('Rbs/Generic/DocumentCodeContextExist'), {
				context: 'Rbs Generic Website Initialize ' + websiteId
			}).success (function (data) {
				$scope.alreadyInitialized = data.result;
				if (data.result) {
					NotificationCenter.warning(null, i18n.trans('m.rbs.generic.admin.already_initialized_website | ucf'));
				}
			}).error (function (error) {
				console.error(error);
				NotificationCenter.error(i18n.trans('m.rbs.generic.admin.check_already_initialized_error | ucf'),
					ErrorFormatter.format(error));
			});
		}

		function preselectTopics(websiteId) {
			$http.post(REST.getBaseUrl('Rbs/Generic/GetDocumentsByCodes'), {
				codes: {userAccountTopicId: 'user_account_topic'},
				context: 'Website_' + websiteId
			}).success (function (data) {
				if (data.userAccountTopicId) {
					$scope.data.userAccountTopicId = data.userAccountTopicId;
				}
			}).error (function (error) {
				console.error(error);
				NotificationCenter.error(i18n.trans('m.rbs.generic.admin.get_document_by_code_error | ucf'),
					ErrorFormatter.format(error));
			});
		}

		$scope.$watch('data.websiteId', function (websiteId) {
			NotificationCenter.clear();
			if (websiteId) {
				preselectTopics(websiteId);
				checkAlreadyInitialized(websiteId);
			}
			else {
				$scope.alreadyInitialized = false;
			}
		});
	}

	RbsGenericInitializeWebsiteCtrl.$inject = ['$scope', '$http', 'RbsChange.REST', 'RbsChange.i18n', 'RbsChange.NotificationCenter', 'RbsChange.ErrorFormatter'];
	app.controller('RbsGenericInitializeWebsiteCtrl', RbsGenericInitializeWebsiteCtrl);
})();