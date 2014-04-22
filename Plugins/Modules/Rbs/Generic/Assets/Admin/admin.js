(function () {
	"use strict";

	var app = angular.module('RbsChange');

	function RbsGenericInitializeWebsiteCtrl ($scope, $http, REST, i18n, NotificationCenter, ErrorFormatter)
	{
		$scope.data = {website: null, sidebarTemplate: null, noSidebarTemplate: null, LCID: null, userAccountTopic: null};
		$scope.onInitialization = false;

		$scope.initializeWebsiteStructure = function () {
			$scope.onInitialization = true;
			$http.post(REST.getBaseUrl('Rbs/Generic/InitializeWebsite'), {
				websiteId: $scope.data.website.id,
				sidebarTemplateId: $scope.data.sidebarTemplate.id,
				noSidebarTemplateId: $scope.data.noSidebarTemplate.id,
				LCID: $scope.data.LCID,
				userAccountTopicId: $scope.data.userAccountTopic != null ? $scope.data.userAccountTopic.id : null
			}).success(function (){
				$scope.onInitialization = false;
				$scope.alreadyInitialized = true;
			}).error(function (error){
				console.error(error);
				$scope.onInitialization = false;
				NotificationCenter.error(i18n.trans('m.rbs.commerce.admin.initialize_web_store_error | ucf'),
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
				codes: {userAccount: 'user_account_topic'},
				context: 'Website_' + websiteId
			}).success (function (data) {
				if (data.userAccount) {
					REST.resource('Rbs_Website_Topic', data.userAccount).then(function (document){
						$scope.data.userAccountTopic = document;
					});
				}
			}).error (function (error) {
				console.error(error);
				NotificationCenter.error(i18n.trans('m.rbs.generic.admin.get_document_by_code_error | ucf'),
					ErrorFormatter.format(error));
			});
		}

		$scope.$watch('data.website', function (website) {
			NotificationCenter.clear();
			if (website) {
				preselectTopics(website.id);
				checkAlreadyInitialized(website.id);
			}
			else {
				$scope.alreadyInitialized = false;
			}
		});
	}

	RbsGenericInitializeWebsiteCtrl.$inject = ['$scope', '$http', 'RbsChange.REST', 'RbsChange.i18n', 'RbsChange.NotificationCenter', 'RbsChange.ErrorFormatter'];
	app.controller('RbsGenericInitializeWebsiteCtrl', RbsGenericInitializeWebsiteCtrl);
})();