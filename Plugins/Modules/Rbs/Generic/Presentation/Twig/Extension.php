<?php
namespace Rbs\Generic\Presentation\Twig;

use Change\Http\Web\UrlManager;

/**
 * @name \Rbs\Generic\Presentation\Twig\Extension
 */
class Extension implements \Twig_ExtensionInterface
{
	/**
	 * @var \Change\Services\ApplicationServices
	 */
	protected $applicationServices;

	/**
	 * @var \Rbs\Generic\GenericServices
	 */
	protected $genericServices;

	/**
	 * @var UrlManager
	 */
	protected $urlManager;

	/**
	 * @param \Change\Application $application
	 * @param \Change\Services\ApplicationServices $applicationServices
	 * @param \Rbs\Generic\GenericServices $genericServices
	 * @param UrlManager $urlManager
	 */
	function __construct(\Change\Application $application, \Change\Services\ApplicationServices $applicationServices, \Rbs\Generic\GenericServices $genericServices, UrlManager $urlManager)
	{
		$this->application = $application;
		$this->applicationServices = $applicationServices;
		$this->urlManager = $urlManager;
		$this->genericServices = $genericServices;
	}

	/**
	 * Returns the name of the extension.
	 * @return string The extension name
	 */
	public function getName()
	{
		return 'Rbs_Generic';
	}

	/**
	 * Initializes the runtime environment.
	 * This is where you can load some file that contains filter functions for instance.
	 * @param \Twig_Environment $environment The current Twig_Environment instance
	 */
	public function initRuntime(\Twig_Environment $environment)
	{
	}

	/**
	 * Returns the token parser instances to add to the existing list.
	 * @return array An array of Twig_TokenParserInterface or Twig_TokenParserBrokerInterface instances
	 */
	public function getTokenParsers()
	{
		return array();
	}

	/**
	 * Returns the node visitor instances to add to the existing list.
	 * @return array An array of Twig_NodeVisitorInterface instances
	 */
	public function getNodeVisitors()
	{
		return array();
	}

	/**
	 * Returns a list of filters to add to the existing list.
	 * @return array An array of filters
	 */
	public function getFilters()
	{
		return array(
			new \Twig_SimpleFilter('richText', array($this, 'richText'), array('is_safe' => array('all'))),
			new \Twig_SimpleFilter('transDate', array($this, 'transDate')),
			new \Twig_SimpleFilter('transDateTime', array($this, 'transDateTime')),
			new \Twig_SimpleFilter('formatDate', array($this, 'formatDate')),
			new \Twig_SimpleFilter('boolean', array($this, 'boolean')),
			new \Twig_SimpleFilter('float', array($this, 'float')),
			new \Twig_SimpleFilter('integer', array($this, 'integer'))
		);
	}

	/**
	 * Returns a list of tests to add to the existing list.
	 * @return array An array of tests
	 */
	public function getTests()
	{
		return array();
	}

	/**
	 * Returns a list of functions to add to the existing list.
	 * @return array An array of functions
	 */
	public function getFunctions()
	{
		return array(
			new \Twig_SimpleFunction('imageURL', array($this, 'imageURL')),
			new \Twig_SimpleFunction('canonicalURL', array($this, 'canonicalURL')),
			new \Twig_SimpleFunction('contextualURL', array($this, 'contextualURL')),
			new \Twig_SimpleFunction('currentURL', array($this, 'currentURL')),
			new \Twig_SimpleFunction('ajaxURL', array($this, 'ajaxURL')),
			new \Twig_SimpleFunction('functionURL', array($this, 'functionURL')),
			new \Twig_SimpleFunction('resourceURL', array($this, 'resourceURL')),
			new \Twig_SimpleFunction('avatarURL', array($this, 'avatarURL'))
		);
	}

	/**
	 * Returns a list of operators to add to the existing list.
	 * @return array An array of operators
	 */
	public function getOperators()
	{
		return array();
	}

	/**
	 * Returns a list of global variables to add to the existing list.
	 * @return array An array of global variables
	 */
	public function getGlobals()
	{
		return array();
	}

	/**
	 * @param \Change\Application $application
	 * @return $this
	 */
	public function setApplication(\Change\Application $application)
	{
		$this->application = $application;
		return $this;
	}

	/**
	 * @return \Change\Application
	 */
	public function getApplication()
	{
		return $this->application;
	}

	/**
	 * @param \Change\Services\ApplicationServices $applicationServices
	 * @return $this
	 */
	public function setApplicationServices(\Change\Services\ApplicationServices $applicationServices)
	{
		$this->applicationServices = $applicationServices;
		return $this;
	}

	/**
	 * @return \Change\Services\ApplicationServices
	 */
	public function getApplicationServices()
	{
		return $this->applicationServices;
	}

	/**
	 * @param UrlManager $urlManager
	 * @return $this
	 */
	public function setUrlManager(UrlManager $urlManager)
	{
		$this->urlManager = $urlManager;
		return $this;
	}


	/**
	 * @param \Rbs\Generic\GenericServices $genericServices
	 * @return $this
	 */
	public function setGenericServices($genericServices)
	{
		$this->genericServices = $genericServices;
		return $this;
	}

	/**
	 * @return UrlManager
	 */
	public function getUrlManager()
	{
		return $this->urlManager;
	}

	/**
	 * @return \Rbs\Generic\GenericServices
	 */
	public function getGenericServices()
	{
		return $this->genericServices;
	}

	/**
	 * @param \Rbs\Media\Documents\Image|integer $image
	 * @param integer $maxWidth
	 * @param integer $maxHeight
	 * @return string
	 */
	public function imageURL($image, $maxWidth = 0, $maxHeight = 0)
	{
		if (is_array($maxWidth))
		{
			$size = array_values($maxWidth);
			$maxWidth = isset($size[0]) ? $size[0] : 0;
			$maxHeight = isset($size[1]) ? $size[1] : $maxHeight;
		}

		if (is_numeric($image))
		{
			$image = $this->getApplicationServices()->getDocumentManager()->getDocumentInstance($image);
		}
		elseif (is_string($image))
		{
			if (strpos($image, 'change://') === 0)
			{
				$storageManager = $this->getApplicationServices()->getStorageManager();
				$storageURI = new \Zend\Uri\Uri($image);
				$storageURI->setQuery(array('max-width' => intval($maxWidth), 'max-height' => intval($maxHeight)));
				$url = $storageURI->normalize()->toString();
				return $storageManager->getPublicURL($url);
			}
			return $image;
		}

		if ($image instanceof \Rbs\Media\Documents\Image)
		{
			return $image->getPublicURL(intval($maxWidth), intval($maxHeight));
		}
		return '';
	}

	/**
	 * @param \Change\Documents\AbstractDocument|integer $document
	 * @param \Change\Presentation\Interfaces\Website|integer|null $website
	 * @param array $query
	 * @param string|null $LCID
	 * @return string|null
	 */
	public function canonicalURL($document, $website = null, $query = array(), $LCID = null)
	{
		if (is_numeric($document) || $document instanceof \Change\Documents\AbstractDocument)
		{
			if (is_numeric($website))
			{
				$website = $this->getApplicationServices()->getDocumentManager()->getDocumentInstance($website);
			}
			if ($website instanceof \Change\Presentation\Interfaces\Section)
			{
				$website = $website->getWebsite();
			}
			if ($website === null || $website instanceof \Change\Presentation\Interfaces\Website)
			{
				return $this->getUrlManager()->getCanonicalByDocument($document, $website, $query, $LCID)->normalize()
					->toString();
			}
		}
		return null;
	}

	/**
	 * @param \Change\Documents\AbstractDocument|integer $document
	 * @param \Change\Presentation\Interfaces\Section|integer|null $section
	 * @param array $query
	 * @param string|null $LCID
	 * @return string|null
	 */
	public function contextualURL($document, $section = null, $query = array(), $LCID = null)
	{
		if (is_numeric($document) || $document instanceof \Change\Documents\AbstractDocument)
		{
			if (is_numeric($section))
			{
				$section = $this->getApplicationServices()->getDocumentManager()->getDocumentInstance($section);
			}

			if ($section === null || $section instanceof \Change\Presentation\Interfaces\Section)
			{
				return $this->getUrlManager()->getByDocument($document, $section, $query, $LCID)->normalize()->toString();
			}
		}
		return null;
	}

	/**
	 * @param array $query
	 * @param string|null $fragment
	 * @return string
	 */
	public function currentURL($query = array(), $fragment = null)
	{
		$uri = $this->getUrlManager()->getSelf();
		$oldQuery = $uri->getQueryAsArray();
		unset($oldQuery['errId']);

		if (is_array($query) && count($query))
		{
			$oldQuery = array_merge($oldQuery, $query);
		}
		$uri->setQuery($oldQuery);
		if ($fragment)
		{
			$uri->setFragment($fragment);
		}
		return $uri->normalize()->toString();
	}

	/**
	 * @param string $module
	 * @param string $action
	 * @param array $query
	 * @return string
	 */
	public function ajaxURL($module, $action, $query = array())
	{
		$module = is_array($module) ? $module : explode('_', $module);
		$action = is_array($action) ? $action : array($action);
		$pathInfo = array_merge(array('Action'), $module, $action);
		return $this->getUrlManager()->getByPathInfo($pathInfo, $query)->normalize()->toString();
	}

	/**
	 * @param string $functionCode
	 * @param \Change\Presentation\Interfaces\Section|null $section
	 * @param array $query
	 * @return null|string
	 */
	public function functionURL($functionCode, $section = null, $query = array())
	{
		if ($section === null)
		{
			$section = $this->getUrlManager()->getSection();
			if ($section === null)
			{
				$section = $this->getUrlManager()->getWebsite();
			}
		}

		if ($section instanceof \Change\Presentation\Interfaces\Section)
		{
			$uri = $this->getUrlManager()->getByFunction($functionCode, $section, $query);
			return $uri ? $uri->normalize()->toString() : null;
		}
		return null;
	}

	/**
	 * @param string $relativePath
	 * @return string
	 */
	public function resourceURL($relativePath)
	{
		if ($this->getApplication()->inDevelopmentMode())
		{
			return $relativePath;
		}
		return $this->getApplicationServices()->getThemeManager()->getAssetBaseUrl() . $relativePath;
	}

	/**
	 * @param integer $size
	 * @param string $email
	 * @param \Rbs\User\Documents\User $user
	 * @param mixed[] $params
	 * @return null|string
	 */
	public function avatarURL($size, $email, $user = null, array $params = array())
	{
		$avatarManager = $this->getGenericServices()->getAvatarManager();
		$avatarManager->setUrlManager($this->getUrlManager());

		return $avatarManager->getAvatarUrl($size, $email, $user, $params);
	}

	/**
	 * @param \Change\Documents\RichtextProperty $richText
	 * @return string
	 */
	public function richText($richText)
	{
		if ($richText instanceof \Change\Documents\RichtextProperty)
		{
			$context = array('website' => $this->getUrlManager()->getWebsite());
			return $this->getApplicationServices()->getRichTextManager()->render($richText, "Website", $context);
		}
		return htmlspecialchars(strval($richText));
	}

	/**
	 * @param \DateTime|string $dateTime
	 * @return string
	 */
	public function transDate($dateTime)
	{
		if (is_string($dateTime))
		{
			$dateTime = new \DateTime($dateTime);
		}
		if ($dateTime instanceof \DateTime)
		{
			return $this->getApplicationServices()->getI18nManager()->transDate($dateTime);
		}
		return htmlspecialchars(strval($dateTime));
	}

	/**
	 * @param \DateTime|string $dateTime
	 * @return string
	 */
	public function transDateTime($dateTime)
	{
		if (is_string($dateTime))
		{
			$dateTime = new \DateTime($dateTime);
		}
		if ($dateTime instanceof \DateTime)
		{
			return $this->getApplicationServices()->getI18nManager()->transDateTime($dateTime);
		}
		return htmlspecialchars(strval($dateTime));
	}

	/**
	 * @param \DateTime|string $dateTime
	 * @param string $format using this syntax: http://userguide.icu-project.org/formatparse/datetime
	 * @return string
	 */
	public function formatDate($dateTime, $format)
	{
		if (is_string($dateTime))
		{
			$dateTime = new \DateTime($dateTime);
		}
		if ($dateTime instanceof \DateTime)
		{
			$i18n = $this->getApplicationServices()->getI18nManager();
			return $i18n->formatDate($i18n->getLCID(), $dateTime, $format);
		}
		return htmlspecialchars(strval($dateTime));
	}

	/**
	 * @param boolean $boolean
	 * @return string
	 */
	public function boolean($boolean)
	{
		if ($boolean === true)
		{
			return $this->getApplicationServices()->getI18nManager()->trans('m.rbs.generic.yes', array('ucf'));
		}
		elseif ($boolean === false)
		{
			return $this->getApplicationServices()->getI18nManager()->trans('m.rbs.generic.no', array('ucf'));
		}
		return htmlspecialchars(strval($boolean));
	}

	/**
	 * @param float $float
	 * @return string
	 */
	public function float($float)
	{
		if (is_numeric($float))
		{
			$nf = new \NumberFormatter($this->getApplicationServices()->getI18nManager()->getLCID(), \NumberFormatter::DECIMAL);
			return $nf->format($float);
		}
		return htmlspecialchars(strval($float));
	}

	/**
	 * @param integer $integer
	 * @return string
	 */
	public function integer($integer)
	{
		if (is_numeric($integer))
		{
			$nf = new \NumberFormatter($this->getApplicationServices()->getI18nManager()
				->getLCID(), \NumberFormatter::DEFAULT_STYLE);
			return $nf->format($integer);
		}
		return htmlspecialchars(strval($integer));
	}
}