/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
(function () {

	"use strict";

	/**
	 * @ngdoc service
	 * @name RbsChange.service:ArrayUtils
	 * @description
	 * Array manipulation utilities.
	 */
	angular.module('RbsChange').factory('RbsChange.ArrayUtils', function ()
	{
		return {

			/**
			 * @ngdoc function
			 * @methodOf RbsChange.service:ArrayUtils
			 * @name RbsChange.service:ArrayUtils#remove
			 *
			 * @description Removes elements in an Array.
			 *
			 * @param {Array} arr Array from which elements should be removed.
			 * @param {Integer} from Index of first element to be removed.
			 * @param {Integer} to Index of last element to be removed.
			 */
			remove : function (arr, from, to) {
				var rest = arr.slice((to || from) + 1 || arr.length);
				arr.length = from < 0 ? arr.length + from : from;
				return arr.push.apply(arr, rest);
			},

			/**
			 * @ngdoc function
			 * @methodOf RbsChange.service:ArrayUtils
			 * @name RbsChange.service:ArrayUtils#removeValue
			 *
			 * @description Removes a value in an Array. This method uses `angular.equals()` to search for the
			 * value to remove.
			 *
			 * @param {Array} arr Array from which the value should be removed.
			 * @param {*} value The value to be removed.
			 */
			removeValue : function (arr, value) {
				for (var i=0 ; i<arr.length ; i++) {
					if (angular.equals(arr[i], value)) {
						arr.splice(i, 1);
						return i;
					}
				}
				return -1;
			},

			/**
			 * @ngdoc function
			 * @methodOf RbsChange.service:ArrayUtils
			 * @name RbsChange.service:ArrayUtils#removeArray
			 *
			 * @description Removes values in an Array from another Array.
			 *
			 * @param {Array} arr Array from which elements should be removed.
			 * @param {Array} elementsToRemove Array of elements to be removed from `arr`.
			 */
			removeArray : function (arr, elementsToRemove) {
				for (var i=0 ; i<elementsToRemove.length ; i++) {
					this.removeValue(arr, elementsToRemove[i]);
				}
			},

			/**
			 * @ngdoc function
			 * @methodOf RbsChange.service:ArrayUtils
			 * @name RbsChange.service:ArrayUtils#move
			 *
			 * @description Moves an element into an Array.
			 *
			 * See {@link http://jsperf.com/array-prototype-move}
			 *
			 * @param {Array} arr Array into which an element should be moved.
			 * @param {Integer} pos1 Initial position of the element to be moved.
			 * @param {Integer} pos2 New position of the element to be moved.
			 */
			move : function (arr, pos1, pos2) {
				// local variables
			    var i, tmp;
			    // cast input parameters to integers
			    pos1 = parseInt(pos1, 10);
			    pos2 = parseInt(pos2, 10);
			    // if positions are different and inside array
			    if (pos1 !== pos2 && 0 <= pos1 && pos1 <= arr.length && 0 <= pos2 && pos2 <= arr.length) {
			      // save element from position 1
			      tmp = arr[pos1];
			      // move element down and shift other elements up
			      if (pos1 < pos2) {
			        for (i = pos1; i < pos2; i++) {
			          arr[i] = arr[i + 1];
			        }
			      }
			      // move element up and shift other elements down
			      else {
			        for (i = pos1; i > pos2; i--) {
			          arr[i] = arr[i - 1];
			        }
			      }
			      // put element from position 1 to destination
			      arr[pos2] = tmp;
			      return true;
			    }
			    return false;
			},

			/**
			 * @ngdoc function
			 * @methodOf RbsChange.service:ArrayUtils
			 * @name RbsChange.service:ArrayUtils#clear
			 *
			 * @description Clears the given Array.
			 *
			 * @param {Array} arr Array to clear.
			 */
			clear : function (arr) {
				arr.splice(0, arr.length);
			},

			/**
			 * @ngdoc function
			 * @methodOf RbsChange.service:ArrayUtils
			 * @name RbsChange.service:ArrayUtils#inArray
			 *
			 * @description Checks if a value is an Array.
			 *
			 * @param {*} value The value.
			 * @param {Array} arr The Array.
			 */
			inArray : function (value, arr) {
				return jQuery.inArray(value, arr);
			},

			/**
			 * @ngdoc function
			 * @methodOf RbsChange.service:ArrayUtils
			 * @name RbsChange.service:ArrayUtils#intersect
			 *
			 * @description Returns an Array containing the elements of `arr1` that are also present in `arr2`.
			 *
			 * @param {Array} arr1 The first Array.
			 * @param {Array} arr2 The second Array.
			 */
			intersect : function (arr1, arr2) {
				var intersect = [];
				for (var i = 0; i < arr1.length; i++) {
					if (jQuery.inArray(arr1[i], arr2)) {
						intersect.push(arr1[i]);
					}
				}
				return intersect;
			},

			/**
			 * @ngdoc function
			 * @methodOf RbsChange.service:ArrayUtils
			 * @name RbsChange.service:ArrayUtils#documentInArray
			 *
			 * @description Checks if the given `document` is in the given `array`. Only the ID is checked.
			 *
			 * @param {Document} document The Document to search in `array`.
			 * @param {Array} array The Array in which `document` is searched for.
			 */
			documentInArray : function (document, array) {
				var isDocumentInArray = false;
				angular.forEach(array, function (inArrayDoc) {
					if (inArrayDoc.id == document.id) {
						isDocumentInArray = true;
					}
				});
				return isDocumentInArray;
			},

			/**
			 * @ngdoc function
			 * @methodOf RbsChange.service:ArrayUtils
			 * @name RbsChange.service:ArrayUtils#append
			 *
			 * @description Appends elements in an Array at the end of another Array.
			 *
			 * @param {Array} dst The destination Array.
			 * @param {Array} src Array of elements to append to `dst`.
			 */
			append : function (dst, src) {
				if (angular.isArray(src)) {
					angular.forEach(src, function (item) {
						dst.push(item);
					});
				} else {
					dst.push(src);
				}
				return dst;
			}
		};
	});

})();