(function(jQuery) {
	"use strict";
	var app = angular.module('RbsChangeApp');

	//---------------- Menu
	function resizeSidebar() {
		jQuery('.process-sidebar').width(function() { return jQuery(this).parent().width(); });
	}

	jQuery(window).resize(function() {
		resizeSidebar();
	});

	resizeSidebar();

	jQuery('.process-sidebar').affix({
		offset: {
			top: function() { return jQuery('.process-sidebar-container').offset().top - 15; },
			bottom: function() {
				var node = jQuery('.process-sidebar-container').parent();
				return jQuery(document).outerHeight() - node.offset().top - node.outerHeight();
			}
		}
	});

	function rbsCommerceProcessMenu() {
		return {
			restrict: 'AE',
			templateUrl: '/rbsCommerceProcessMenu.tpl',
			link: function(scope) {
			}
		}
	}
	app.directive('rbsCommerceProcessMenu', rbsCommerceProcessMenu);

	function rbsCommerceProcess($rootScope, $compile, AjaxAPI) {
		var cacheCartDataKey = 'cartData';

		return {
			restrict: 'A',
			templateUrl: '/rbsCommerceProcess.tpl',
			scope: {},
			controller : ['$scope', '$element', function(scope, elem) {
				var self = this;
				var processInfo = null;
				scope.currentStep = null;
				scope.steps = ['identification', 'shipping', 'payment', 'confirm'];
				scope.processData = {};
				scope.loading = false;

				function setObjectData(cartData) {
					scope.cartData = cartData;
					if (cartData.processData) {
						processInfo = cartData.processData;
						if (!scope.currentStep && processInfo.common && processInfo.common.currentStep) {
							self.setCurrentStep(processInfo.common.currentStep);
						}
					}
					if (self.showPrices()) {
						scope.currencyCode = cartData.common.currencyCode;
					} else {
						scope.currencyCode = null;
					}
					$rootScope.$broadcast('rbsRefreshCart', {'cart': cartData });
				}

				this.loading = function(loading) {
					if (angular.isDefined(loading)) {
						scope.loading = (loading == true);
					}
					return scope.loading;
				};

				/**
				 * In alternative implementation of this controller, this method should return an HttpPromise or null (when there
				 * is no AJAX call to do). So make sure to check if there is a returned promise before calling methods on it.
				 */
				this.loadObjectData = function(withProcessData) {
					scope.loading = true;
					var params = {detailed: true, visualFormats: scope.parameters['imageFormats']};
					if (withProcessData) {
						params.dataSets = "process";
					}
					var request = AjaxAPI.getData('Rbs/Commerce/Cart', null, params);
					request.success(function(data, status, headers, config) {
						var cartData = data.dataSets;
						if (cartData && !angular.isArray(cartData)) {
							setObjectData(cartData);
						}
						scope.loading = false;
					}).error(function(data, status, headers, config) {
						scope.loading = false;
					});
					return request;
				};

				/**
				 * In alternative implementation of this controller, this method should return an HttpPromise or null (when there
				 * is no AJAX call to do). So make sure to check if there is a returned promise before calling methods on it.
				 */
				this.updateObjectData = function(actions) {
					scope.loading = true;
					var request = AjaxAPI.putData('Rbs/Commerce/Cart', actions, {detailed: true, visualFormats: scope.parameters['imageFormats']});
					request.success(function(data, status, headers, config) {
						var cartData = data.dataSets;
						if (cartData && !angular.isArray(cartData)) {
							setObjectData(cartData);
						}
						scope.loading = false;
					}).error(function(data, status, headers, config) {
						console.log('updateObjectData error', data, status);
						scope.loading = false;
					});
					return request;
				};

				this.getObjectData = function() {
					return scope.cartData;
				};

				this.showPrices = function(asObject) {
					var showPrices = (scope.parameters && (scope.parameters.displayPricesWithTax || scope.parameters.displayPricesWithoutTax));
					if (asObject && showPrices) {
						return {
							currencyCode: this.getCurrencyCode(),
							displayPricesWithTax: this.parameters('displayPricesWithTax'),
							displayPricesWithoutTax: this.parameters('displayPricesWithoutTax')
						}
					}
					return showPrices;
				};

				this.getCurrencyCode = function() {
					return scope.currencyCode;
				};

				this.parameters = function(name) {
					if (scope.parameters) {
						if (angular.isUndefined(name)) {
							return scope.parameters;
						} else {
							return scope.parameters[name];
						}
					}
					return null;
				};

				this.getProcessInfo = function() {
					return processInfo;
				};

				this.replaceChildren = function(parentNode, scope, html) {
					var collection = parentNode.children();
					collection.each(function() {
						var isolateScope = angular.element(this).isolateScope();
						if (isolateScope) {
							isolateScope.$destroy();
						}
					});
					collection.remove();
					if (html != '') {
						$compile(html)(scope, function (clone) {
							parentNode.append(clone);
						});
					}
				};

				function redrawLines() {
					var linesContainer = elem.find('[data-role="cart-lines"]');
					var directiveName = angular.isFunction(self.getLineDirectiveName) ? self.getLineDirectiveName : function(line) {
						return 'rbs-commerce-process-line-default';
					};
					var lines = scope.cartData.lines;
					var html = [];
					angular.forEach(lines, function(line, idx){
						html.push('<tr data-line="cartData.lines['+ idx +']" ' + directiveName(line) + '=""></tr>');
					});
					self.replaceChildren(linesContainer, scope, html.join(''));
				}

				scope.$watch('cartData', function(cartData, oldCartData) {
						if (cartData) {
							redrawLines();
							if (!scope.currentStep) {
								self.nextStep();
							}
						}
					}
				);

				this.nextStep = function () {
					this.setCurrentStep(this.getNextStep(scope.currentStep));
				};

				this.getNextStep = function (step) {
					if (!step) {
						return scope.steps[0];
					}
					for (var i = 0; i < scope.steps.length - 1; i++) {
						if (step == scope.steps[i]) {
							return scope.steps[i + 1];
						}
					}
					return null;
				};

				this.setCurrentStep = function(currentStep) {
					scope.currentStep = currentStep;
					var enabled = currentStep !== null, checked = enabled;
					for (var i =0; i < scope.steps.length; i++) {
						var step = scope.steps[i];
						var stepProcessData = this.getStepProcessData(step);
						if (step == currentStep) {
							checked = false;
							stepProcessData.isCurrent = true;
							stepProcessData.isChecked = checked;
							stepProcessData.isEnabled = enabled;
							enabled = false;
						} else {
							stepProcessData.isCurrent = false;
							stepProcessData.isChecked = checked;
							stepProcessData.isEnabled = enabled;
						}
					}
				};

				this.getStepProcessData = function(step) {
					if (step === null) {
						return {name: step, isCurrent: false, isEnabled: false, isChecked: false};
					}
					if (!angular.isObject(scope.processData[step])) {
						scope.processData[step] = {name: step, isCurrent: false, isEnabled: false, isChecked: false};
					}
					return scope.processData[step];
				};

				var cacheKey = elem.attr('data-cache-key');
				scope.parameters = AjaxAPI.getBlockParameters(cacheKey);

				var cartData = AjaxAPI.globalVar(cacheCartDataKey);
				if (!cartData) {
					this.loadObjectData(true);
				} else {
					setObjectData(cartData);
				}
			}],

			link: function(scope, elem, attrs, controller) {
				scope.showPrices = controller.showPrices();
				scope.isStepEnabled = function(step) {
					return controller.getStepProcessData(step).isEnabled;
				};
				scope.isStepChecked = function(step) {
					return controller.getStepProcessData(step).isChecked;
				};
			}
		}
	}

	rbsCommerceProcess.$inject = ['$rootScope', '$compile', 'RbsChange.AjaxAPI'];
	app.directive('rbsCommerceProcess', rbsCommerceProcess);
})(window.jQuery);