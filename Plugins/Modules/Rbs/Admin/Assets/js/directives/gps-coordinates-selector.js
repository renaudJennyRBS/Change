/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
(function ($) {

	"use strict";

	/**
	 * @name rbsGpsCoordinatesSelector
	 */
	angular.module('RbsChange').directive('rbsGpsCoordinatesSelector', ['RbsChange.Dialog', '$rootScope', 'RbsChange.i18n', 'RbsChange.Settings', '$q', function (Dialog, $rootScope, i18n, Settings, $q) {

		return {
			restrict    : 'E',
			templateUrl : 'Rbs/Admin/js/directives/gps-coordinates-selector.twig',
			scope       : {
				coordinates: '='
			},

			link : function (scope, elm, attrs) {

				scope.openMap = function ($event) {
					Dialog.embed(
						$(elm).find('.mapContainer'),
						{
							"title"    : i18n.trans('m.rbs.admin.admin.gps_map_title | ucf'),
							"contents" : '<rbs-gps-google-maps mode="coordinates" map-markers="mapMarkers" ></rbs-gps-google-maps>'
						},
						scope,
						{
							"pointedElement" : $event.target
						}
					);
				};

				if (!angular.isObject(scope.coordinates)) {
					scope.coordinates = {};
				}

				scope.mapMarkers = [scope.coordinates];

				scope.$watch('mapMarkers', function (markers){
					if (markers && markers.length > 0) {
						scope.coordinates = {
							latitude: markers[0].latitude,
							longitude: markers[0].longitude
						};
					}
				}, true);
			}
		};
	}]);

})(window.jQuery);