/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
(function () {

	"use strict";

	/**
	 * @ngdoc directive
	 * @id RbsChange.directive:rbsDocumentPublicationSection
	 * @name Document publication editor panel
	 * @element fieldset
	 * @restrict A
	 *
	 * @description
	 * Used to display the <em>Publication</em> section in Document editors.
	 *
	 * @param {String=} rbs-document-publication-section-help Additional help message.
	 *
	 * @example
	 * <pre>
	 *     <fieldset data-rbs-editor-section="publication"
	 *        data-editor-section-label="Publication"
	 *        data-rbs-document-publication-section="">
	 *     </fieldset>
	 * </pre>
	 */
	angular.module('RbsChange').directive('rbsDocumentPublicationSection', ['RbsChange.Settings', function (Settings) {
		return {
			restrict: 'A',
			templateUrl: 'Rbs/Admin/js/directives/document-publication-section.twig',
			replace: false,
			scope: true,
			link: function (scope, iElement, iAttrs) {
				scope.hasSpecificHelp = false;
				var _timeZone = Settings.get('TimeZone');

				function now() {
					return moment.utc().tz(_timeZone);
				}

				function setPublicationClasses() {
					if (scope.document && scope.document.startPublication && scope.document.endPublication) {
						var startAct = moment(scope.document.startPublication);
						var endAct = moment(scope.document.endPublication);

						if (endAct.diff(startAct, 'weeks', true) == 1) {
							scope.publicationOffsetClass = {"1w": "active", "2w": null, "1M": null};
						} else {
							if (endAct.diff(startAct, 'weeks', true) == 2) {
								scope.publicationOffsetClass = {"1w": null, "2w": "active", "1M": null};
							} else {
								if (endAct.diff(startAct, 'months', true) == 1) {
									scope.publicationOffsetClass = {"1w": null, "2w": null, "1M": "active"};
								} else {
									scope.publicationOffsetClass = {"1w": null, "2w": null, "1M": null};
								}
							}
						}
					}
					else {
						scope.publicationOffsetClass = {"1w": null, "2w": null, "1M": null};
					}
				}

				scope.$on('Change:TimeZoneChanged', function (event, tz) {
					_timeZone = tz;
				});

				scope.publicationNow = function () {
					scope.document.startPublication = now().toDate();
				};

				scope.publicationTomorrow = function () {
					scope.document.startPublication = now().startOf('d').add('d', 1).toDate();
				};

				scope.publicationNextMonday = function () {
					scope.document.startPublication = now().add('w', 1).startOf('w').startOf('d').toDate();
				};

				scope.publicationNextMonth = function () {
					scope.document.startPublication = now().add('M', 1).startOf('M').startOf('d').toDate();
				};

				scope.$watch('document.startPublication', function (newValue, oldValue) {
					if (newValue != oldValue && angular.isObject(scope.publicationOffsetClass)) {
						if (newValue) {
							if (scope.publicationOffsetClass['1w']) {
								scope.endPublicationOneWeek();
							} else {
								if (scope.publicationOffsetClass['2w']) {
									scope.endPublicationTwoWeeks();
								} else {
									if (scope.publicationOffsetClass['1M']) {
										scope.endPublicationOneMonth();
									}
								}
							}
						}
						else {
							setPublicationClasses();
						}
					}
				});

				scope.$watch('document.endPublication', function () {
					setPublicationClasses();
				});

				scope.endPublicationOneWeek = function (toggle) {
					if (toggle && scope.publicationOffsetClass && scope.publicationOffsetClass['1w']) {
						scope.publicationOffsetClass['1w'] = null;
						return;
					}
					scope.document.endPublication = moment(scope.document.startPublication).add('w', 1).toDate();
					scope.publicationOffsetClass = {"1w": "active", "2w": null, "1M": null};
				};

				scope.endPublicationTwoWeeks = function (toggle) {
					if (toggle && scope.publicationOffsetClass && scope.publicationOffsetClass['2w']) {
						scope.publicationOffsetClass['2w'] = null;
						return;
					}
					scope.document.endPublication = moment(scope.document.startPublication).add('w', 2).toDate();
					scope.publicationOffsetClass = {"1w": null, "2w": "active", "1M": null};
				};

				scope.endPublicationOneMonth = function (toggle) {
					if (toggle && scope.publicationOffsetClass && scope.publicationOffsetClass['1M']) {
						scope.publicationOffsetClass['1M'] = null;
						return;
					}
					scope.document.endPublication = moment(scope.document.startPublication).add('M', 1).toDate();
					scope.publicationOffsetClass = {"1w": null, "2w": null, "1M": "active"};
				};

				scope.endPublicationTomorrow = function () {
					scope.document.endPublication = moment().endOf('d').toDate();
				};

				scope.endPublicationEndOfWeek = function () {
					scope.document.endPublication = moment().endOf('w').toDate();
				};

				scope.endPublicationEndOfMonth = function () {
					scope.document.endPublication = moment().endOf('M').toDate();
				};

				if (angular.isDefined(iAttrs['rbsDocumentPublicationSectionHelp'])
					&& iAttrs['rbsDocumentPublicationSectionHelp'] !== "")
				{
					scope.hasSpecificHelp = true;
					scope.specificHelp = iAttrs['rbsDocumentPublicationSectionHelp'];
				}

				scope.showPublicationSections = function () {
					if (!scope.document) {
						return false;
					} else {
						if (!scope.modelInfo || !('publicationSections' in scope.modelInfo.properties)) {
							return false;
						} else {
							if (scope.currentLCID && scope.currentLCID !== scope.document.refLCID) {
								return false;
							}
						}
					}
					return true;
				};
			}
		}
	}]);
})();