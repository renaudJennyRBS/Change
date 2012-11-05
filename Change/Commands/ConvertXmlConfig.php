<?php

namespace Change\Commands;

use Zend\Json\Json;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ConvertXmlConfig extends \Change\Application\Console\ChangeCommand
{
	/**
	 */
	protected function configure()
	{
		// Configure your command here
		$this->addArgument('file', InputArgument::REQUIRED, 'file to a valid change XML configuration');
	}

	/**
	 *
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 * @throws \LogicException
	 */
	protected function execute(InputInterface $input, OutputInterface $output)
	{
		// Code of you command here
		$config = realpath($input->getArgument('file'));
		if (!file_exists($config))
		{
			throw new \RuntimeException('File ' . $config . ' does not exist');
		}
		$dom = new \DOMDocument('1.0', 'UTF-8');
		$dom->load($config);
		if ($dom === false)
		{
			throw new \RuntimeException('Invalid XML file');
		}
		$toRemove = array();
		foreach ($dom->getElementsByTagName('entry') as $element)
		{
			$value = $element->nodeValue;

			$newNode = $dom->createElement($element->getAttribute('name'), $value);

			$element->parentNode->appendChild($newNode);
			$toRemove[] = $element;
		}
		foreach ($dom->getElementsByTagName('define') as $element)
		{
			/* @var \DOMNode $dom */
			$value = $element->nodeValue;
			$newNode = $dom->createElement($element->getAttribute('name'), $value);
			$element->parentNode->appendChild($newNode);
			$toRemove[] = $element;
		}
		array_map(function ($elem)
		{
			$elem->parentNode->removeChild($elem);
		}, $toRemove);
		$jsonFile = str_replace('.xml', '.json', $config);
		$fullArray = Json::decode(Json::fromXml($dom->saveXML()), Json::TYPE_ARRAY);
		array_walk_recursive($fullArray, function (&$item, $key)
		{
			if (is_numeric($item))
			{
					$item = floatval($item);
				}
				if ($item === "false")
				{
					$item = false;
				}
				if ($item === "true")
				{
					$item = true;
				}

			});
		if (isset($fullArray['project']))
		{
			$fullArray = $fullArray['project'];
		}
		file_put_contents($jsonFile, Json::prettyPrint(Json::encode($fullArray)));
	}
}