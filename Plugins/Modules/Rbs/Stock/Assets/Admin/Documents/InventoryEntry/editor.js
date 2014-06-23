/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
(function ()
{
	"use strict";

	function Editor($http, REST, Utils, Dialog, i18n, NotificationCenter)
	{
		return {
			restrict: 'A',
			require: '^rbsDocumentEditorBase',

			link : function (scope, element) {

				scope.movement = {value: null};
				scope.info = {};
				scope.disabled = true;

				scope.isLocked = function() {
					return scope.disabled;
				};

				scope.askUnlock = function() {
					scope.disabled = !scope.disabled;
				};

				scope.confirmConsolidate = function ($event)
				{
					Dialog.confirmEmbed(
						element.find('.confirmation-area'),
						i18n.trans('m.rbs.stock.admin.confirm_consolidate | ucf'),
						i18n.trans('m.rbs.stock.admin.confirm_consolidate_message | ucf'),
						scope,
						{
							'pointedElement' : $($event.target),
							'primaryButtonText' : i18n.trans('m.rbs.stock.admin.consolidate_button | ucf'),
							'primaryButtonClass' : 'btn-danger',
							'cssClass' : 'danger'
						}
					).then(function () {
							var url = Utils.makeUrl('resources/Rbs/Stock/InventoryEntry/'+ scope.document.id +'/consolidate/', {warehouseId: scope.document.warehouse });
							$http.get(REST.getBaseUrl(url))
								.success(function(result) {
									scope.info = result;
									scope.document.level = scope.original.level = result.level;
								})
								.error(function(){
									NotificationCenter.error(
										i18n.trans('m.rbs.stock.admin.consolidate_movement_title | ucf'),
										i18n.trans('m.rbs.stock.admin.consolidate_movement_message | ucf'),
										'EDITOR');
								})
						});
				};

				scope.addMovement = function ()
				{
					var data = {movement: scope.movement.value, warehouseId: scope.document.warehouse};

					var url = Utils.makeUrl('resources/Rbs/Stock/Sku/'+ scope.document.sku.id +'/movement/');
					$http.post(REST.getBaseUrl(url), data)
						.success(function() {
						scope.movement.value = null;
						loadInfos();
						})
						.error(function(){
							NotificationCenter.error(
								i18n.trans('m.rbs.stock.admin.add_movement_title | ucf'),
								i18n.trans('m.rbs.stock.admin.add_movement_error_message | ucf'),
								'EDITOR'
							);
						});
				}

				scope.onReady = function() {
					loadInfos();
				}
				scope.onReload = function() {
					loadInfos();
				}

				function loadInfos ()
				{
					var url = Utils.makeUrl('resources/Rbs/Stock/InventoryEntry/'+ scope.document.id +'/info/', {warehouseId: scope.document.warehouse });
					$http.get(REST.getBaseUrl(url)).success(function(data) {
						scope.info = data;
					});
				}

			}
		};
	}

	Editor.$inject = ['$http', 'RbsChange.REST', 'RbsChange.Utils', 'RbsChange.Dialog', 'RbsChange.i18n', 'RbsChange.NotificationCenter'];
	angular.module('RbsChange').directive('rbsDocumentEditorRbsStockInventoryEntryEdit', Editor);

})();