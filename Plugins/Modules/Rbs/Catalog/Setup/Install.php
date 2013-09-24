<?php
namespace Rbs\Catalog\Setup;

use Change\Db\Schema\FieldDefinition;
use Change\Db\Schema\KeyDefinition;

/**
 * @name \Rbs\Generic\Setup\Install
 */
class Install extends \Change\Plugins\InstallBase
{
	/**
	 * @param \Change\Plugins\Plugin $plugin
	 * @param \Change\Application\ApplicationServices $applicationServices
	 * @param \Change\Documents\DocumentServices $documentServices
	 * @param \Change\Presentation\PresentationServices $presentationServices
	 * @throws \Exception
	 */
	public function executeServices($plugin, $applicationServices, $documentServices, $presentationServices)
	{
		$schema = new Schema($applicationServices->getDbProvider()->getSchemaManager());
		$schema->generate();
		$applicationServices->getDbProvider()->closeConnection();

		$presentationServices->getThemeManager()->installPluginTemplates($plugin);

		$this->installGenericAttributes($applicationServices, $documentServices);
	}

	/**
	 * @param \Change\Application\ApplicationServices $applicationServices
	 * @param \Change\Documents\DocumentServices $documentServices
	 * @throws \Exception
	 */
	public function installGenericAttributes($applicationServices, $documentServices)
	{
		$i18nManager = $applicationServices->getI18nManager();
		$transactionManager = $applicationServices->getTransactionManager();
		try
		{
			$transactionManager->begin();

			$attributes = array();
			$attributesData = \Zend\Json\Json::decode(file_get_contents(__DIR__ . '/Assets/attributes.json'),
				\Zend\Json\Json::TYPE_ARRAY);
			foreach ($attributesData as $key => $attributeData)
			{
				/** @var $attribute \Rbs\Catalog\Documents\Attribute */
				$label = $i18nManager->trans($attributeData['title']);
				$attributeData['title'] = $label;
				$query = new \Change\Documents\Query\Query($documentServices, 'Rbs_Catalog_Attribute');
				$query->andPredicates($query->eq('label', $label));
				$attribute = $query->getFirstDocument();
				if ($attribute === null)
				{
					$attribute = $documentServices->getDocumentManager()->getNewDocumentInstanceByModelName('Rbs_Catalog_Attribute');
					$attribute->setLabel($label);
				}
				foreach ($attributeData as $propertyName => $value)
				{
					switch ($propertyName)
					{
						case 'attributes':
							foreach ($value as $attrKey)
							{
								$attribute->getAttributes()->add($attributes[$attrKey]);
							}
							break;
						default:
							$attribute->getDocumentModel()->setPropertyValue($attribute, $propertyName, $value);
							break;
					}
				}
				$attribute->save();
				$attributes[$key] = $attribute;
			}
			$transactionManager->commit();
		}
		catch (\Exception $e)
		{
			throw $transactionManager->rollBack($e);
		}
	}

	/**
	 * @param \Change\Plugins\Plugin $plugin
	 */
	public function finalize($plugin)
	{
		$plugin->setConfigurationEntry('locked', true);
	}
}