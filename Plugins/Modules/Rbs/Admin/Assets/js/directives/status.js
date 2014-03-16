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
	 * @name RbsChange.directive:rbs-status
	 * @restrict E
	 *
	 * @description
	 * Displays a bullet that indicates the publication status of a Document.
	 *
	 * @param {Document} ng-model The Document.
	 */
	angular.module('RbsChange').directive('rbsStatus', ['RbsChange.Utils', 'RbsChange.i18n', function (Utils, i18n)
	{
		return {
			restrict : 'E',
			template : '<div title="(=tooltip=)" ng-show="publicationStatus" class="bullet-status (=publicationStatus=)"><div class="overlay correction" ng-if="correction">C</div></div>',
			require : '?ngModel',
			replace : true,
			scope : {
				document : '=ngModel'
			},

			link : function (scope, iElement)
			{
				scope.$watchCollection('document', function (doc)
				{
					if (doc) {
						if (! doc.publicationStatus && Utils.hasLocalCopy(doc)) {
							scope.publicationStatus = doc.META$.localCopy.publicationStatus;
						}
						else {
							scope.publicationStatus = doc.publicationStatus;
						}

						if (scope.publicationStatus)
						{
							scope.tooltip = i18n.trans('m.rbs.admin.adminjs.status_' + angular.lowercase(scope.publicationStatus));
							scope.correction = Utils.hasCorrection(doc);
							if (scope.correction) {
								scope.tooltip += ' (avec correction)';
							}
						}
						else {
							iElement.hide();
						}
					}
					else {
						iElement.hide();
					}
				});
			}
		};
	}]);

})();