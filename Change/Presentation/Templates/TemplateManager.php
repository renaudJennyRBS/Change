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
	 * @param PresentationServices $presentationServices
	 */
	public function setPresentationServices(PresentationServices $presentationServices)
	{
		$this->presentationServices = $presentationServices;
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
	 * @param string $pathName
	 * @param array $attributes
	 * @return string
	 */
	public function renderTemplateFile($pathName, array $attributes)
	{
		$loader = new \Twig_Loader_Filesystem(dirname($pathName));
		$twig = new \Twig_Environment($loader, array('cache' => $this->getCachePath(), 'auto_reload' => true));
		$twig->addExtension(new \Change\Presentation\Templates\Twig\Extension($this->presentationServices));
		return $twig->render(basename($pathName), $attributes);
	}
}