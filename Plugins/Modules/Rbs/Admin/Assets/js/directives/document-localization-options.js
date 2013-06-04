(function () {

	var app = angular.module('RbsChange');

	var DIRECTIVE_NAME = 'documentLocalizationOptions';

	app.directive(DIRECTIVE_NAME, ['$http', '$timeout', '$route', '$location', 'RbsChange.Loading', 'RbsChange.Dialog', 'RbsChange.UrlManager', function ($http, $timeout, $route, $location, Loading, Dialog, UrlManager) {

		return {
			restrict : 'A',

			templateUrl: 'Rbs/Admin/js/directives/document-localization-options.html',

			scope : {
				document: '=' + DIRECTIVE_NAME,
				LCID: '=language',
				refLCID: '=referenceLanguage'
			},

			link : function (scope, element, attrs) {


				function updateLanguages () {
					if (angular.isArray(scope.languages)) {
						angular.forEach(scope.languages, function (language) {
							if (language.isReference) {
								scope.referenceLanguage = language;
							}
							if (language.id === scope.LCID) {
								scope.selectedLanguage = language;
							}
							if (language.id === scope.refLCID) {
								scope.fromLanguage = language;
							}
						});
					} else {
						console.error("Error while loading languages for document ", scope.document);
					}
					if (!scope.selectedLanguage) {
						scope.selectedLanguage = scope.languages[0].id;
					}
				}


				scope.referenceLanguage = null;

				scope.selectLanguage = function (language) {
					if (language.isReference) {
						scope.fromLanguage = null;
					} else {
						if (scope.fromLanguage === language) {
							scope.fromLanguage = scope.referenceLanguage;
						} else {
							scope.fromLanguage = scope.fromLanguage || scope.referenceLanguage;
						}
					}
					scope.selectedLanguage = language;
				};

				scope.selectFromLanguage = function (language) {
					if (language !== scope.selectedLanguage && ! scope.selectedLanguage.isReference) {
						scope.fromLanguage = language;
					}
				};

				scope.closeEmbeddedModal = function () {
					Dialog.closeEmbedded();
				};

				scope.applySelectedLanguage = function () {
					// /!\ refLCID should be changed first due to the loading process in FormsManager.
					//scope.refLCID = scope.fromLanguage ? scope.fromLanguage.id : null;
					//scope.LCID = scope.selectedLanguage.id;

					Dialog.closeEmbedded();

					$location.path(UrlManager.getI18nUrl(
						scope.document,
						scope.selectedLanguage.id,
						scope.fromLanguage ? scope.fromLanguage.id : null
					));

/*
					console.log($route);
					$route.current.params.LCID = scope.selectedLanguage.id;
					if (scope.fromLanguage) {
						$route.current.params.fromLCID = scope.fromLanguage.id;
					}
					$route.reload();
*/

				};

				scope.selectReferenceLanguage = function (language) {
					scope.referenceLanguage.isReference = false;
					scope.referenceLanguage = language;
					scope.referenceLanguage.isReference = true;
					$timeout(updateLanguages);
				};


				// FIXME Model name
				//Loading.start("Chargement de la liste des traductions du document...");

				scope.languages = scope.document.META$.locales;
				scope.referenceLanguage = scope.document.refLCID;
				updateLanguages();
				/*
				var url = 'api/rest.php/resources/Change/Module/pages/' + scope.document.id + '/languages';
				$http.get(url).then(function (response) {
					scope.languages = response.data;
					updateLanguages();
					Loading.stop();
				});
				*/
			}
		};

	}]);

})();