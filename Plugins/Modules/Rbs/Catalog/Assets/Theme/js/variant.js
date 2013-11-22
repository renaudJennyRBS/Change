angular.module('RbsChangeApp').controller('RbsVariantList', function ($scope, $http)
	{
		$scope.$watch('blockId', function (){
			$scope.initAxes();
		});

		$scope.initAxes= function()
		{
			if ($scope.axesCount > 0)
			{
				var i;
				for (i = 0; i < $scope.axesCount; i++)
				{
					eval("$scope.selectedVariant" + i + " = null");
					eval("$scope.variants" + i + " = []");
					eval("$scope.variantNames" + i + "= '" + $scope.axesNames[i] + "'");
				}
			}
			if ($scope.axesValues.length)
			{
				$scope.variants0 = $scope.axesValues;
			}
		}

		$scope.variantChanged = function (selectedVariant, level)
		{
			if (level < $scope.axesCount && selectedVariant > 0)
			{
				$http.post('Action/Rbs/Catalog/VariantGroup', {
					variantGroupId: $scope.variantGroupId,
					parentVariant: selectedVariant
				}).success(function (data) {
					var i;
					if (data.axesValues)
					{
						eval("$scope.variants" + level + "= data.axesValues");
					}
					for (i = level+1; i <= $scope.axesCount; i++)
					{
						eval("$scope.variants" + i + "= []");
						eval("$scope.selectedVariant" + i + "= null");
					}
				});
			}

			if (selectedVariant > 0)
			{
				$http.post('Action/Rbs/Catalog/ProductResult', {
					productId: selectedVariant
				}).success(function (data) {
					$scope.productPresentation = data;
					$scope.currentProductId = selectedVariant;
					if (data.stock.level > 0 && data.prices.price != null)
					{
						$scope.canBeAddedToCart = true;
					}
					else
					{
						$scope.canBeAddedToCart = false;
					}
				});
			}
		}
	}
);