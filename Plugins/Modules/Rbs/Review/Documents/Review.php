<?php
namespace Rbs\Review\Documents;

use Change\Presentation\PresentationServices;

/**
 * @name \Rbs\Review\Documents\Review
 */
class Review extends \Compilation\Rbs\Review\Documents\Review
{
	public function getLabel()
	{
		if (!parent::getLabel())
		{
			//TODO: do something right
			return ' ';
		}
		return parent::getLabel();
	}

	protected function onCreate()
	{
		if ($this->isPropertyModified('content'))
		{
			$this->transformMarkdownToHtml();
		}
	}

	protected function onUpdate()
	{
		if ($this->isPropertyModified('content'))
		{
			$this->transformMarkdownToHtml();
		}
	}

	/**
	 * @return array|\Change\Documents\AbstractDocument
	 */
	public function getPublicationSections()
	{
		if ($this->getSection())
		{
			return array($this->getSection());
		}
		return array();
	}

	/**
	 * //TODO only markdown for now
	 */
	private function transformMarkdownToHtml()
	{
		$ps = new PresentationServices($this->getApplicationServices());
		//set a web profile
		$ps->getRichTextManager()->setDocumentServices($this->getDocumentServices())->render($this->getContent(), 'Admin');
	}

	/**
	 * @see http://fr.gravatar.com/site/implement/url
	 * @param integer $size
	 * @param string $defaultImageUrl
	 * @param string $rating
	 * @return string|null
	 */
	public function getGravatarUrl($size = '48', $defaultImageUrl = '', $rating = 'g')
	{
		$author = $this->getDocumentManager()->getDocumentInstance($this->getAuthorId());
		if ($author)
		{
			/* @var $author \Rbs\User\Documents\User */
			$url = 'http://www.gravatar.com/avatar/'.md5($author->getEmail()).'?s='.$size.'&amp;r='.$rating;
			if ($defaultImageUrl)
			{
				$url .= '&amp;d='.urlencode($defaultImageUrl);
			}
			return $url;
		}
		return null;
	}

	/**
	 * @param \Change\Http\Web\UrlManager $urlManager
	 * @return array
	 */
	public function getInfoForTemplate($urlManager)
	{
		$target = $this->getTarget();
		/* @var $target \Change\Documents\AbstractDocument|\Change\Documents\Interfaces\Publishable|\Change\Documents\Interfaces\Editable */
		return [
			'id' => $this->getId(),
			'pseudonym' => $this->getPseudonym(),
			'rating' => $this->getRating(),
			'reviewStarRating' => ceil($this->getRating()*(5/100)),
			'reviewDate' => $this->getReviewDate(),
			'content' => $this->getContent()->getHtml(),
			'promoted' => $this->getPromoted(),
			'url' => $urlManager->getCanonicalByDocument($this, $this->getSection()->getWebsite()),
			//TODO: getLabel for target is not a good thing, find another way
			'target' => [ 'title' => $target->getLabel(), 'url' => $urlManager->getCanonicalByDocument($target, $this->getSection()->getWebsite()) ]
		];
	}
}
