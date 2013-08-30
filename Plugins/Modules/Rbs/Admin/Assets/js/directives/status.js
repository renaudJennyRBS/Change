(function () {

	"use strict";

	/**
	 * @name status
	 * @description Display the status of a document.
	 *
	 * @attribute code
	 *
	 * @example: <code><status ng-model="document"/></code>
	 */
	angular.module('RbsChange').directive('status', ['RbsChange.Utils', 'RbsChange.i18n', function (Utils, i18n) {

		return {

			restrict : 'E',
			template : '<div title="(=tooltip=)" class="bullet-status (=document.publicationStatus=)"><div class="overlay correction" ng-if="correction">C</div></div>',
			require: '?ngModel',
			replace: true,
			scope : {
				document : '=ngModel'
			},

			link : function (scope, elm, attrs) {
				if (scope.document) {
					scope.status = scope.document.publicationStatus;
					scope.correction = Utils.hasCorrection(scope.document);
				} else {
					scope.status = attrs.value;
					scope.correction = false;
				}

				scope.$watch('document', function (doc) {
					if (doc && doc.publicationStatus) {
						scope.tooltip = i18n.trans('m.rbs.admin.admin.js.status-' + angular.lowercase(doc.publicationStatus));
						scope.correction = Utils.hasCorrection(doc);
						if (scope.correction) {
							scope.tooltip += ' (avec correction)';
						}
					} else {
						elm.hide();
					}
				}, true);
			}
		};
	}]);

})();