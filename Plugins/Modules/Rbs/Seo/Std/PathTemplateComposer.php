<?php
/**
 * Copyright (C) 2014 Eric Hauswald
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Seo\Std;

/**
 * @name \Rbs\Seo\Std\PathTemplateComposer
 */
class PathTemplateComposer
{
	public function onPopulatePathRule(\Change\Events\Event $event)
	{
		$pathRule = $event->getParam('pathRule');
		$document = $event->getParam('document');

		if ($pathRule instanceof \Change\Http\Web\PathRule && $document instanceof \Change\Documents\AbstractDocument)
		{
			$applicationServices = $event->getApplicationServices();

			$query = $applicationServices->getDocumentManager()->getNewQuery('Rbs_Seo_ModelConfiguration');
			$query->andPredicates($query->eq('modelName', $document->getDocumentModelName()));
			$modelConfiguration = $query->getFirstDocument();

			if ($modelConfiguration instanceof \Rbs\Seo\Documents\ModelConfiguration)
			{
				$modelConfigurationLocalized = $modelConfiguration->getCurrentLocalization();
				if (!$modelConfigurationLocalized->getPathTemplate())
				{
					$modelConfigurationLocalized = $modelConfiguration->getRefLocalization();
				}
				$pathTemplate = $modelConfigurationLocalized->getPathTemplate();
				if ($pathTemplate)
				{
					if (preg_match_all('/\{([a-zA-Z0-9\.]+)\}/', $pathTemplate, $matches, PREG_SET_ORDER))
					{
						foreach ($matches as $match)
						{
							$value = null;
							$varPath = explode('.', $match[1]);
							$varPathIndex = 0;
							$context = $document;
							if ($varPath[$varPathIndex] == 'section')
							{
								$context = $applicationServices->getDocumentManager()
									->getDocumentInstance($pathRule->getSectionId());
								$varPathIndex = 1;
								if (!isset($varPath[$varPathIndex]))
								{
									$varPath[] = 'title';
								}
							}
							elseif ($varPath[$varPathIndex] == 'document')
							{
								$varPathIndex = 1;
								if (!isset($varPath[$varPathIndex]))
								{
									$varPath[] = 'title';
								}
							}

							$value = $this->resolveVarPath($varPath, $varPathIndex, $context);
							$value = $this->normalizePathValue($value);
							$pathTemplate = str_replace($match[0], $value, $pathTemplate);
						}

						$relativePtah = $this->normalizeRelativePath($pathTemplate);
						if (strlen($relativePtah))
						{
							$pathRule->setRelativePath($relativePtah);
							$event->stopPropagation();
						}
					}
				}
			}
		}
	}

	/**
	 * @param string[] $varPath
	 * @param integer $varPathIndex
	 * @param mixed $context
	 * @return mixed
	 */
	protected function resolveVarPath(array $varPath, $varPathIndex, $context)
	{
		if ($context === null || !isset($varPath[$varPathIndex]))
		{
			return null;
		}

		$propertyName = $varPath[$varPathIndex];
		if ($context instanceof \Change\Documents\AbstractDocument)
		{
			$model = $context->getDocumentModel();
			/** @var $property \Change\Documents\Property */
			$property = $model->getProperty($propertyName);
			if ($property)
			{
				$context = $property->getValue($context);
			}
			else
			{
				$callable = [$context, 'get' . ucfirst($propertyName)];
				if (is_callable($callable))
				{
					$context = call_user_func($callable);
				}
				else
				{
					$context = null;
				}
			}
		}
		elseif ($context instanceof \Change\Documents\DocumentArrayProperty)
		{
			$index = intval($propertyName);
			if ($context->offsetExists($index))
			{
				$context = $context->offsetGet($index);
			}
			else
			{
				$context = null;
			}
		}
		elseif (is_array($context))
		{
			$context = isset($context[$propertyName]) ? $context[$propertyName] : null;
		}
		elseif (is_object($context))
		{
			$callable = [$context, 'get' . ucfirst($propertyName)];
			if (is_callable($callable))
			{
				$context = call_user_func($callable);
			}
			else
			{
				$context = null;
			}
		}
		$varPathIndex++;
		if ($varPathIndex < count($varPath))
		{
			return $this->resolveVarPath($varPath, $varPathIndex, $context);
		}
		return $context;
	}

	/**
	 * @param $value
	 * @return string
	 */
	protected function normalizePathValue($value)
	{
		if ($value instanceof \Change\Documents\AbstractDocument)
		{
			$model = $value->getDocumentModel();
			if ($model->isPublishable())
			{
				return strval($model->getPropertyValue($value, 'title'));
			}
			return '';
		}
		elseif ($value instanceof \Change\Documents\DocumentArrayProperty)
		{
			if ($value->count())
			{
				return $this->normalizePathValue($value->offsetGet(0));
			}
			return '';
		}
		elseif ($value instanceof \DateTime)
		{
			return $value->format('Y-m-d');
		}
		elseif (is_numeric($value))
		{
			return strval($value);
		}
		elseif (is_string($value))
		{
			return $value;
		}
		return '';
	}

	/**
	 * @param string $path
	 * @return string
	 */
	protected function normalizeRelativePath($path)
	{
		$path = str_replace(array('\\', '&', '?', '#', ' '), '-', $path);
		$path = preg_replace('/\/{2,}/', '/', $path);
		if (substr($path, 0, 1) == '/')
		{
			$path = substr($path, 1);
		}
		return $path;
	}

	/**
	 * @param \Change\Events\Event $event
	 */
	public function onGetPathVariables(\Change\Events\Event $event)
	{
		$applicationServices = $event->getApplicationServices();
		$variables = ($event->getParam('variables')) ? $event->getParam('variables') : [];

		$i18nManager = $applicationServices->getI18nManager();
		$variables['section.title'] = $i18nManager->trans('m.rbs.seo.admin.meta_variable_section_title', ['ucf']);

		$modelName = $event->getParam('modelName');
		$model = $applicationServices->getModelManager()->getModelByName($modelName);
		if ($model)
		{
			$excludedProperties = ['model', 'label', 'authorName', 'documentVersion', 'publicationStatus', 'refLCID'];

			foreach ($model->getProperties() as $property)
			{
				if (in_array($property->getName(), $excludedProperties))
				{
					continue;
				}

				switch ($property->getType())
				{
					case \Change\Documents\Property::TYPE_INTEGER:
					case \Change\Documents\Property::TYPE_STRING:
					case \Change\Documents\Property::TYPE_DOCUMENT:
						$label = $i18nManager->trans($property->getLabelKey(), ['ucf']);
						if ($label != $property->getLabelKey())
						{
							$variables['document.' . $property->getName()] = $label;
						}
						break;
					case \Change\Documents\Property::TYPE_DOCUMENTARRAY:
						$label = $i18nManager->trans($property->getLabelKey(), ['ucf']);
						if ($label != $property->getLabelKey())
						{
							$variables['document.' . $property->getName() .'.0'] = $label;
						}
						break;
					case\Change\Documents\Property::TYPE_JSON:
						$label = $i18nManager->trans($property->getLabelKey(), ['ucf']);
						if ($label != $property->getLabelKey())
						{
							$variables['document.' . $property->getName(). '.KEY_NAME'] = $label;
						}
						break;
				}
			}
		}
		$event->setParam('variables', $variables);
	}
}