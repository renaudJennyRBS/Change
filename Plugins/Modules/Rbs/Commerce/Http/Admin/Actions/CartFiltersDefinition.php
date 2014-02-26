<?php
namespace Rbs\Commerce\Http\Admin\Actions;

use Zend\Http\Response as HttpResponse;

/**
* @name \Rbs\Commerce\Http\Admin\Actions\CartFiltersDefinition
*/
class CartFiltersDefinition
{
	public function execute(\Change\Http\Event $event)
	{
		$commerceServices = $event->getServices('commerceServices');
		if ($commerceServices instanceof \Rbs\Commerce\CommerceServices)
		{
			$definitions = $commerceServices->getCartManager()->getFiltersDefinition();
			$i18nManager = $event->getApplicationServices()->getI18nManager();

			$groupLabel = $i18nManager->trans('m.rbs.admin.admin.group_filter', ['ucf']);
			$groupDefinition = ['name' => 'group', 'config' => ['listLabel' => $groupLabel, 'label' => $groupLabel],
				'directiveName' => 'rbs-document-filter-group'];

			array_unshift($definitions, $groupDefinition);

			usort($definitions, function($a , $b) {
				$grpA = isset($a['config']['group']) ? $a['config']['group'] : '';
				$grpB = isset($b['config']['group']) ? $b['config']['group'] : '';
				if ($grpA == $grpB)
				{
					$labA =  isset($a['config']['listLabel']) ? $a['config']['listLabel'] : '';
					$labB =  isset($b['config']['listLabel']) ? $b['config']['listLabel'] : '';
					if ($labA == $labB)
					{
						return 0;
					}
					return strcmp($labA, $labB);
				}
				return strcmp($grpA, $grpB);
			});

			$result = new \Rbs\Admin\Http\Result\Renderer();
			$result->setHttpStatusCode(HttpResponse::STATUS_CODE_200);

			/* @var $manager \Rbs\Admin\Manager */
			$manager = $event->getParam('manager');
			$attributes = array('definitions' => $definitions);
			$filePath = __DIR__ . '/Assets/fitersDefinition.twig';
			$renderer = function () use ($filePath, $manager, $attributes)
			{
				return $manager->renderTemplateFile($filePath, $attributes);
			};
			$result->setRenderer($renderer);
			$event->setResult($result);
		}
	}
} 