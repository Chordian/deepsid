<?php

namespace JBBCode;

require_once 'CodeDefinition.php';
require_once 'CodeDefinitionBuilder.php';
require_once 'CodeDefinitionSet.php';
require_once 'validators/CssColorValidator.php';
require_once 'validators/UrlValidator.php';

/**
 * Provides a default set of common bbcode definitions.
 *
 * Modified by JCH for CSDb comments in DeepSID.
 *
 * @author jbowens
 */
class DefaultCodeDefinitionSet implements CodeDefinitionSet
{

    /** @var CodeDefinition[] The default code definitions in this set. */
    protected $definitions = array();

    /**
     * Constructs the default code definitions.
     */
    public function __construct()
    {
        /* [b] bold tag */
        $builder = new CodeDefinitionBuilder('b', '<b>{param}</b>');
        $this->definitions[] = $builder->build();

        /* [i] italics tag */
        $builder = new CodeDefinitionBuilder('i', '<i>{param}</i>');
        $this->definitions[] = $builder->build();

        /* [u] underline tag */
        $builder = new CodeDefinitionBuilder('u', '<u>{param}</u>');
        $this->definitions[] = $builder->build();

        /* [s] strikeout tag */
        $builder = new CodeDefinitionBuilder('s', '<del>{param}</del>');
        $this->definitions[] = $builder->build();

        /* [code] code tag */
        $builder = new CodeDefinitionBuilder('code', '<code>{param}</code>');
        $this->definitions[] = $builder->build();

        /* [quote] quote tag */
        $builder = new CodeDefinitionBuilder('quote', '<span class="quote">Quote:</span><div class="quote">{param}</div>');
		$builder->setUseOption(false)->setParseContent(true);
        $this->definitions[] = $builder->build();

        /* [quote=scener] quote tag */
        $builder = new CodeDefinitionBuilder('quote', '<span class="quote">Quote by {option}:</span><div class="quote">{param}</div>');
		$builder->setUseOption(true)->setParseContent(true);
        $this->definitions[] = $builder->build();

        $urlValidator = new \JBBCode\validators\UrlValidator();

        /* [url] link tag */
        $builder = new CodeDefinitionBuilder('url', '<a href="{param}" target="_blank">{param}</a>');
        $builder->setParseContent(false)->setBodyValidator($urlValidator);
        $this->definitions[] = $builder->build();

        /* [url=http://example.com] link tag */
        $builder = new CodeDefinitionBuilder('url', '<a href="{option}" target="_blank">{param}</a>');
        $builder->setUseOption(true)->setParseContent(true)->setOptionValidator($urlValidator);
        $this->definitions[] = $builder->build();

        /* [img] image tag */
        $builder = new CodeDefinitionBuilder('img', '<img src="{param}" />');
        $builder->setUseOption(false)->setParseContent(false)->setBodyValidator($urlValidator);
        $this->definitions[] = $builder->build();

        /* [img=alt text] image tag */
        $builder = new CodeDefinitionBuilder('img', '<img src="{param}" alt="{option}" />');
        $builder->setUseOption(true)->setParseContent(false)->setBodyValidator($urlValidator);
        $this->definitions[] = $builder->build();

        /* [color] color tag */ /* Not used by CSDb comments */
        $builder = new CodeDefinitionBuilder('color', '<span style="color: {option}">{param}</span>');
        $builder->setUseOption(true)->setOptionValidator(new \JBBCode\validators\CssColorValidator());
        $this->definitions[] = $builder->build();
    }

    /**
     * Returns an array of the default code definitions.
     *
     * @return CodeDefinition[]
     */
    public function getCodeDefinitions()
    {
        return $this->definitions;
    }

}
