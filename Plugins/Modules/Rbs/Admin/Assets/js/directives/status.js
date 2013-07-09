(function () {

	/**
	 * @name status
	 * @description Display the status of a document.
	 *
	 * @attribute code
	 *
	 * @example: <code><status ng-model="document.status"/></code>
	 */
	angular.module('RbsChange').directive('status', ['RbsChange.Utils', function (Utils) {

		// FIXME Put this somewhere else (i18n)
		var messages = {
			'DRAFT'       : "Brouillon",
			'PUBLISHABLE' : "Publiable",
			'DEACTIVATED' : "Désactivé",
			'VALIDATION'  : "Workflow en cours",
			'ACTIVE'      : "Activé"
		};


		return {
			restrict : 'E',

			template : '<div title="{{tooltip}}" class="bullet-status {{document.publicationStatus}}" href="Rbs/Admin/help/status" help="#helpListBottom"><div class="overlay correction" ng-show="correction">C</div></div>',

			require: '?ng-model',

			replace: true,

			scope : {
				document: '=ngModel'
			},

			link : function (scope, elm, attrs, ngModel) {
				if (scope.document) {
					scope.status = scope.document.publicationStatus;
					scope.correction = Utils.hasCorrection(scope.document);
				} else {
					scope.status = attrs.value;
					scope.correction = false;
				}

				scope.$watch('document', function (doc) {
					if (doc && doc.publicationStatus) {
						scope.tooltip = messages[doc.publicationStatus];
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