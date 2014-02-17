(function (jQuery) {

	"use strict";

	var app = angular.module('RbsChange');

	app.filter('rbsCollectionFilterTemplateURL', function () {
		return function (modelName) {
			return 'Collection/'  + modelName.replace(/_/g, '/') + '/filter-panel.twig';
		};
	});

	app.directive('rbsDocumentFilterPanel', ['$http', 'localStorageService', 'RbsChange.REST', 'RbsChange.Utils', 'RbsChange.NotificationCenter', 'RbsChange.i18n', function ($http, localStorageService, REST, Utils, NotificationCenter, i18n) {
		var searchIndex = 0;
		return {
			restrict: 'E',
			templateUrl : 'Rbs/Admin/js/directives/document-filter-panel.twig',
			controller: ['$scope', function(scope) {
				if (!angular.isDefined(scope.filter)) {
					scope.filter = {};
				}
			}],
			link: function(scope, element, attrs) {
				var model = attrs.model;

				if (!model) {
					scope.model ='Rbs_Invalid_Model';
					element.hide();
				}

				function getStoredFilter() {
					var local = localStorageService.get('filter_' + model);
					if (local) {
						return angular.fromJson(local);
					}
					return null;
				}

				function removeStoredFilter() {
					localStorageService.remove('filter_' + model);
				}

				function saveStoredFilter(filter) {
					if (!filter || !filter.filters ||  filter.filters.length === 0) {
						removeStoredFilter();
					} else {
						localStorageService.add('filter_' + model, angular.toJson(filter));
					}
				}

				scope.countAllFilters = function() {
					if (!angular.isObject(scope.filter.parameters))
					{
						scope.filter.parameters = {};
					}
					var args = scope.filter.parameters;
					args.all = args.configured = 0;
					scope.$broadcast('countAllFilters', args);
					return args.all;
				};

				function initializeFilter()
				{
					scope.model = attrs.model;
					var loadedFilter = getStoredFilter();
					if (loadedFilter && loadedFilter.name == 'group') {
						angular.copy(loadedFilter, scope.filter);
					} else if (scope.defaultFilter && scope.defaultFilter.name == 'group') {
						angular.copy(scope.defaultFilter, scope.filter);
					}
					scope.showFilter = (attrs.openByDefault == 'true' || (scope.filter && scope.filter.filters && scope.filter.filters.length));
					scope.filter.search = searchIndex++;
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
					if (scope.defaultFilter && scope.defaultFilter.name == 'group') {
						angular.copy(scope.defaultFilter, scope.filter);
					} else {
						scope.$broadcast('resetFilter', {});
					}
					removeStoredFilter();
					scope.filter.search = searchIndex++;
				};

				scope.applyFilter = function() {
					saveStoredFilter(scope.filter);
					scope.filter.search = searchIndex++;
				};

				scope.$on('applyFilter', function(event) {
					scope.applyFilter();
					event.stopPropagation();
				});

				initializeFilter();

				scope.$watchCollection('filter.filters', function(filters) {
					if (!scope.showFilter && angular.isArray(filters) && filters.length) {
						scope.showFilter = true;
					}
				});


				scope.savedFilters = [];
				scope.showFilterList = false;


				function loadFilters ()
				{
					var p, url = Utils.makeUrl(REST.getBaseUrl('actions/filters/'), {'model' : model});
					p = $http.get(url);
					p.success(function (filters)
					{
						scope.savedFilters = filters;
					});
					return p;
				}
				loadFilters();


				scope.useExistingFilter = function (f)
				{
					if (f && f.content) {
						scope.filter = f.content;
						scope.existingFilterInUse = f;
						scope.applyFilter();
					} else {
						scope.existingFilterInUse = null;
					}
				};


				scope.createFilter = function ()
				{
					var url = Utils.makeUrl(REST.getBaseUrl('actions/filters/')),
						label = window.prompt(i18n.trans('m.rbs.admin.adminjs.enter_filter_title'));

					if (label)
					{
						$http.post(url, {'model_name' : model, 'content' : angular.fromJson(scope.filter), 'label' : label})
							.success(function (data) {
								loadFilters().then(function ()
								{
									scope.useExistingFilter(data);
								});
							})
							.error(function (data) {
								NotificationCenter.error(i18n.trans('m.rbs.admin.adminjs.filter_create_error'), data.message, 'rbs_filter_create_error');
							});
					}
				};


				scope.updateExistingFilter = function ()
				{
					var url = Utils.makeUrl(REST.getBaseUrl('actions/filters/'));

					$http.put(url, scope.existingFilterInUse)
						.success(function () {
							loadFilters();
						})
						.error(function (data) {
							NotificationCenter.error(i18n.trans('m.rbs.admin.adminjs.filter_update_error'), data.message, 'rbs_filter_update_error');
						});
				};


				scope.removeExistingFilter = function ()
				{
					if (window.confirm(i18n.trans('m.rbs.admin.adminjs.confirm_delete_filter')))
					{
						var url = Utils.makeUrl(REST.getBaseUrl('actions/filters/'), {'filter_id' : scope.existingFilterInUse['filter_id']});

						$http['delete'](url)
							.success(function () {
								scope.useExistingFilter(null);
								loadFilters();
							})
							.error(function (data) {
								NotificationCenter.error(i18n.trans('m.rbs.admin.adminjs.filter_delete_error'), data.message, 'rbs_filter_delete_error');
							});
					}
				};
			}
		};
	}]);

	function redrawFilters($compile, scope, element, filters) {
		var collection = element.children('ul.list-group').children('li:not(:last)');
		collection.each(function() {
			angular.element(jQuery(this)).isolateScope().$destroy();
		});
		collection.remove();

		var html = '', directiveName, contextKey;
		angular.forEach(filters, function(filter, idx){
			if (scope.filterDefinitions.hasOwnProperty(filter.name)) {
				directiveName = scope.filterDefinitions[filter.name].directiveName;
				html += '<li class="list-group-item" '+ directiveName+ '="" filter="filter.filters['+ idx + ']"';
				contextKey = element.attr('context-key');
				if (contextKey && contextKey.length) {
					html += ' context-key="' + contextKey + '_'  + idx +'"';
				} else {
					html += ' context-key="' + idx +'"';
				}
				if (filter.name === 'group') {
					html += ' parent-operator="filter.operator"';
				}
				html += '></li>';
			} else {
				html += '<li class="list-group-item" rbs-document-filter-unknown="" filter="filter.filters['+ idx + ']" parent-operator="filter.operator"></li>';
			}
		});

		if (html != '') {
			element.children('ul.list-group').children('li:last').before(html);
			$compile(element.children('ul.list-group').children('li:not(:last)'))(scope);
		}
	}

	app.directive('rbsDocumentFilterDefinition', function() {
		return {
			restrict: 'A',
			require: '^rbsDocumentFilterContainer',
			template : '<div></div>',
			replace: true,
			scope: {
				label: '@', group: '@',
				name: '@', directiveName: '@'
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
			restrict: 'A',
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
							definition.hasOwnProperty('directiveName') &&
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

	app.directive('rbsDocumentFilterContainer', ['$compile', 'RbsChange.Models', 'RbsChange.Navigation', function($compile, Models, Navigation) {
		return {
			restrict: 'A',
			transclude: true,
			templateUrl : 'Rbs/Admin/js/directives/document-filter-container.twig',
			scope: {filter : '=', model: '@'},
			controller: ['$scope', function(scope) {

				function initFilter() {
					scope.filter.name = 'group';
					scope.filter.parameters = {all: 0, configured: 0};
					scope.filter.operator = 'AND';
					scope.filter.filters = [];
				}

				scope.$watch('filter', function(filter) {
					if (angular.isObject(filter) && scope.filter.name !== 'group') {
						initFilter();
					}
				});

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

				var filterDefinitions = scope.filterDefinitions = {};

				scope.definitionToAdd = null;
				scope.sortedDefinitions = [];

				scope.$on('resetFilter', function() {
					initFilter();
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
					if (!angular.isObject(scope.filter.parameters))
					{
						scope.filter.parameters = {};
					}
					var args = scope.filter.parameters;
					args.all = args.configured = 0;
					scope.$broadcast('countAllFilters', args);
					return args.configured + ' / ' + args.all;
				};

				scope.applyFilter = function() {
					scope.$broadcast('applyFilter');
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

				this.getCurrentContext = function() {
					return scope.currentContext;
				};

				var currentContext = Navigation.getCurrentContext();
				if (currentContext) {
					var data = currentContext.savedData('filter_' + scope.model);
					if (angular.isObject(data) && data.hasOwnProperty('filter'))
					{
						scope.currentContext = currentContext;
						var search = scope.filter.search;
						angular.extend(scope.filter, data.filter);
						scope.filter.search = search;
						Navigation.popContext(currentContext);
					}
				}
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

				scope.$on('Navigation.saveContext', function (event, args) {
					var label = Models.getModelLabel(scope.model);
					args.context.label(label);
					var data = {filter: scope.filter};
					args.context.savedData('filter_' + scope.model, data);
				});
			}
		};
	}]);

	app.directive('rbsDocumentFilterGroup', ['$compile', function($compile) {
		return {
			restrict: 'A',
			require: '^rbsDocumentFilterContainer',
			templateUrl : 'Rbs/Admin/js/directives/document-filter-group.twig',
			scope: {
				filter : '=',
				parentOperator : '='
			},
			link: function(scope, element, attrs, containerController) {
				containerController.linkNode(scope);
				scope.definitionToAdd = null;

				scope.filter.operator = scope.parentOperator == 'AND' ? 'OR' : 'AND';
				scope.filter.name = 'group';
				if (!angular.isArray(scope.filter.filters))
				{
					scope.filter.filters = [];
				}
				if (!angular.isObject(scope.filter.parameters))
				{
					scope.filter.parameters = {};
				}

				scope.isAnd = function() {
					return scope.filter.operator == 'AND';
				};

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
			restrict: 'A',
			require: '^rbsDocumentFilterContainer',
			templateUrl : 'Rbs/Admin/js/directives/document-filter-unknown.twig',
			scope: {
				filter : '=', contextKey: "@"
			},
			link: function(scope, element, attrs, containerController) {
				containerController.linkNode(scope);

				scope.isConfigured = function() {
					return false;
				};

				scope.$on('countAllFilters', function(event, args) {
					args.all++;
					if (scope.isConfigured()) {
						args.configured++;
					}
				});
			}
		};
	});

	app.directive('rbsDocumentFilterPropertyBoolean', function() {
		return {
			restrict: 'A',
			require: '^rbsDocumentFilterContainer',
			templateUrl : 'Rbs/Admin/js/directives/document-filter-property-boolean.twig',
			scope: {
				filter : '=', contextKey: "@"
			},
			link: function(scope, element, attrs, containerController) {
				containerController.linkNode(scope);

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
			}
		};
	});

	app.directive('rbsDocumentFilterPropertyDatetime', function() {
		return {
			restrict: 'A',
			require: '^rbsDocumentFilterContainer',
			templateUrl : 'Rbs/Admin/js/directives/document-filter-property-datetime.twig',
			scope: {
				filter : '=', contextKey: "@"
			},
			link: function(scope, element, attrs, containerController) {
				containerController.linkNode(scope);

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
			}
		};
	});

	app.directive('rbsDocumentFilterPropertyDocument', function() {

		return {
			restrict: 'A',
			require: '^rbsDocumentFilterContainer',
			templateUrl : 'Rbs/Admin/js/directives/document-filter-property-document.twig',
			scope: {
				filter : '=', contextKey: "@"
			},
			link: function(scope, element, attrs, containerController) {
				containerController.linkNode(scope);

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
			}
		};
	});

	app.directive('rbsDocumentFilterPropertyNumber', function() {
		return {
			restrict: 'A',
			require: '^rbsDocumentFilterContainer',
			templateUrl : 'Rbs/Admin/js/directives/document-filter-property-number.twig',
			scope: {
				filter : '=', contextKey: "@"
			},
			link: function(scope, element, attrs, containerController) {
				containerController.linkNode(scope);
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
			}
		};
	});

	app.directive('rbsDocumentFilterPropertyString', function() {
		return {
			restrict: 'A',
			require: '^rbsDocumentFilterContainer',
			templateUrl : 'Rbs/Admin/js/directives/document-filter-property-string.twig',
			scope: {
				filter : '=', contextKey: "@"
			},
			link: function(scope, element, attrs, containerController) {
				containerController.linkNode(scope);
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
			}
		};
	});

})(window.jQuery);