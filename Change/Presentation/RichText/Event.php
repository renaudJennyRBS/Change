<?php
namespace Change\Presentation\RichText;

/**
 * @name \Change\Presentation\RichText\Event
 */
class Event extends \Change\Events\Event
{
	/**
	 * @var ParserInterface
	 */
	protected $parser;

	/**
	 * @var string
	 */
	protected $profile;

	/**
	 * @var string
	 */
	protected $editor;

	/**
	 * @param string $editor
	 * @return $this
	 */
	public function setEditor($editor)
	{
		$this->editor = $editor;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getEditor()
	{
		return $this->editor;
	}

	/**
	 * @var array
	 */
	protected $context;

	/**
	 * @param array $context
	 * @return $this
	 */
	public function setContext($context)
	{
		$this->context = $context;
		return $this;
	}

	/**
	 * @return array
	 */
	public function getContext()
	{
		return $this->context;
	}

	/**
	 * @param string $profile
	 * @return $this
	 */
	public function setProfile($profile)
	{
		$this->profile = $profile;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getProfile()
	{
		return $this->profile;
	}

	/**
	 * @param ParserInterface $parser
	 * @return $this
	 */
	public function setParser(ParserInterface $parser)
	{
		$this->parser = $parser;
		return $this;
	}

	/**
	 * @return ParserInterface
	 */
	public function getParser()
	{
		return $this->parser;
	}
}