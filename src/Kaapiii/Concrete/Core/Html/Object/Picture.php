<?php
namespace Kaapiii\Concrete\Core\Html\Object;

use HtmlObject\Element;
use HtmlObject\Image;


class Picture extends Element
{
    /**
     * Default element
     *
     * @var string
     */
    protected $element = 'picture';

    /**
     * Whether the element is self closing
     *
     * @var boolean
     */
    protected $isSelfClosing = false;

    /**
     * Default element for nested children
     *
     * @var string
     */
    protected $defaultChild = 'source';

    ////////////////////////////////////////////////////////////////////
    //////////////////////////// CORE METHODS //////////////////////////
    ////////////////////////////////////////////////////////////////////

    public function __construct(array $sources = array(), $fallbackSrc, $attributes = array())
    {
        $this->sources($sources);
        $this->fallback($fallbackSrc);
        $this->seoFallback($fallbackSrc);
    }

    /**
     * Static alias for constructor
     *
     * @param string $element
     * @param string|null|Tag $value
     * @param array $attributes
     * @return                Table
     */
    public static function create($sources = array(), $fallbackSrc = false, $attributes = array())
    {
        return new static($sources, $fallbackSrc, $attributes);
    }

    ////////////////////////////////////////////////////////////////////
    /////////////////////////////// CHILDREN ///////////////////////////
    ////////////////////////////////////////////////////////////////////

    public function sources($sources)
    {
        //https://developer.mozilla.org/de/docs/Web/HTML/Element/source
        $this->nest("<!--[if IE 9]><video style='display: none;'><![endif]-->");
        foreach($sources as $source) {
            $path = $source['src'];
            $width = $source['width'];
            $source = SourceLazy::create();
            //$source->srcset('');
            $source->setAttribute('data-src', $path);

            if ($width != 0) {
                $source->media("(min-width: $width)");
            }
            $this->setChild($source);
        }

        $this->nest("<!--[if IE 9]></video><![endif]-->");

        return $this;
    }

    /**
     * Add img tag for older browsers
     *
     * @param string $src
     */
    public function fallback($src)
    {
        $img = Image::create();
        $img->src('');
        $img->setAttribute('data-src', $src);
        $this->setChild($img);
    }

    /**
     * Add img tag in noscript, so it's more SEO frendly
     *
     * @param string $src
     */
    public function seoFallback($src){
        $img = Image::create();
        $img->src($src);
        $this->nest('<noscript>');
        $this->setChild($img);
        $this->nest('</noscript>');
    }

    public function alt($alt)
    {
        foreach($this->getChildren() as $child) {
            if ($child instanceof Image || $child instanceof Source) {
                $child->alt($alt);
            }
        }
    }

    public function title($title)
    {
        foreach($this->getChildren() as $child) {
            if ($child instanceof Image || $child instanceof Source) {
                $child->title($title);
            }
        }
    }

    public function addClass($classes)
    {
        $sources = $this->getChildren();
        foreach($sources as $source) {
            $source->addClass($classes);
        }
    }

}
