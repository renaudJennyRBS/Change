(function () {

	angular.module('RbsChange').factory('RbsChange.ArrayUtils', function () {
		return {

			remove: function (arr, from, to) {
				var rest = arr.slice((to || from) + 1 || arr.length);
				arr.length = from < 0 ? arr.length + from : from;
				return arr.push.apply(arr, rest);
			},
			
			removeValue: function (arr, value) {
				for (var i=0 ; i<arr.length ; i++) {
					if (angular.equals(arr[i], value)) {
						arr.splice(i, 1);
						return i;
					}
				}
				return -1;
			},
			
			removeArray: function (arr, elementsToRemove) {
				for (var i=0 ; i<elementsToRemove.length ; i++) {
					this.removeValue(arr, elementsToRemove[i]);
				}
			},

			// See http://jsperf.com/array-prototype-move
			move: function (arr, pos1, pos2) {
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

			clear: function (arr) {
				arr.splice(0, arr.length);
			},

			inArray: function (value, arr) {
				return jQuery.inArray(value, arr);
			},

			documentInArray: function (document, array) {
				var isDocumentInArray = false;
				angular.forEach(array, function (inArrayDoc) {
					if (inArrayDoc.id == document.id) {
						isDocumentInArray = true;
					}
				});
				return isDocumentInArray;
			},

			append: function (dst, src) {
				angular.forEach(src, function (item) {
					dst.push(item);
				});
				return dst;
			}
		};
	});

})();