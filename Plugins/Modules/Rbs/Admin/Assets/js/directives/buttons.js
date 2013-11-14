(function () {

	"use strict";

	var app = angular.module('RbsChange');

	app.directive('rbsButtonRemove', function () {
		return {
			'restrict'   : 'E',
			'template'   : '<button type="button" class="btn btn-danger"><i class="icon-remove"></i> <span ng-transclude=""></span></button>',
			'replace'    : true,
			'transclude' : true
		};
	});

	app.directive('rbsButtonDelete', function () {
		return {
			'restrict'   : 'E',
			'template'   : '<button type="button" class="btn btn-danger"><i class="icon-trash"></i> <span ng-transclude=""></span></button>',
			'replace'    : true,
			'transclude' : true
		};
	});

	app.directive('rbsButtonHelpToggle', ['RbsChange.i18n', function (i18n) {
		return {
			'restrict' : 'E',
			'template' : '<button type="button" class="btn btn-info" ng-class="{\'active\':value}" ng-click="onClick()"><i ng-class="{false: \'icon-chevron-down\', true: \'icon-chevron-up\'}[value]"></i> <span ng-bind-html="text"></span></button>',
			'replace'    : true,
			'scope' : {
				'value' : '='
			},
			'link' : function (scope, element, attrs) {
				if (angular.isUndefined(scope.value)) {
					scope.value = false;
				}
				scope.onClick = function () {
					scope.value = ! scope.value;
				};
				if (element.is('[text]')) {
					scope.text = attrs.text;
				} else {
					scope.text = i18n.trans('m.rbs.admin.adminjs.help | ucf');
				}
			}
		};
	}]);

	app.directive('rbsButtonClose', function () {
		return {
			'restrict'   : 'E',
			'template'   : '<button type="button" class="close pull-right">&times;</button>',
			'replace'    : true
		};
	});

})();