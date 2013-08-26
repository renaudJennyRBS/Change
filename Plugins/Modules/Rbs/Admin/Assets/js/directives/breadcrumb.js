(function () {

	"use strict";

	var modelIcons = {
		//'Rbs_Website_Website' : 'icon-home'
	};

	angular.module('RbsChange').directive('breadcrumb', ['$location', 'RbsChange.Utils', 'RbsChange.i18n', function ($location, Utils, i18n) {

		return {
			restrict : 'E',

			scope : true,

			template : '<ul class="breadcrumb"></ul>',

			replace : true,

			link : function (scope, elm) {

				function getLabelSuffix () {
					var search = $location.search(), page;
					if ('offset' in search && 'limit' in search) {
						page = 1 + (search.offset / search.limit);
						return '<em> &mdash; page ' + page + '</em>';
					}
					return '';
				}

				function getEntryHtml (entry, disabled, last, cssClass) {

					var item, html, icon;

					if (Utils.isDocument(entry)) {
						item = [ entry.label ];
						if (entry.treeUrl()) {
							item.push(entry.treeUrl());
						} else {
							item.push("javascript:;");
						}
						if (modelIcons[entry.model]) {
							item.push(modelIcons[entry.model]);
						}
					} else if (angular.isString(entry)) {
						item = [ entry ];
					} else {
						item = entry;
					}

					icon = item.length === 3 && item[2] ? '<i class="' + item[2] + '"></i> ' : '';

					if (disabled || last) {
						html = '<li class="active';
						if (cssClass) {
							html += ' ' + cssClass;
						}
						html += '"><span>' + icon + item[0];
						if (last) {
							html += getLabelSuffix();
						}
						html += '</span></li>';
					} else {
						html = '<li' + (cssClass ? (' class="' + cssClass + '"') : '') + '><a href="' + item[1] + '">' + icon + item[0] + '</a></li>';
					}

					return html;
				}

				function update (breadcrumbData) {
					var html, i;
					html = getEntryHtml(
						[i18n.trans('m.rbs.admin.admin.js.home | ucf'), ""],
						breadcrumbData.disabled,
						// Last element?
						breadcrumbData.location.length === 0 && breadcrumbData.path.length === 0 && ! breadcrumbData.resource,
						'location'
					);

					for (i = 0; i < breadcrumbData.location.length; i++) {
						if (angular.isDefined(breadcrumbData.location[i])) {
							html += getEntryHtml(
								breadcrumbData.location[i],
								breadcrumbData.disabled,
								// Last element?
								i === (breadcrumbData.location.length-1) && breadcrumbData.path.length === 0 && ! breadcrumbData.resource,
								'location'
							);
						}
					}

					for (i = 0; i < breadcrumbData.path.length; i++) {
						if (angular.isDefined(breadcrumbData.path[i])) {
							html += getEntryHtml(
								breadcrumbData.path[i],
								false,
								// Last element?
								i === (breadcrumbData.path.length-1) && ! breadcrumbData.resource
							);
						}
					}

					if (breadcrumbData.resource) {
						html += getEntryHtml(breadcrumbData.resource, breadcrumbData.disabled, true);
					}

					if (breadcrumbData.resourceModifier) {
						html += getEntryHtml(breadcrumbData.resourceModifier, breadcrumbData.disabled, true);
					}

					elm.html(html);
				}

				scope.$on('Change:TreePathChanged', function (event, breadcrumbData) {
					update(breadcrumbData);
				});

			}

		};

	}]);


	angular.module('RbsChange').directive('rbsBreadcrumbConfig', ['RbsChange.Breadcrumb', function (Breadcrumb) {

		return {
			restrict : 'E',
			scope : true,
			require  : '^rbsWorkspaceConfig',

			controller : ['$scope', function ($scope) {

				var bc = {
					'Location' : [],
					'Path' : []
				};

				this.add = function (type, index, text, href) {
					bc[type][index] = [text, href];
					if (bc[type].length === $scope.counts[type]) {
						Breadcrumb['set' + type](bc[type]);
					}
				};

			}],

			compile : function (tElement) {
				tElement.hide();
				return function linkFn (scope, element, attrs) {
					scope.counts = {
						'Location' : element.find('location').length,
						'Path' : element.find('path').length
					};
				};
			}
		};

	}]);

	angular.module('RbsChange').directive('location', function () {
		return {
			restrict : 'E',
			require  : '?^rbsBreadcrumbConfig',
			link : function (scope, element, attrs, rbsBreadcrumbUpdate) {
				if (rbsBreadcrumbUpdate) {
					attrs.$observe('href', function (href) {
						rbsBreadcrumbUpdate.add('Location', element.index(), element.text(), href);
					});
				}
			}
		};
	});

	angular.module('RbsChange').directive('path', function () {
		return {
			restrict : 'E',
			require  : '?^rbsBreadcrumbConfig',
			link : function (scope, element, attrs, rbsBreadcrumbUpdate) {
				if (rbsBreadcrumbUpdate) {
					attrs.$observe('href', function (href) {
						rbsBreadcrumbUpdate.add('Path', element.index()-element.prevAll('location').length, element.text(), href);
					});
				}
			}
		};
	});



	angular.module('RbsChange').directive('rbsWorkspaceConfig', ['RbsChange.Workspace', 'RbsChange.MainMenu', function (Workspace, MainMenu) {

		return {
			restrict : 'E',
			scope : true,

			controller : ['$scope', '$attrs', function ($scope, $attrs) {
				if ($attrs.menu) {
					MainMenu.loadModuleMenu($attrs.menu);
				}
			}]
		};

	}]);

})();