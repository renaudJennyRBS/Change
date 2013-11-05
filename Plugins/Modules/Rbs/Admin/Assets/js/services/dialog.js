(function ($) {


	var app = angular.module('RbsChange');


	app.provider('RbsChange.Dialog', function RbsChangeDialogProvider() {

		$('body').append('<div id="embedded-modal-backdrop"/>');
		var	$embeddedModalBackdrop = $('#embedded-modal-backdrop'),
			buttonIdCounter = 0;

		this.$get = ['$filter', '$compile', '$timeout', '$rootScope', '$q', 'RbsChange.Utils', 'RbsChange.i18n', function ($filter, $compile, $timeout, $rootScope, $q, Utils, i18n) {

			var dialog = {

				btnStyle : 'warning',

				$modal : null,

				confirmPopoverOptions : {
					placement : 'left',
					html      : true
				},



				// FIXME $compile the contents into a scope which should be given as argument too.
				confirm: function (title, message, style, warningMessage) {
					var deferred = $q.defer(),
					    self = this;

					title = title || "Demande de confirmation";
					if (this.btnStyle && this.btnStyle !== style) {
						this.$modal.find('.modal-footer .btn-primary').removeClass('btn-' + this.btnStyle);
					}
					this.btnStyle = style || 'warning';
					this.$modal.find('.modal-footer .btn-primary').addClass('btn-' + this.btnStyle);
					this.$modal.find('.modal-header h3').html(title);
					this.$modal.find('.modal-body').html($filter('BBcode')(message));
					if (warningMessage) {
						this.$modal.find('.message').html(warningMessage);
					} else {
						this.$modal.find('.message').empty();
					}


					this.$modal.find('.modal-footer .btn-primary').off('click').click(function (e) {
						// Promise is resolved and it won't change its state.
						deferred.resolve();
						self.$modal.modal('hide');
					});

					this.$modal.off('hide').on('hide', function () {
						// When the primary button is clicked, the promise is resolved,
						// so the reject() call below won't affect the promise as it has
						// already been resolved.
						// More info: http://api.jquery.com/category/deferred-object/
						deferred.reject();
					});

					this.$modal.modal('show');
					return deferred.promise;
				},


				// FIXME Use AngularJS's $q service instead of jQuery's Deferred.
				// FIXME $compile the contents into a scope which should be given as argument too.
				confirmLocal: function ($el, title, message, options) {
					$el = $($el);
					$el.popover('destroy');
					options = options || {};
					options.question = options.question || i18n.trans('m.rbs.admin.admin.js.do-you-want-to-continue | ucf');
					options.container = 'body';

					var deferred = $q.defer();
					deferred.promise.then(function () {
						$el.popover('destroy');
					}, function () {
						$el.popover('destroy');
					});

					buttonIdCounter++;
					var uidOK = "ok_button_" + buttonIdCounter;
					var uidCancel = "cancel_button_" + buttonIdCounter;

					message += '<hr/>' +
						options.question +
						'<div class="btn-toolbar confirm-dialogbox">' +
						'<button class="btn btn-primary btn-warning" type="button" id="' + uidOK + '">' + i18n.trans('m.rbs.admin.admin.js.yes | ucf') + '</button>' +
						'<button type="button" class="btn" id="'+uidCancel+'">' + i18n.trans('m.rbs.admin.admin.js.no | ucf') + '</button>' +
						'</div>';
					var opt = $.extend({}, { 'title': title, 'content': $filter('BBcode')(message) }, this.confirmPopoverOptions, options);
					$el.popover(opt).popover('show');

					$('#'+uidOK)
						.click(function () {
							$timeout(function () {
								deferred.resolve();
							});
						})
						.keydown(function (e) {
							if (e.keyCode === 27) { // Escape key
								$timeout(function () {
									deferred.reject();
								});
							}
						})
						.focus();

					$('#'+uidCancel).click(function () {
						$timeout(function () {
							deferred.reject();
						});
					});

					return deferred.promise;
				},


				$embeddedEl : null,


				cssRule :
					'<style type="text/css">' +
					'.embedded-modal.embedded-modal-{{id}}:after {' +
					'	right:auto;left:{{leftAfter}};' +
					'}' +
					'.embedded-modal.embedded-modal-{{id}}:before {' +
					'	right:auto;left:{{leftBefore}};' +
					'}' +
					'</style>',

				cssRulesApplied : {},

				lastCssRule : null,

				addCssRule : function (button, parent) {
					var offset = button.offset();
					var left = Math.floor(offset.left + (button.outerWidth()-14) / 2 - parent.offset().left);
					var id = ''+left;
					if ( ! (id in this.cssRulesApplied) ) {
						$(this.cssRule.replace(/\{\{id\}\}/g, id).replace(/\{\{leftBefore\}\}/g, (left-1)+'px').replace(/\{\{leftAfter\}\}/g, left+'px')).appendTo("head");
						this.cssRulesApplied[id] = true;
					}
					return 'embedded-modal-{{id}}'.replace(/\{\{id\}\}/g, id);
				},


				embed : function ($el, url, scope, options) {
					if (this.$embeddedEl !== null) {
						throw new Error("Cannot embed more than one Modal.");
					}

					options = options || {};
					this.$embeddedEl = $el;

					var self = this,
					    theScope = scope || $rootScope;

					scope.closeEmbeddedModal = function () {
						self.closeEmbedded();
					};

					if (angular.isUndefined(options.backdrop) || options.backdrop === true) {
						$embeddedModalBackdrop.fadeIn('fast');
					}
					$el.addClass('embedded-modal');

					if (options.cssClass) {
						$el.addClass(options.cssClass);
					}


					$el.fadeIn('fast');
					options.pointedElement = $(options.pointedElement);
					if (options.pointedElement) {
						var elTop = options.pointedElement.offset().top;
						var embedTop = $el.offset().top;

						if (embedTop < elTop) {
							$el.addClass('bottom');
						}

						this.lastCssRule = this.addCssRule(options.pointedElement, $el.parent());
						$el.addClass(this.lastCssRule);
					}

					theScope.dialogEmbedQ = $q.defer();

					if (angular.isObject(url)) {

						var contents =
							'<header class="clearfix">' +
							'<button data-ng-click="' + (url.closeButtonNgClick || 'closeEmbeddedModal()') + '" class="close pull-right" style="margin-left:30px;" type="button">×</button>' +
							'<h3>' + url.title + '</h3>' +
							'</header>' + url.contents;
						$el.html(contents);
						$compile($el.contents())(theScope);
						if ($el.find('.form-actions').length > 0) {
							$el.addClass('has-form-actions');
						}

					} else {

						$.get(url, function (data) {
							$compile(data)(theScope, function (clone) {
								$timeout(function() {
									$el.empty().append(clone);
								});
							});
						});

					}

					return theScope.dialogEmbedQ.promise;

				},


				closeEmbedded: function () {
					var self = this,
					    q = $q.defer();

					if (self.$embeddedEl !== null) {
						$embeddedModalBackdrop.fadeOut('fast');
						self.$embeddedEl.fadeOut('fast', function () {
							// Remove any <rbs-document-list/> elements.
							self.$embeddedEl.find('rbs-document-list').each(function () {
								angular.element($(this)).scope().$destroy();
							});
							self.$embeddedEl.empty().hide().removeClass('bottom').removeClass(self.lastCssRule);
							self.$embeddedEl = null;
							$timeout(function () {
								q.resolve();
							});
						});
					} else {
						$timeout(function () {
							q.resolve();
						});
					}

					return q.promise;
				},


				confirmEmbed: function ($el, title, text, scope, options) {

					var deferred = $q.defer(),
					    self = this;

					scope.__dialog_confirmEmbed_confirm = function () {
						self.closeEmbedded();
						deferred.resolve();
					};

					scope.__dialog_confirmEmbed_cancel = function () {
						self.closeEmbedded();
						deferred.reject();
					};

					options = options || {};
					if (!options.primaryButtonClass) {
						options.primaryButtonClass = 'btn-warning';
					}
					if (options.pointedElement) {
						options.pointedElement = $(options.pointedElement);
					}

					var contents =
						'<p>' + text + '</p>' +
						'<p><strong>' + i18n.trans('m.rbs.admin.admin.js.do-you-want-to-continue | ucf') + '</strong></p>' +
						'<div class="form-actions">' +
						'<button class="btn btn-primary ' + options.primaryButtonClass + '" type="button" data-ng-click="__dialog_confirmEmbed_confirm()">';

					if (!options.primaryButtonIcon && options.pointedElement) {
						var icon = options.pointedElement.find('i[class^="icon-"]').first();
						if (icon.length) {
							var classes = icon.attr('class').split(/\s+/);
							for (var i=0 ; i<classes.length ; i++) {
								if (classes[i] !== 'icon-white' && Utils.startsWith(classes[i], 'icon-')) {
									options.primaryButtonIcon = classes[i];
									title = '<i class="' + options.primaryButtonIcon + '"></i> ' + title;
									break;
								}
							}
						}
					}

					if (options.primaryButtonIcon) {
						contents += '<i class="icon-white ' + options.primaryButtonIcon + '"></i> ';
					}

					if (options.primaryButtonText) {
						contents += i18n.trans('m.rbs.admin.admin.js.yes | ucf') + '<small>, ' + options.primaryButtonText+'</small>';
					} else {
						contents += i18n.trans('m.rbs.admin.admin.js.yes | ucf');
					}
					contents += '</button>';
					contents += ' <button type="button" class="btn btn-default" data-ng-click="__dialog_confirmEmbed_cancel()">' + i18n.trans('m.rbs.admin.admin.js.no | ucf') + '</button></div>';

					this.embed(
							$el,
							{
								contents           : contents,
								title              : title,
								closeButtonNgClick : '__dialog_confirmEmbed_cancel()'
							},
							scope,
							options
					);

					return deferred.promise;

				},


				destroyEmbedded: function () {
					var self = this,
						q = $q.defer();

					if (self.$embeddedEl !== null) {
						$embeddedModalBackdrop.hide();
						// Remove any <rbs-document-list/> elements.
						self.$embeddedEl.find('rbs-document-list').each(function () {
							angular.element($(this)).scope().$destroy();
						});
						self.$embeddedEl.empty().hide().removeClass('bottom').removeClass(self.lastCssRule);
						self.$embeddedEl.hide();
						self.$embeddedEl = null;
						$timeout(function () {
							q.resolve();
						});
					} else {
						$timeout(function () {
							q.resolve();
						});
					}

					return q.promise;
				}

			};

			dialog.$modal = $('#modalConfirm').modal({
				backdrop : 'static',
				keyboard : true,
				show     : false
			});

			dialog.$modal.on('shown', function () {
				dialog.$modal.find('.modal-footer .btn-primary').focus();
			});

			// Close any Dialog when the route changes.
			$rootScope.$on('$routeChangeSuccess', function () {
				if (dialog.$embeddedEl) {
					dialog.destroyEmbedded();
				}
			});

			return dialog;
		}];
	});


})( window.jQuery );