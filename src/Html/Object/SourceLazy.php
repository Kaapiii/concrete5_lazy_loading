<?php
namespace Concrete\Package\Concrete5LazyLoading\Src\Html\Object;

use HtmlObject\Element;

class SourceLazy extends Element
{
    /**
     * Default element
     *
     * @var string
     */
    protected $element = 'br';

    /**
     * Whether the element is self closing
     *
     * @var boolean
     */
    protected $isSelfClosing = true;


    public function __construct()
    {
    }
}
