(function () {

	"use strict";

	/**
	 * @name status
	 * @description Display the status of a document.
	 * @example <code><status ng-model="document"/></code>
	 */
	angular.module('RbsChange').directive('rbsStatus', ['RbsChange.Utils', 'RbsChange.i18n', function (Utils, i18n)
	{
		return {

			restrict : 'E',
			template : '<div title="(=tooltip=)" ng-show="publicationStatus" class="bullet-status (=publicationStatus=)"><div class="overlay correction" ng-if="correction">C</div></div>',
			require: '?ngModel',
			replace: true,
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