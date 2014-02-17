<?php
namespace Rbs\Admin\Http\Actions;

use Change\Documents\Property;
use Change\Http\Event;
use Zend\Http\Response as HttpResponse;

/**
 * @name \Rbs\Admin\Http\Actions\GetHtmlCollectionFilterPanel
 */
class GetHtmlCollectionFilterPanel
{
	/**
	 * Use Required Event Params: vendor, shortModuleName, shortBlockName
	 * @param Event $event
	 */
	public function execute($event)
	{
		$result = new \Rbs\Admin\Http\Result\Renderer();
		$vendor = $event->getParam('vendor');
		$shortModuleName = $event->getParam('shortModuleName');
		$shortDocumentName = $event->getParam('shortDocumentName');

		$documentName = $vendor. '_' . $shortModuleName . '_' . $shortDocumentName;
		$model = $event->getApplicationServices()->getModelManager()->getModelByName($documentName);
		if ($model)
		{
			$plugin = $event->getApplicationServices()->getPluginManager()->getModule($vendor, $shortModuleName);
			if ($plugin && $plugin->isAvailable())
			{
				$workspace =  $event->getApplication()->getWorkspace();
				$filePath = $workspace->composePath($plugin->getAssetsPath(), 'Admin', 'Documents', $shortDocumentName, 'filter-panel.twig');
				if (!is_readable($filePath))
				{
					$filePath = $workspace->pluginsModulesPath('Rbs', 'Admin', 'Assets', 'collection-filter-panel.twig');
				}

				$result->setHttpStatusCode(HttpResponse::STATUS_CODE_200);

				$definitions = $event->getApplicationServices()->getModelManager()->getFiltersDefinition($model);
				$attributes = array('model' => $model);
				if (count($definitions))
				{
					foreach ($definitions as $key => $definition)
					{
						if (!isset($definition['directiveName']) && isset($definition['config']['propertyType']))
						{
							switch($definition['config']['propertyType'])
							{
								case Property::TYPE_FLOAT:
								case Property::TYPE_DECIMAL:
								case Property::TYPE_INTEGER:
									$definitions[$key]['directiveName']= 'rbs-document-filter-property-number';
									break;
								case Property::TYPE_STRING:
									$definitions[$key]['directiveName']= 'rbs-document-filter-property-string';
									break;
								case Property::TYPE_BOOLEAN:
									$definitions[$key]['directiveName']= 'rbs-document-filter-property-boolean';
									break;
								case Property::TYPE_DATETIME:
								case Property::TYPE_DATE:
									$definitions[$key]['directiveName']= 'rbs-document-filter-property-datetime';
									break;
								case Property::TYPE_DOCUMENT:
								case Property::TYPE_DOCUMENTID:
								case Property::TYPE_DOCUMENTARRAY:
									$definitions[$key]['directiveName']= 'rbs-document-filter-property-document';
									if (isset($definition['config']['documentType']))
									{
										$definitions[$key]['config']['selectModel'] = json_encode(['name' => $definition['config']['documentType']]);
									}
									else
									{
										$definitions[$key]['config']['selectModel'] = 'true';
									}
									break;
							}
						}
					}

					$i18nManager = $event->getApplicationServices()->getI18nManager();
					array_unshift($definitions, ['name' => 'group', 'directiveName' => 'rbs-document-filter-group',
						'config' => ['listLabel' => $i18nManager->trans('m.rbs.admin.admin.group_filter', ['ucf'])]]);

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

					$attributes['definitions'] = $definitions;
				}
				/* @var $manager \Rbs\Admin\Manager */
				$manager = $event->getParam('manager');
				$renderer = function () use ($filePath, $manager, $attributes)
				{
					return $manager->renderTemplateFile($filePath, $attributes);
				};
				$result->setRenderer($renderer);
				$event->setResult($result);
				return;
			}
		}

		$result->setHttpStatusCode(HttpResponse::STATUS_CODE_404);
		$result->setRenderer(function ()
		{
			return null;
		});
		$event->setResult($result);
	}
}