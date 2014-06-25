(function($) {
	"use strict";

	var app = angular.module('RbsChange');

	// TODO: Dispatch the content of this file in appropriate files?

	app.factory('RbsChange.EditorManager',
		['$compile', '$http', '$timeout', '$q', '$rootScope', '$routeParams', '$location', '$resource', 'RbsChange.Dialog',
			'RbsChange.MainMenu', 'RbsChange.REST', 'RbsChange.Utils', 'RbsChange.ArrayUtils', 'localStorageService',
			function($compile, $http, $timeout, $q, $rootScope, $routeParams, $location, $resource, Dialog, MainMenu, REST, Utils, ArrayUtils, localStorageService) {
				var localCopyRepo;

				localCopyRepo = localStorageService.get("localCopy");

				if (localCopyRepo) {
					localCopyRepo = JSON.parse(localCopyRepo);
				}

				if (!angular.isObject(localCopyRepo)) {
					localCopyRepo = {};
					commitLocalCopyRepository();
				}

				// Local copy methods.

				function commitLocalCopyRepository() {
					localStorageService.add("localCopy", JSON.stringify(localCopyRepo));
				}

				function makeLocalCopyKey(doc) {
					var key;
					if (doc.id < 0) {
						key = doc.model + '-' + 'new';
					}
					else {
						key = doc.model + '-' + doc.id;
					}
					if (doc.LCID) {
						key += '-' + doc.LCID;
					}
					return key;
				}

				return {
					// Local copy public API.

					'saveLocalCopy': function(doc, url) {
						var key = makeLocalCopyKey(doc);
						doc.META$.localCopy = {
							saveDate: (new Date()).toString(),
							documentVersion: doc.documentVersion,
							modificationDate: doc.modificationDate,
							publicationStatus: doc.publicationStatus,
							editorUrl: url || doc.url()
						};
						delete doc.documentVersion;
						delete doc.modificationDate;
						delete doc.publicationStatus;
						localCopyRepo[key] = doc;
						commitLocalCopyRepository();
					},

					'getLocalCopy': function(doc) {
						var key = makeLocalCopyKey(doc);
						return localCopyRepo.hasOwnProperty(key) ? localCopyRepo[key] : null;
					},

					'removeLocalCopy': function(doc) {
						var key = makeLocalCopyKey(doc);
						if (localCopyRepo.hasOwnProperty(key)) {
							delete localCopyRepo[key];
							delete doc.META$.localCopy;
							commitLocalCopyRepository();
						}
					},

					'removeCreationLocalCopy': function(doc) {
						var key = makeLocalCopyKey(doc);
						if (localCopyRepo.hasOwnProperty(key)) {
							delete localCopyRepo[key];
							delete doc.META$.localCopy;
							commitLocalCopyRepository();
						}
					},

					'removeAllLocalCopies': function() {
						for (var key in localCopyRepo) {
							if (localCopyRepo.hasOwnProperty(key)) {
								delete localCopyRepo[key];
							}
						}
						localStorageService.remove("temporaryId");
						commitLocalCopyRepository();
					},

					'getLocalCopies': function() {
						return localCopyRepo;
					}
				};
			}]);

	app.controller('RbsChangeTranslateEditorController', ['$scope', 'RbsChange.MainMenu', function($scope, MainMenu) {
		$scope.document = {};
		$scope.editMode = 'translate';
		MainMenu.clear();
	}]);

	app.controller('RbsChangeWorkflowController',
		['RbsChange.REST', '$scope', '$filter', '$routeParams', 'RbsChange.i18n', 'RbsChange.Utils',
			function(REST, $scope, $filter, $routeParams, i18n, Utils) {
				$scope.$watch('model', function(model) {
					if (model) {
						REST.resource(model, $routeParams.id, $routeParams.LCID).then(function(doc) {
							$scope.document = doc;

							var mi = Utils.modelInfo(model),
								location = [
									[
										i18n.trans('m.' + angular.lowercase(mi.vendor + '.' + mi.module) +
											'.adminjs.module_name | ucf'),
										$filter('rbsURL')(mi.vendor + '_' + mi.module, 'home')
									],
									[
										i18n.trans('m.' +
											angular.lowercase(mi.vendor + '.' + mi.module + '.adminjs.' + mi.document) +
											'_list | ucf'),
										$filter('rbsURL')(model, 'list')
									]
								];
						});
					}
				});
			}]);

	/**
	 * Default controller for Document-based views.
	 */
	app.controller('RbsChangeSimpleDocumentController',
		['RbsChange.REST', '$scope', '$routeParams', function(REST, $scope, $routeParams) {
			REST.resource($routeParams.id).then(function(doc) {
				$scope.document = doc;
			});
		}]);

	/**
	 * Redirects to the editor of the document with the id specified in $routeParams.
	 */
	function RedirectToForm($routeParams, $location, REST, $filter) {
		var listId = $routeParams.id;
		REST.resource(listId).then(function(doc) {
			$location.path($filter('rbsURL')(doc, 'edit'));
		});
	}

	RedirectToForm.$inject = ['$routeParams', '$location', 'RbsChange.REST', '$filter'];
	app.controller('RbsChangeRedirectToForm', RedirectToForm);

	// Validators directives.

	var INTEGER_REGEXP = /^\-?\d*$/;
	app.directive('rbsInteger', function() {
		return {
			require: 'ngModel',
			link: function(scope, elm, attrs, ctrl) {
				ctrl.$parsers.unshift(function(viewValue) {
					if (angular.isNumber(viewValue)) {
						return viewValue;
					}
					else if (viewValue == '' || INTEGER_REGEXP.test(viewValue)) {
						// it is valid
						ctrl.$setValidity('integer', true);
						return viewValue;
					}
					else {
						// it is invalid, return undefined (no model update)
						ctrl.$setValidity('integer', false);
						return undefined;
					}
				});
			}
		};
	});

	var FLOAT_REGEXP = /^\-?\d+((\.|\,)\d+)?$/;
	app.directive('rbsSmartFloat', function() {
		return {
			require: 'ngModel',
			link: function(scope, elm, attrs, ctrl) {
				ctrl.$parsers.unshift(function(viewValue) {
					if (angular.isNumber(viewValue)) {
						return viewValue;
					}
					else if (FLOAT_REGEXP.test(viewValue)) {
						ctrl.$setValidity('float', true);
						return parseFloat(viewValue.replace(',', '.'));
					}
					else if (viewValue == '') {
						ctrl.$setValidity('float', true);
						return undefined;
					}
					else {
						ctrl.$setValidity('float', false);
						return undefined;
					}
				});
			}
		};
	});
})(window.jQuery);