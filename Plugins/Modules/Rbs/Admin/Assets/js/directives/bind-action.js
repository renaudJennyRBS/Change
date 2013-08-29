(function ($) {

	"use strict";

	angular.module('RbsChange').directive('rbsBindAction', ['RbsChange.Actions', '$timeout', 'RbsChange.i18n', function (Actions, $timeout, i18n) {

		return {
			restrict : 'A',
			scope    : true,


			link : function (scope, elm, attrs) {

				switch (elm.prop("tagName").toLowerCase()) {
				case 'button':
					elm.addClass('btn');
					break;
				case 'a':
					if (!elm.attr('href')) {
						elm.attr('href', 'javascript:;');
					}
					break;
				}

				attrs.$observe('rbsBindAction', function (rbsBindAction) {

					var actionObject, info, actionName, params = [];

					if (rbsBindAction) {
						info = (/^([a-z0-9_]+)(\((.*)\))?$/i).exec(rbsBindAction);
						if (info.length === 4) {
							actionName = info[1];
							if (info[3]) {
								params = info[3].split(/\s*,\s*/);
							}
						} else {
							actionName = rbsBindAction;
						}

						actionObject = Actions.get(actionName);
						if (! actionObject) {
							console.log('[Rbs/Admin/Assets/js/directives/bind-action.js] ' + actionName + ' does not exist!');
							return;
						}

						// Update UI
						if (params.indexOf('icon+label') !== -1) {
							elm.html('<i class="' + actionObject.icon + '"></i> ' + actionObject.label);
						}
						else if (params.indexOf('label+icon') !== -1) {
							elm.html(actionObject.label + ' <i class="' + actionObject.icon + '"></i>');
						}
						else if (params.indexOf('icon') !== -1) {
							elm.html('<i class="' + actionObject.icon + '"></i>');
							elm.attr('title', actionObject.label);
						}
						else {
							elm.html(actionObject.label);
						}

						scope.$watch('selectedDocuments', function (selection) {
							if (actionObject.isEnabled(selection)) {
								elm.removeAttr('disabled');
							} else {
								elm.attr('disabled', 'disabled');
							}
						});


						elm.click(function (e) {

							var params = {}, iconElm, iconElmClass;

							params.$scope  = scope;
							params.$extend = scope.extend;
							params.$target = $(this);
							params.$event  = e;
							params.$docs   = scope.selectedDocuments;
							params.$collection = scope.collection;
							params.$embedDialog = attrs.embedDialog ? jQuery('#' + attrs.embedDialog) : null;

							if (actionObject.loading) {
								iconElm = jQuery(elm).find('i[class^="icon-"]').first();
								iconElmClass = iconElm.attr('class');
								iconElm.removeClass(iconElmClass).addClass('icon-spinner icon-spin');
								elm.attr('disabled', 'disabled');
							}

							scope.$apply(function () {
								Actions.execute(actionObject.name, params).then(
									// Success
									function (value) {
										if (iconElm) {
											iconElm.removeClass('icon-spinner icon-spin').addClass(iconElmClass);
										}
										elm.removeAttr('disabled');
										/*if (attrs.bindActionSuccess) {
											scope._actionSuccessResult = value;
											scope.$eval(attrs.bindActionSuccess);
										}*/
									},
									// Error
									function (reason) {
										if (iconElm) {
											iconElm.removeClass('icon-spinner icon-spin').addClass(iconElmClass);
										}
										elm.removeAttr('disabled');
										/*if (attrs.bindActionError) {
											scope._actionErrorReason = reason;
											scope.$eval(attrs.bindActionError);
										}*/
									}
								);
							});

						});

					}

				});

			}
		};
	}]);

})(window.jQuery);