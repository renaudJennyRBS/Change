(function ($) {

	"use strict";

	angular.module('RbsChange').directive('bindAction', ['RbsChange.Actions', '$timeout', 'RbsChange.i18n', function (Actions, $timeout, i18n) {

		return {
			restrict : 'A',

			priority: 90,

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

				var ready = false;

				attrs.$observe('bindAction', function () {

					if (ready) {
						return;
					}

					ready = true;

					var info,
					    actionName,
					    actionObject,
					    DL,
					    display;

					// Check whether 'bind-action' is like ['actionName', DocumentListInstance, 'display'] or not
					if (attrs.bindAction.charAt(0) === '[') {
						info = scope.$eval(attrs.bindAction);
						actionName = info[0];
						DL = info.length > 1 ? info[1] : scope.DL;
						display = attrs.actionDisplay ? attrs.actionDisplay : (info.length > 2 ? info[2] : null);
					} else {
						info = attrs.bindAction;
						actionName = info;
						DL = scope.DL;
						display = attrs.actionDisplay ? attrs.actionDisplay : null;
					}

					function updateUI (actionObject) {
						if (display === null) {
							display = actionObject.display ? actionObject.display : 'icon+label';
						}

						elm.empty();

						// FIXME Keep content if there is one?
						if (actionObject.label && display.indexOf('label') !== -1) {
							if (elm.children().length > 0 && elm.children().first().prop('tagName').toUpperCase() === 'I') {
								elm.append(' ' + i18n.trans(actionObject.label));
							} else {
								elm.append(i18n.trans(actionObject.label));
							}
						}
						if (actionObject.description) {
							elm.attr('title', i18n.trans(actionObject.description));
						}
						if (actionObject.icon && display.indexOf('icon') !== -1) {
							if (display === 'label+icon') {
								elm.append(' <i class="' + actionObject.icon + '"></i>');
							} else {
								elm.prepend('<i class="' + actionObject.icon + '"></i> ');
							}
						}
						if (actionObject.cssClass) {
							elm.addClass(actionObject.cssClass);
						}
					}

					var groupName = null;
					if (Actions.isGroup(actionName) && DL) {
						groupName = actionName;

						scope.$on('Change:DocumentListChanged', function () {
							actionObject = Actions.getActionForGroup(groupName, DL.selectedDocuments);
							updateUI(actionObject);
							if (DL.isActionEnabled(actionObject.name)) {
								elm.removeAttr('disabled');
							} else {
								elm.attr('disabled', 'disabled');
							}
						});
					} else {
						actionObject = Actions.get(actionName);
						if (actionObject) {
							updateUI(actionObject);
						} else {
							throw new Error("Unknown action '" + actionName + "' for directive 'bindAction'.");
						}
						if (DL) {
							scope.$on('Change:DocumentListChanged', function () {
								if (DL.isActionEnabled(actionName)) {
									elm.removeAttr('disabled');
								} else {
									elm.attr('disabled', 'disabled');
								}
							});
						}
					}

					var clickHandler = function (e) {
						var params,
						    iconElm, iconElmClass;

						if (attrs.bindActionParams) {
							params = scope.$eval(attrs.bindActionParams);
						} else {
							params = {};
						}

						if (attrs.bindActionEmbedDialog) {
							params.$embedDialog = jQuery('#' + attrs.bindActionEmbedDialog);
						}

						params.$target = $(this);
						params.$event = e;

						if (DL) {
							params = DL.fillActionParams(params);
						} else {
							params.$scope = scope;
						}

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
									if (actionObject.loading) {
										iconElm.removeClass('icon-spinner icon-spin').addClass(iconElmClass);
									}
									elm.removeAttr('disabled');
									if (attrs.bindActionSuccess) {
										scope._actionSuccessResult = value;
										scope.$eval(attrs.bindActionSuccess);
									}
								},
								// Error
								function (reason) {
									if (actionObject.loading) {
										iconElm.removeClass('icon-spinner icon-spin').addClass(iconElmClass);
									}
									elm.removeAttr('disabled');
									if (attrs.bindActionError) {
										scope._actionErrorReason = reason;
										scope.$eval(attrs.bindActionError);
									}
								}
							);
						});
					};

					elm.click(clickHandler);

				});

			}
		};
	}]);

})(window.jQuery);