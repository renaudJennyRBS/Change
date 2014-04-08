/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
(function() {
	"use strict";

	/**
	 * @ngdoc directive
	 * @name RbsChange.directive:rbs-document-activation-section
	 * @restrict A
	 *
	 * @description
	 * Used to display the <em>Activation</em> section in Document editors.
	 *
	 * @example
	 * <pre>
	 *     <fieldset data-rbs-editor-section="activation"
	 *        data-editor-section-label="{{ i18nAttr('m.rbs.admin.admin.activation_properties', ['ucf']) }}"
	 *        data-rbs-document-activation-section="">
	 *     </fieldset>
	 * </pre>
	 */
	angular.module('RbsChange').directive('rbsDocumentActivationSection', ['RbsChange.Settings', function(Settings) {
		return {
			restrict: 'A',
			templateUrl: 'Rbs/Admin/js/directives/document-activation-section.twig',
			replace: false,
			scope: true,
			link : function (scope, elm, attrs) {
				var _timeZone = Settings.get('TimeZone');

				function now () {
					return moment.utc().tz(_timeZone);
				}

				function toIso (date) {
					return date.zone('+0000').format('YYYY-MM-DDTHH:mm:ssZZ')
				}

				function setActivationClasses() {
					if (scope.document && scope.document.startActivation && scope.document.endActivation) {
						var startAct = moment(scope.document.startActivation);
						var endAct = moment(scope.document.endActivation);

						if (endAct.diff(startAct, 'weeks', true) == 1) {
							scope.activationOffsetClass = {"1w": "active", "2w" : null, "1M": null};
						} else if (endAct.diff(startAct, 'weeks', true) == 2) {
							scope.activationOffsetClass = {"1w": null, "2w" : "active", "1M": null};
						} else if (endAct.diff(startAct, 'months', true) == 1) {
							scope.activationOffsetClass = {"1w": null, "2w" : null, "1M": "active"};
						} else {
							scope.activationOffsetClass = {"1w": null, "2w" : null, "1M": null};
						}
					}
					else {
						scope.activationOffsetClass = {"1w": null, "2w" : null, "1M": null};
					}
				}

				scope.$on('Change:TimeZoneChanged', function (event, tz) {
					_timeZone = tz;
				});

				//2014-04-08T07:29:09+0000
				scope.activationNow = function(){
					scope.document.startActivation = toIso(now());
				};

				scope.activationTomorrow = function(){
					scope.document.startActivation = toIso(now().startOf('d').add('d', 1));
				};

				scope.activationNextMonday = function(){
					scope.document.startActivation = toIso(now().add('w', 1).startOf('w').startOf('d'));
				};

				scope.activationNextMonth = function(){
					scope.document.startActivation = toIso(now().add('M', 1).startOf('M').startOf('d'));
				};

				scope.$watch('document.startActivation', function(newValue, oldValue){
					if (newValue != oldValue && angular.isObject(scope.activationOffsetClass)) {
						if (newValue) {
							if (scope.activationOffsetClass['1w']) {
								scope.endActivationOneWeek();
							} else if (scope.activationOffsetClass['2w']) {
								scope.endActivationTwoWeeks();
							} else if (scope.activationOffsetClass['1M']) {
								scope.endActivationOneMonth();
							}
						} else {
							setActivationClasses();
						}
					}
				});

				scope.$watch('document.endActivation', function(){
					setActivationClasses();
				});

				scope.endActivationOneWeek = function(toggle){
					if (toggle && scope.activationOffsetClass && scope.activationOffsetClass['1w']) {
						scope.activationOffsetClass['1w'] = null;
						return;
					}
					scope.document.endActivation = toIso(moment(scope.document.startActivation).add('w', 1));
					scope.activationOffsetClass = {"1w":"active", "2w" : null, "1M": null};
				};

				scope.endActivationTwoWeeks = function(toggle){
					if (toggle && scope.activationOffsetClass && scope.activationOffsetClass['2w']) {
						scope.activationOffsetClass['2w'] = null;
						return;
					}
					scope.document.endActivation = toIso(moment(scope.document.startActivation).add('w', 2));
					scope.activationOffsetClass = {"1w":null, "2w" : "active", "1M": null};
				};

				scope.endActivationOneMonth = function(toggle) {
					if (toggle && scope.activationOffsetClass && scope.activationOffsetClass['1M']) {
						scope.activationOffsetClass['1M'] = null;
						return;
					}
					scope.document.endActivation = toIso(moment(scope.document.startActivation).add('M', 1));
					scope.activationOffsetClass = {"1w":null, "2w" : null, "1M": "active"};
				};

				scope.endActivationTomorrow = function(){
					scope.document.endActivation = toIso(moment().endOf('d'));
				};

				scope.endActivationEndOfWeek = function(){
					scope.document.endActivation = toIso(moment().endOf('w'));
				};

				scope.endActivationEndOfMonth = function(){
					scope.document.endActivation = toIso(moment().endOf('M'));
				};
			}
		};
	}]);
})();