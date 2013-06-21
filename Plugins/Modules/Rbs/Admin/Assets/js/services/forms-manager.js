(function ($) {

	"use strict";

	var app = angular.module('RbsChange');


	app.service('RbsChange.FormsManager', ['$compile', '$timeout', '$q', '$rootScope', '$routeParams', '$location', '$resource', 'RbsChange.Breadcrumb', 'RbsChange.Dialog', 'RbsChange.Loading', 'RbsChange.MainMenu', 'RbsChange.REST', 'RbsChange.Utils', 'RbsChange.ArrayUtils', 'RbsChange.i18n', function ($compile, $timeout, $q, $rootScope, $routeParams, $location, $resource, Breadcrumb, Dialog, Loading, MainMenu, REST, Utils, ArrayUtils, i18n) {

		var	$ws = $('#workspace'),
			cascadeContextStack = [],
			cascadedElement = null,
			self = this,
			idStack = [];

		/**
		 * When the route changes, we need to clean up any cascading process.
		 */
		$rootScope.$on('$routeChangeSuccess', function () {
			cascadeContextStack = [];
		});

		$rootScope.$on('$routeChangeStart', function () {
			Breadcrumb.unfreeze();
		});

		this.startEditSession = function startEditSessionFn (doc) {
			idStack.push(doc.id);
		};

		this.stopEditSession = function stopEditSessionFn () {
			idStack.pop();
		};

		/**
		 * Open a new form from the given formUrl. A new FormContext will be created directly
		 * within the form when it is loaded.
		 * @param formUrl The URL of the form to load.
		 * @param queryParam Query parameters (hash) to retrieve the existing document (if any).
		 * @param saveCallback The callback to be called when the sub-$resource will be saved.
		 * @param message The message to display in the current form when it is collapsed.
		 *
		 * @return String null if OK, message if not OK.
		 */
		this.cascade = function (formUrl, queryParam, saveCallback, message) {
			var	$form,
				contents;

			// Check circular cascade:
			if (queryParam && ArrayUtils.inArray(queryParam.id, idStack) !== -1) {
				return i18n.trans('m.rbs.admin.admin.js.document-is-already-being-edited | ucf');
			}

			// Freeze the Breadcrumb to prevent any other controller from modifying it.
			Breadcrumb.freeze();

			// Create cascade context.
			cascadeContextStack.push({
				'saveCallback' : saveCallback,
				'queryParam'   : queryParam
			});
			if (queryParam) {
				idStack.push(queryParam.id);
			}

			// Slides up the current form.
			$form = $ws.children('.document-form').last();
			$form.slideUp('fast');

			// Load and insert the new cascaded form.
			$.get(formUrl, function (html) {
				// Create a new isolated scope for the new form.
				var scope = angular.element($ws).scope().$new(true);
				if (queryParam && queryParam.lang) {
					scope.language = queryParam.lang;
				}
				// Compile the HTML and insert it into the #workspace.
				// The insertion of new content is a bit tricky. We need to:
				// * Create a new Element with jQuery,
				// * Append that new Element in the DOM,
				// * Compile it with the Angular $compile service.
				$ws.append('<div class="cascading-forms-collapsed">' + message + '</div>');
				contents = $(html);
				$ws.append(contents);
				$compile(contents)(scope);
				cascadedElement = contents;
				cascadedElement.hide();

				MainMenu.pushContents($ws.find('.document-editor').last().scope());

				self.updateCollapsedForms();
				$ws.find(':input').first().focus();
			});

			return null;
		};


		/**
		 * Returns true if we are in a cascading process, false otherwise.
		 *
		 * @returns {Boolean}
		 */
		this.isCascading = function () {
			return cascadeContextStack.length > 0;
		};


		/**
		 * Uncascade (cancel) the current form and go back to the previous form,
		 * without any changes on it.
		 */
		this.uncascade = function (doc) {
			var	ctx = cascadeContextStack.pop(),
				$form;

			console.log("uncascade(): cascadeContext=", ctx);
			if (ctx && doc !== null && angular.isFunction(ctx.saveCallback)) {
				ctx.saveCallback(doc);
			}

			cascadedElement = null;
			idStack.pop();

			// Remove the last from and destroy its associated scope.
			$form = $ws.children('.document-form').last();
			angular.element($form).scope().$destroy();
			$form.remove();
			$ws.children('.cascading-forms-collapsed').last().remove();

			// Display the last form.
			$form = $ws.children('.document-form').last();
			$form.fadeIn('fast');

			MainMenu.popContents();

			// If all cascades are finished, unfreeze the Breadcrumb to allow modifications on it.
			if (cascadeContextStack.length === 0) {
				Breadcrumb.unfreeze();
			}

			this.updateCollapsedForms();
		};


		this.getCurrentContext = function () {
			return cascadeContextStack.length ? cascadeContextStack[cascadeContextStack.length-1] : null;
		};


		this.updateCollapsedForms = function () {
			// "Shrink" older forms.
			var collapsed = $ws.find('.cascading-forms-collapsed');
			collapsed.each(function (i) {
				$(this).removeClass('cascading-forms-last').css({
					margin    : '0 ' + ((collapsed.length - i)*15)+'px',
					opacity   : (0.7 + ((i+1)/collapsed.length*0.3)),
					zIndex    : i + 1,
					fontSize  : ((1 + ((i+1)/collapsed.length*0.2))*100)+'%',
					lineHeight: ((1 + ((i+1)/collapsed.length*0.2))*100)+'%'
				});
			});
			if (this.isCascading()) {
				$ws.children('.document-form').last().addClass('cascading-forms-last');
			}
		};



		this.initResource = function (scope, rest) {
			var promise,
			    self = this,
			    params,
				q,
				ctx;

			// Install event handlers.

			// Events for corrections handling:
			// 'correctionRemoved' and 'correctionChanged' events are triggered by the CorrectionViewer directive.
			// When the FormsManager (this class) is notified of one of these events, it broadcasts the
			// 'updateDocumentProperties' event down to the child scopes.
			// The Editor listens to it and updates its 'document' Model consequently, using the properties that
			// come as the event's parameters.
			function correctionChangedHandler (event, properties) {
				scope.$broadcast('Change:UpdateDocumentProperties', properties);
			}
			scope.$on('Change:CorrectionChanged', correctionChangedHandler);
			scope.$on('Change:CorrectionRemoved', correctionChangedHandler);

			scope.$on('Change:DocumentUpdated', function (event, doc) {
				scope.document = angular.extend(scope.document, doc);
			});


			// Init scope data and functions.

			scope.document = {};
			scope.language = $routeParams.LCID || 'fr_FR'; // FIXME
			scope.parentId = $routeParams.parentId || null;

			scope.hasCorrection = function () {
				return Utils.hasCorrection(scope.document);
			};

			scope.isCascading = function () {
				return self.isCascading();
			};


			Loading.start(i18n.trans('m.rbs.admin.admin.js.loading-document | ucf'));

			ctx = this.getCurrentContext();
			if (this.isCascading()) {
				if (ctx.queryParam) {
					params = ctx.queryParam;
				} else {
					params = { "id": "new" };
				}
			} else {
				params = $routeParams;
			}

			// Is 'rest' parameter a Model name?
			if (Utils.isModelName(rest)) {

				if (params.id === 'new') {
					q = $q.defer();
					promise = q.promise;
					$timeout(function () {
						q.resolve(REST.newResource(rest, scope.language));
					});
				} else {
					promise = REST.resource(rest, params.id, params.LCID); // || 'fr_FR'); // FIXME
				}

			} else {

				promise = rest;

			}

			function focus () {
				$timeout(function () {
					var focusable = $('#workspace .control-group.error input:visible').first();
					if (focusable.length === 0) {
						focusable = $('#workspace input:visible').first();
					}
					if (focusable.length === 0) {
						focusable = $('#workspace textarea:visible').first();
					}
					focusable.focus();
				});
			}

			function getSection () {
				return $routeParams.section || $location.search()['section'] || '';
			}

			function resourceReadyCallback (doc) {

				Loading.stop();
				scope.section = self.isCascading() ? '' : getSection();

				scope.document = doc;
				scope.isReferenceLanguage = (scope.document.refLCID === scope.document.LCID);
				scope.isLocalized = angular.isDefined(scope.document.refLCID);
				scope.locales = doc.META$.locales;

				Breadcrumb.setResource(scope.document);

				if (cascadedElement) {
					cascadedElement.show();
				}

				focus();
			}

			function resourceReadyErrorCallback () {
				Loading.stop();
			}

			promise.then(resourceReadyCallback, resourceReadyErrorCallback);


			// Install watches.


			// Watch form section changes.
			scope.$watch('section', function (section) {
				focus();
				scope.formSectionLabel = MainMenu.getCurrentSectionLabel();
				Breadcrumb.setResourceModifier(scope.formSectionLabel);
			}, true);

			scope.routeParams = $routeParams;
			scope.$watch('routeParams.section', function (section) {
				scope.section = getSection();
			}, true);

			return promise;
		};


	}]);


	// Validators directives.

	// FIXME Move these directives elsewhere

	var INTEGER_REGEXP = /^\-?\d*$/;
	app.directive('integer', function () {
		return {
			require : 'ngModel',
			link : function (scope, elm, attrs, ctrl) {
				ctrl.$parsers.unshift(function (viewValue) {
					if (INTEGER_REGEXP.test(viewValue)) {
						// it is valid
						ctrl.$setValidity('integer', true);
						return viewValue;
					} else {
						// it is invalid, return undefined (no model update)
						ctrl.$setValidity('integer', false);
						return undefined;
					}
				});
			}
		};
	});


	var FLOAT_REGEXP = /^\-?\d+((\.|\,)\d+)?$/;
	app.directive('smartFloat', function () {
		return {
			require : 'ngModel',
			link : function (scope, elm, attrs, ctrl) {
				ctrl.$parsers.unshift(function (viewValue) {
					if (FLOAT_REGEXP.test(viewValue)) {
						ctrl.$setValidity('float', true);
						return parseFloat(viewValue.replace(',', '.'));
					} else {
						ctrl.$setValidity('float', false);
						return undefined;
					}
				});
			}
		};
	});


})(window.jQuery);