(function () {

	"use strict";

	var app = angular.module('RbsChange'),
		TAG_COLORS = ['red', 'orange', 'yellow', 'green', 'blue', 'purple', 'gray'];

	app.config(['$routeProvider', function ($routeProvider) {
		$routeProvider

		// Users

		. when(
			'/Rbs/Tag',
			{
				templateUrl : 'Rbs/Tag/Tag/list.twig',
				reloadOnSearch : false
			})

		. when(
			'/Rbs/Tag/:id',
			{
				templateUrl : 'Rbs/Tag/Tag/form.twig',
				reloadOnSearch : false
			})

		;
	}]);


	/**
	 * <rbs-tag-color-selector/>
	 */
	app.directive('rbsTagColorSelector', function () {

		return {
			restrict : 'E',
			require  : 'ngModel',
			scope    : true,
			template :
				'<a ng-repeat="color in colors" class="tag tag-color-setter (=color=)" href ng-click="setColor($event, color)">' +
				'<i ng-class="{true:\'icon-circle\', false:\'icon-circle-blank\'}[selectedColor == color]" class="(= iconSize =)"></i>' +
				'</a>',

			link : function (scope, elm, attrs, ngModel) {
				scope.colors = TAG_COLORS;

				ngModel.$render = function () {
					scope.selectedColor = ngModel.$viewValue;
				};

				scope.iconSize = attrs.iconSize || 'icon-large';

				scope.setColor = function ($event, color) {
					ngModel.$setViewValue(color);
					ngModel.$render();
				};
			}
		};

	});


	/**
	 * <rbs-tag/>
	 */
	app.directive('rbsTag', ['RbsChange.i18n', function (i18n) {
		return {
			restrict : 'A',
			replace  : true,
			template :
				'<span class="tag (= rbsTag.color =)">' +
					'<i class="icon-user" ng-if="rbsTag.userTag" title="' + i18n.trans('m.rbs.tag.admin.js.usertag-text | ucf') + '" style="border-right:1px dotted white; padding-right:4px"></i> ' +
					'<i class="icon-exclamation-sign" ng-if="rbsTag.unsaved" title="' + i18n.trans('m.rbs.tag.admin.js.tag-not-saved | ucf') + '" style="border-right:1px dotted white; padding-right:4px"></i> ' +
					'(= rbsTag.label =)' +
					'<a ng-if="canBeRemoved" href tabindex="-1" class="tag-delete" ng-click="remove()"><i class="icon-remove"></i></a>' +
				'</span>',

			scope : {
				'rbsTag'   : '=',
				'onRemove' : '&'
			},

			link : function (scope, elm) {
				elm.addClass('tag');
				scope.canBeRemoved = elm.is('[on-remove]');
				scope.remove = function () {
					scope.onRemove();
				};
				scope.$watch('rbsTag', function (value) {
					if (value) {
						elm.addClass(value.color);
						if (value.unsaved) {
							elm.addClass('new');
						} else {
							elm.removeClass('new');
						}
						if (value.used) {
							elm.addClass('opacity-half');
						} else {
							elm.removeClass('opacity-half');
						}
					}
				}, true);
			}
		};
	}]);


	app.config(['$provide', function ($provide) {
		$provide.decorator('RbsChange.UrlManager', ['$delegate', function ($delegate) {

			// Users
			$delegate.register('Rbs_Tag_Tag', {
				'form'  : '/Rbs/Tag/:id',
				'list'  : '/Rbs/Tag',
				'tree'  : '/Rbs/Tag/?tn=:id'
			});

			return $delegate;

		}]);
	}]);

})();