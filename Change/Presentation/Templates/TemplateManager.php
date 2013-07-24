<?php
namespace Change\Presentation\Templates;

use Change\Presentation\PresentationServices;

/**
 * @api
 * @name \Change\Presentation\Templates\TemplateManager
 */
class TemplateManager
{
	/**
	 * @var PresentationServices
	 */
	protected $presentationServices;

	/**
	 * @var string
	 */
	protected $cachePath;

	/**
	 * @var \Twig_ExtensionInterface[]
	 */
	protected $extensions = array();

	/**
	 * @param PresentationServices $presentationServices
	 */
	public function setPresentationServices(PresentationServices $presentationServices)
	{
		$this->presentationServices = $presentationServices;
		$this->addExtension(new Twig\Extension($presentationServices->getApplicationServices()));
	}

	/**
	 * @api
	 * @param \Twig_ExtensionInterface $extension
	 * @return $this
	 */
	public function addExtension(\Twig_ExtensionInterface $extension)
	{
		$this->extensions[$extension->getName()] = $extension;
		return $this;
	}

	/**
	 * @return PresentationServices
	 */
	public function getPresentationServices()
	{
		return $this->presentationServices;
	}

	/**
	 * @return string
	 */
	protected function getCachePath()
	{
		if ($this->cachePath === null)
		{
			$this->cachePath = $this->presentationServices->getApplicationServices()->getApplication()->getWorkspace()->cachePath('Templates', 'Compiled');
			\Change\Stdlib\File::mkdir($this->cachePath);
		}
		return $this->cachePath;
	}

	/**
	 * @api
	 * @param string $pathName
	 * @param array $attributes
	 * @return string
	 */
	public function renderTemplateFile($pathName, array $attributes)
	{
		$loader = new \Twig_Loader_Filesystem(dirname($pathName));
		$twig = new \Twig_Environment($loader, array('cache' => $this->getCachePath(), 'auto_reload' => true));
		foreach ($this->extensions as $extension)
		{
			$twig->addExtension($extension);
		}
		return $twig->render(basename($pathName), $attributes);
	}
}