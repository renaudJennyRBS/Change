(function () {
	"use strict";

	var app = angular.module('RbsChange');

	function RbsCommerceInitializeWebStoreCtrl ($scope, $http, REST, i18n, NotificationCenter, ErrorFormatter)
	{
		$scope.data = {websiteId: null, storeId: null, sidebarTemplateId: null, noSidebarTemplateId: null, LCID: null, userAccountTopicId: null};

		$scope.initializeWebStoreStructure = function () {
			$scope.onInitialization = true;
			$http.post(REST.getBaseUrl('commands/rbs_commerce/initialize-web-store'), {
				websiteId: $scope.data.websiteId,
				storeId: $scope.data.storeId,
				sidebarTemplateId: $scope.data.sidebarTemplateId,
				noSidebarTemplateId: $scope.data.noSidebarTemplateId,
				LCID: $scope.data.LCID,
				userAccountTopicId: $scope.data.userAccountTopicId
			}).success(function (){
				$scope.onInitialization = false;
				$scope.alreadyInitialized = true;
				NotificationCenter.info(null, i18n.trans('m.rbs.commerce.admin.initialize_web_store_success | ucf'));
			}).error(function (error){
				console.error(error);
				$scope.onInitialization = false;
				NotificationCenter.error(i18n.trans('m.rbs.commerce.admin.initialize_web_store_error | ucf'),
					ErrorFormatter.format(error));
			});
		};

		function checkAlreadyInitialized(websiteId, storeId) {
			$http.post(REST.getBaseUrl('Rbs/Generic/DocumentCodeContextExist'), {
				context: 'Rbs Commerce WebStore Initialize ' + websiteId + ' ' + storeId
			}).success (function (data) {
				$scope.alreadyInitialized = data.result;
				if (data.result) {
					NotificationCenter.warning(null, i18n.trans('m.rbs.commerce.admin.already_initialized_web_store | ucf'));
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
				if ($scope.data.storeId) {
					checkAlreadyInitialized(websiteId, $scope.data.storeId);
				}
			}
			else {
				$scope.alreadyInitialized = false;
			}
		});

		$scope.$watch('data.storeId', function (storeId) {
			NotificationCenter.clear();
			if (storeId && $scope.data.websiteId) {
				checkAlreadyInitialized($scope.data.websiteId, storeId);
			}
			else {
				$scope.alreadyInitialized = false;
			}
		});
	}

	RbsCommerceInitializeWebStoreCtrl.$inject = ['$scope', '$http', 'RbsChange.REST', 'RbsChange.i18n', 'RbsChange.NotificationCenter', 'RbsChange.ErrorFormatter'];
	app.controller('RbsCommerceInitializeWebStoreCtrl', RbsCommerceInitializeWebStoreCtrl);

	function RbsCommerceInitializeOrderProcessCtrl ($scope, $http, REST, i18n, NotificationCenter, ErrorFormatter)
	{
		$scope.data = {websiteId: null, storeId: null, sidebarTemplateId: null, noSidebarTemplateId: null, popinTemplateId: null, LCID: null, userAccountTopicId: null};

		$scope.initializeOrderProcessStructure = function () {
			$scope.onInitialization = true;
			$http.post(REST.getBaseUrl('commands/rbs_commerce/initialize-order-process'), {
				websiteId: $scope.data.websiteId,
				storeId: $scope.data.storeId,
				sidebarTemplateId: $scope.data.sidebarTemplateId,
				noSidebarTemplateId: $scope.data.noSidebarTemplateId,
				popinTemplateId: $scope.data.popinTemplateId,
				LCID: $scope.data.LCID,
				userAccountTopicId: $scope.data.userAccountTopicId
			}).success(function (){
				$scope.onInitialization = false;
				$scope.alreadyInitialized = true;
				NotificationCenter.info(null, i18n.trans('m.rbs.commerce.admin.initialize_order_process_success | ucf'));
			}).error(function (error){
				console.error(error);
				$scope.onInitialization = false;
				NotificationCenter.error(i18n.trans('m.rbs.commerce.admin.initialize_order_process_error | ucf'),
					ErrorFormatter.format(error));
			});
		};

		function checkAlreadyInitialized(websiteId, storeId) {
			$http.post(REST.getBaseUrl('Rbs/Generic/DocumentCodeContextExist'), {
				context: 'Rbs Commerce Order Process Initialize ' + websiteId + ' ' + storeId
			}).success (function (data) {
				$scope.alreadyInitialized = data.result;
				if (data.result) {
					NotificationCenter.warning(null, i18n.trans('m.rbs.commerce.admin.already_initialized_order_process | ucf'));
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
				if ($scope.data.storeId) {
					checkAlreadyInitialized(websiteId, $scope.data.storeId);
				}
			}
			else {
				$scope.alreadyInitialized = false;
			}
		});

		$scope.$watch('data.storeId', function (storeId) {
			NotificationCenter.clear();
			if (storeId && $scope.data.websiteId) {
				checkAlreadyInitialized($scope.data.websiteId, storeId);
			}
			else {
				$scope.alreadyInitialized = false;
			}
		});
	}

	RbsCommerceInitializeOrderProcessCtrl.$inject = ['$scope', '$http', 'RbsChange.REST', 'RbsChange.i18n', 'RbsChange.NotificationCenter', 'RbsChange.ErrorFormatter'];
	app.controller('RbsCommerceInitializeOrderProcessCtrl', RbsCommerceInitializeOrderProcessCtrl);
})();