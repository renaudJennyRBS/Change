(function (jQuery) {

	"use strict";

	var app = angular.module('RbsChange');

	app.filter('collectionFilterTemplateURL', function () {
		return function (modelName) {
			return 'Collection/'  + modelName.replace(/_/g, '/') + '/filter-panel.twig';
		};
	});

	function loadStoredFilter(localStorageService, model, filter) {
		var local = localStorageService.get('filter_' + model);
		if (local) {
			local = angular.fromJson(local);
			if (local && local.name === 'group')
			{
				angular.copy(local, filter);
			}
		}
	}

	function removeStoredFilter(localStorageService, model) {
		localStorageService.remove('filter_' + model);
	}

	function saveStoredFilter(localStorageService, model, filter) {
		if (filter.filters.length === 0) {
			removeStoredFilter(localStorageService, model);
		} else {
			var local = angular.copy(filter);
			localStorageService.add('filter_' + model, angular.toJson(local));
		}
	}

	app.directive('rbsDocumentFilterPanel', ['localStorageService', function(localStorageService) {
		return {
			restrict: 'E',
			templateUrl : 'Rbs/Admin/js/directives/document-filter-panel.twig',
			link: function(scope, element, attrs) {

				if (attrs.hasOwnProperty('model')) {
					scope.model = attrs.model;
				} else {
					console.log('rbsDocumentFilterPanel directive require "model" attribute');
					scope.model ='Rbs_Invalid_Model';
					element.hide();
				}

				if (attrs.hasOwnProperty('openByDefault')) {
					scope.showFilter = (attrs.openByDefault == 'true');
				} else {
					scope.showFilter = false;
				}

				scope.closeFilterPanel = function() {
					scope.showFilter = false;
				};

				scope.openFilterPanel = function() {
					scope.showFilter = true;
				};

				scope.toggleFilterPanel = function() {
					if (scope.showFilter) {
						scope.closeFilterPanel();
					} else {
						scope.openFilterPanel();
					}
				};

				scope.resetFilter = function() {
					scope.$broadcast('resetFilter', {});
					removeStoredFilter(localStorageService, scope.model);
				};

				scope.applyFilter = function() {
					if (scope.filter.hasOwnProperty('search')) {
						scope.filter.search ++;
					} else {
						scope.filter.search = 1;
					}
					saveStoredFilter(localStorageService, scope.model, scope.filter);
				};

				if (!angular.isObject(scope.filter)){
					scope.filter = {};
				}
			}
		}
	}]);

	function redrawFilters($compile, scope, element, filters) {
		var collection = element.children('ul.list-group').children('li:not(:last)');
		collection.each(function() {
			angular.element(jQuery(this)).isolateScope().$destroy();
		});
		collection.remove();

		var html = '', directiveClass;
		angular.forEach(filters, function(filter, idx){
			if (scope.filterDefinitions.hasOwnProperty(filter.name)) {
				directiveClass = scope.filterDefinitions[filter.name].directiveClass;
				html += '<li class="list-group-item '+ directiveClass + '" filter="filter.filters['+ idx + ']" parent-operator="filter.operator"></li>';
			} else {
				html += '<li class="list-group-item rbs-document-filter-unknown" filter="filter.filters['+ idx + ']" parent-operator="filter.operator"></li>';
			}
		});

		if (html != '') {
			element.children('ul.list-group').children('li:last').before(html);
			$compile(element.children('ul.list-group').children('li:not(:last)'))(scope);
		}
	}

	app.directive('rbsDocumentFilterDefinition', function() {
		return {
			restrict: 'C',
			require: '^rbsDocumentFilterContainer',
			template : '<div></div>',
			replace: true,
			scope: {
				label: '@', group: '@',
				name: '@', directiveClass: '@'
			},
			link: function(scope, element, attrs, filterContainerCtrl) {
				scope.parameters = {};
				scope.config = {};
				if (attrs.hasOwnProperty('parameters'))
				{
					angular.forEach(angular.fromJson(attrs.parameters), function(v, k) {
						scope.parameters[k] = v;
					})
				}
				else
				{
					angular.forEach(attrs, function(value, key) {
						if (key.substr(0, 9) === 'parameter')
						{
							key = key.substr(9);
							if (key.length)
							{
								key = key.substr(0,1).toLowerCase() + key.substr(1);
								scope.parameters[key] = value;
							}

						}
					});
				}
				if (attrs.hasOwnProperty('configs'))
				{
					angular.forEach(angular.fromJson(attrs.configs), function(v, k) {
						scope.config[k] = v;
					})
				}
				else
				{
					angular.forEach(attrs, function(value, key) {
						if (key.substr(0, 6) === 'config')
						{
							key = key.substr(6);
							if (key.length)
							{
								key = key.substr(0,1).toLowerCase() + key.substr(1);
								scope.config[key] = value;
							}

						}
					});
				}
				filterContainerCtrl.addFilterDefinition(scope);
			}
		};
	});

	app.directive('rbsDocumentFilterDefinitions', function() {
		return {
			restrict: 'C',
			require: '^rbsDocumentFilterContainer',
			template : '<div></div>',
			replace: true,
			scope: true,
			link: function(scope, element, attrs, filterContainerCtrl) {
				if (attrs.hasOwnProperty('definitions'))
				{
					var definitions = angular.fromJson(attrs.definitions);
					angular.forEach(definitions, function(definition) {
						if (definition.hasOwnProperty('name') &&
							definition.hasOwnProperty('directiveClass') &&
							definition.hasOwnProperty('config')) {
							if (!definition.hasOwnProperty('parameters'))
							{
								definition.parameters = {};
							}
							definition.label = definition.config.listLabel;
							definition.group = definition.config.group;
							filterContainerCtrl.addFilterDefinition(definition);
						}
					});
				}
			}
		};
	});

	app.directive('rbsDocumentFilterContainer', ['$compile', 'localStorageService', function($compile, localStorageService) {
		var searchIndex = 0;
		return {
			restrict: 'C',
			transclude: true,
			templateUrl : 'Rbs/Admin/js/directives/document-filter-container.twig',
			scope: {filter : '=', model: '@'},
			controller: ['$scope', function(scope) {

				function resetFilter() {
					if (!angular.isObject(scope.filter)) {
						scope.filter = {};
					}
					scope.filter.name = 'group';
					scope.filter.search = searchIndex++;
					scope.filter.parameters = {all:0, configured:0};
					scope.filter.operator = 'AND';
					scope.filter.filters = [];
				}

				function delFilter(filter) {
					var removeFilter = function(filter, filters) {
						for (var i = 0; i < filters.length; i++) {
							if (filters[i] === filter) {
								filters.splice(i, 1);
								return true;
							}
							else if (filters[i].hasOwnProperty('filters')) {
								if (removeFilter(filter, filters[i].filters)) {
									return true;
								}
							}
						}
						return false;
					};
					removeFilter(filter, scope.filter.filters);
				}

				if (!angular.isObject(scope.filter) || !scope.filter.hasOwnProperty('name') || scope.filter.name !== 'group') {
					resetFilter();
				} else {
					scope.defaultFilter = angular.copy(scope.filter);
				}

				var filterDefinitions = scope.filterDefinitions = {};

				scope.definitionToAdd = null;
				scope.sortedDefinitions = [];

				scope.$on('resetFilter', function(event, args) {
					if (scope.defaultFilter) {
						angular.copy(scope.defaultFilter, scope.filter);
						scope.filter.search = searchIndex++;
					} else {
						resetFilter();
					}
				});

				this.addFilterDefinition = function(filterDefinition) {
					if (filterDefinitions.hasOwnProperty(filterDefinition.name)){
						throw new Error('Duplicate filter definition name');
					}
					filterDefinitions[filterDefinition.name] = filterDefinition;
					scope.sortedDefinitions.push(filterDefinition);
				};

				this.getFilterDefinitions = function() {
					return filterDefinitions;
				};

				this.getSortedDefinitions = function() {
					return scope.sortedDefinitions;
				};

				scope.isAnd = function() {
					return scope.filter.operator == 'AND';
				};

				scope.countAllFilters = function() {
					var args = scope.filter.parameters;
					var oldConfigured = args.configured, oldAll = args.all;
					args.all = args.configured = 0;
					scope.$broadcast('countAllFilters', args);
					if (args.configured != oldConfigured || args.all != oldAll)
					{
						saveStoredFilter(localStorageService, scope.model, scope.filter);
					}
					return args.configured + ' / ' + args.all;
				};

				scope.applyFilter = function() {
//					if (scope.filter.parameters.configured > 0) {
//						scope.filter.search = searchIndex++;
//					}
					saveStoredFilter(localStorageService, scope.model, scope.filter);
				};

				this.applyFilter = function() {
					scope.applyFilter();
				};

				this.linkNode = function(nodeScope) {
					nodeScope.delFilter = function () {
						delFilter(nodeScope.filter);
						scope.applyFilter();
					};
					if (nodeScope.filter.name && scope.filterDefinitions.hasOwnProperty(nodeScope.filter.name)) {
						nodeScope.filterDefinition = scope.filterDefinitions[nodeScope.filter.name];
					}
				};
				loadStoredFilter(localStorageService, scope.model, scope.filter);
				scope.filter.search = searchIndex++;
			}],

			link: function(scope, element) {
				scope.swapOperator = function() {
					var rc = function (pOperator, filters) {
						for (var i = 0; i < filters.length; i++) {
							if (filters[i].hasOwnProperty('filters')) {
								filters[i].operator = pOperator == 'AND' ? 'OR' : 'AND';
								rc(filters[i].operator, filters[i].filters);
							}
						}
					};
					scope.filter.operator =  scope.filter.operator == 'AND' ? 'OR' : 'AND';
					rc(scope.filter.operator , scope.filter.filters);
					scope.applyFilter();
				};

				scope.addFilter = function() {
					if (angular.isObject(scope.definitionToAdd)) {
						var childName = scope.definitionToAdd.name;
						scope.filter.filters.push({name: childName, parameters:angular.copy(scope.definitionToAdd.parameters)});
						scope.definitionToAdd = null;
						scope.applyFilter();
					}
				};

				scope.$watchCollection('filter.filters', function(filters) {
					redrawFilters($compile, scope, element, filters);
				});
			}
		};
	}]);

	app.directive('rbsDocumentFilterGroup', ['$compile', function($compile) {
		return {
			restrict: 'C',
			require: '^rbsDocumentFilterContainer',
			templateUrl : 'Rbs/Admin/js/directives/document-filter-group.twig',
			scope: {
				filter : '=',
				parentOperator : '='
			},
			controller: ['$scope', function(scope) {
				var operator = scope.parentOperator == 'AND' ? 'OR' : 'AND';

				if (scope.filter === undefined) {
					scope.filter = {name: 'group', operator: operator, filters:[], parameters:{}};
				} else {
					scope.filter.operator = operator;
					if (!angular.isArray(scope.filter.filters))
					{
						scope.filter.filters = [];
					}
				}

				scope.isAnd = function() {
					return scope.filter.operator == 'AND';
				};

				scope.countAllFilters = function() {
					var args = {all: 0, configured: 0};
					scope.$broadcast('countAllFilters', args);
					return args.configured + ' / ' + args.all;
				};
			}],

			link: function(scope, element, attrs, containerController) {
				scope.definitionToAdd = null;
				containerController.linkNode(scope);

				scope.sortedDefinitions = containerController.getSortedDefinitions();
				scope.filterDefinitions = containerController.getFilterDefinitions();

				scope.addFilter = function() {
					if (angular.isObject(scope.definitionToAdd)) {
						var childName = scope.definitionToAdd.name;
						scope.filter.filters.push({name: childName, parameters:angular.copy(scope.definitionToAdd.parameters)});
						scope.definitionToAdd = null;
					}
				};

				scope.$watchCollection('filter.filters', function(filters) {
					redrawFilters($compile, scope, element, filters);
				});
			}
		};
	}]);


	app.directive('rbsDocumentFilterUnknown', function() {
		return {
			restrict: 'C',
			require: '^rbsDocumentFilterContainer',
			templateUrl : 'Rbs/Admin/js/directives/document-filter-unknown.twig',
			scope: {
				filter : '='
			},
			controller: ['$scope', function(scope) {
				scope.isConfigured = function() {
					return false;
				};
				scope.$on('countAllFilters', function(event, args) {
					args.all++;
					if (scope.isConfigured()) {
						args.configured++;
					}
				});
			}],

			link: function(scope, element, attrs, containerController) {
				containerController.linkNode(scope);
			}
		};
	});

	app.directive('rbsDocumentFilterPropertyBoolean', function() {
		return {
			restrict: 'C',
			require: '^rbsDocumentFilterContainer',
			templateUrl : 'Rbs/Admin/js/directives/document-filter-property-boolean.twig',
			scope: {
				filter : '='
			},
			controller: ['$scope', function(scope) {
				scope.filter.parameters.operator = 'eq';
				if (!scope.filter.parameters.hasOwnProperty('value')){
					scope.filter.parameters.value = false;
				}
				scope.isConfigured = function() {
					return scope.filter.parameters.operator && scope.filter.parameters.hasOwnProperty('value');
				};

				scope.$on('countAllFilters', function(event, args) {
					args.all++;
					if (scope.isConfigured()) {
						args.configured++;
					}
				});
			}],

			link: function(scope, element, attrs, containerController) {
				containerController.linkNode(scope);
			}
		};
	});

	app.directive('rbsDocumentFilterPropertyDatetime', function() {
		return {
			restrict: 'C',
			require: '^rbsDocumentFilterContainer',
			templateUrl : 'Rbs/Admin/js/directives/document-filter-property-datetime.twig',
			scope: {
				filter : '='
			},
			controller: ['$scope', function(scope) {
				scope.isConfigured = function() {
					var op = scope.filter.parameters.operator;
					return op && (op == 'isNull' || scope.filter.parameters.value);
				};

				scope.$on('countAllFilters', function(event, args) {
					args.all++;
					if (scope.isConfigured()) {
						args.configured++;
					}
				});
			}],

			link: function(scope, element, attrs, containerController) {
				containerController.linkNode(scope);
			}
		};
	});

	app.directive('rbsDocumentFilterPropertyDocument', function() {

		return {
			restrict: 'C',
			require: '^rbsDocumentFilterContainer',
			templateUrl : 'Rbs/Admin/js/directives/document-filter-property-document.twig',
			scope: {
				filter : '='
			},
			controller: ['$scope', function(scope) {
				scope.isConfigured = function() {
					var op = scope.filter.parameters.operator;
					return op && (op == 'isNull' || scope.filter.parameters.value);
				};

				scope.$on('countAllFilters', function(event, args) {
					args.all++;
					if (scope.isConfigured()) {
						args.configured++;
					}
				});
			}],

			link: function(scope, element, attrs, containerController) {
				containerController.linkNode(scope);
			}
		};
	});

	app.directive('rbsDocumentFilterPropertyNumber', function() {
		return {
			restrict: 'C',
			require: '^rbsDocumentFilterContainer',
			templateUrl : 'Rbs/Admin/js/directives/document-filter-property-number.twig',
			scope: {
				filter : '='
			},
			controller: ['$scope', function(scope) {
				scope.isConfigured = function() {
					var op = scope.filter.parameters.operator;
					return op && (op == 'isNull' || scope.filter.parameters.value);
				};

				scope.$on('countAllFilters', function(event, args) {
					args.all++;
					if (scope.isConfigured()) {
						args.configured++;
					}
				});
			}],

			link: function(scope, element, attrs, containerController) {
				containerController.linkNode(scope);
			}
		};
	});

	app.directive('rbsDocumentFilterPropertyString', function() {
		return {
			restrict: 'C',
			require: '^rbsDocumentFilterContainer',
			templateUrl : 'Rbs/Admin/js/directives/document-filter-property-string.twig',
			scope: {
				filter : '='
			},
			controller: ['$scope', function(scope) {
				scope.isConfigured = function() {
					var op = scope.filter.parameters.operator;
					return op && (op == 'isNull' || scope.filter.parameters.value);
				};

				scope.$on('countAllFilters', function(event, args) {
					args.all++;
					if (scope.isConfigured()) {
						args.configured++;
					}
				});
			}],

			link: function(scope, element, attrs, containerController) {
				containerController.linkNode(scope);
			}
		};
	});

})(window.jQuery);