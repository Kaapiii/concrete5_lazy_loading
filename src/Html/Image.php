<?php
namespace Concrete\Package\Concrete5LazyLoading\Src\Html;

use \Concrete\Core\Page\Theme\Theme as PageTheme;
use \Concrete\Package\LazyLoading\Src\Html\Object\Picture;

class Image
{
    protected $usePictureTag = false;
    protected $tag;

    protected $theme;

    protected function loadPictureSettingsFromTheme()
    {
        $c = \Page::getCurrentPage();
        if (is_object($c)) {
            $th = PageTheme::getByHandle($c->getPageController()->getTheme());
            if (is_object($th)) {
                $this->theme = $th;
                $this->usePictureTag = count($th->getThemeResponsiveImageMap()) > 0;
            }
        }
    }

    /**
     * @param \File $f
     * @param null $usePictureTag
     */
    public function __construct(File $f = null, $usePictureTag = null)
    {
        if (!is_object($f)) {
            return false;
        }

        if (isset($usePictureTag)) {
            $this->usePictureTag = $usePictureTag;
        } else {
            $this->loadPictureSettingsFromTheme();
        }

        if ($this->usePictureTag) {
            if (!isset($this->theme)) {
                $c = \Page::getCurrentPage();
                $this->theme = $c->getCollectionThemeObject();
            }
            $sources = array();
            $fallbackSrc = $f->getRelativePath();
            if (!$fallbackSrc) {
                $fallbackSrc = $f->getURL();
            }
            foreach ($this->theme->getThemeResponsiveImageMap() as $thumbnail => $width) {
                $type = \Concrete\Core\File\Image\Thumbnail\Type\Type::getByHandle($thumbnail);
                if ($type != null) {
                    $src = $f->getThumbnailURL($type->getBaseVersion());
                    $sources[] = array('src' => $src, 'width' => $width);
                    if ($width == 0) {
                        $fallbackSrc = $src;
                    }
                }
            }
            $this->tag = \Concrete\Core\Html\Object\Picture::create($sources, $fallbackSrc);
        } else {
            // Return a simple image tag.
            $path = $f->getRelativePath();
            if (!$path) {
                $path = $f->getURL();
            }
            // pass adittional attributes to the element
            $attributes = array(
                'data-src' => $path,
            );
            // omit the path for lazy loading purposes
            $this->tag = \HtmlObject\Image::create('','',$attributes);
            $this->tag->width($f->getAttribute('width'));
            $this->tag->height($f->getAttribute('height'));
            //$this->tag->addClass('xt-lazy');
            // add the image in noscirpt tags for search engines

        }
    }

    /**
     * @return \HTMLObject\Element\Tag
     */
    public function getTag()
    {
        return $this->tag;
    }

    /**
     *
     * @param string $thumbnailHandle
     * @param array $attributes
     * @return string
     */
    public function getImageTag($thumbnailHandle = '', $attributes = array()){
        if(!empty($thumbnailHandle)){
            $type = \Concrete\Core\File\Image\Thumbnail\Type\Type::getByHandle($thumbnailHandle);
            if($type){
                $type = \Concrete\Core\File\Image\Thumbnail\Type\Type::getByHandle($thumbnailHandle);
                $src = $this->file->getThumbnailURL($type->getBaseVersion());
                $srcset = $this->getRetinaSrcSetThumbnailUrl($type);
                if(!array_key_exists('srcset', $attributes)){
                    $attributes['srcset'] = $srcset;
                }
            }else{
                $src = $this->file->getURL();
            }
        }else{
            $src = $this->file->getURL();
        }
        
        $alt = $this->file->getDescription();

        if(!array_key_exists('sizes', $attributes)){
            // add default values
            $attributes['sizes'] = '100vw';
        }

        $imageTag = \HtmlObject\Image::create($src, $alt, $attributes);
        return $imageTag;
    }

    /**
     * Get a optimized image tag for lazyloading. The srcset value contains
     * if present a retina image. The src values is empty. This only works with
     * a javascript plugin like lazyloadxt.js or equivalent libraries
     *
     * @param string $thumbnailHandle
     * @param array $attributes
     * @return string   Image html tag with no "src" value set
     */
    public function getLazyloadingImageTag($thumbnailHandle = '', $attributes = array()){

        if(!empty($thumbnailHandle)){
            $type = \Concrete\Core\File\Image\Thumbnail\Type\Type::getByHandle($thumbnailHandle);
            if($type){
                // Thumbnail type is specified - so there is probably also a retina picture
                if(!array_key_exists('data-srcset', $attributes)){
                    $attributes['data-srcset'] = $this->getSrcSetURLs($type);
                }
            }else{
                // just in case -> fallback for wrong written handles
                $attributes['data-srcset'] = $this->file->getURL();
            }
        }else{
            // not type specified get the original version - has no retina version
            $attributes['data-srcset'] = $this->file->getURL();
        }

        $alt = $this->file->getDescription();

        if(!array_key_exists('sizes', $attributes)){
            // add default values
            $attributes['sizes'] = '100vw';
        }

        $imageTag = \HtmlObject\Image::create('', $alt, $attributes);
        $imageTag .= $this->getLazyLoadingImageTagFallback($thumbnailHandle, $attributes);
        return $imageTag;
    }


    /**
     * Add img tag in noscript, so it's more SEO frendly
     *
     * @param string $src
     */
    protected function getLazyLoadingImageTagFallback($thumbnailHandle = '', $attributes = array()){

        $allowedAttributes = array(
            'src' => '',
            'srcset' => '',
            'alt' => '',
            'title' => '',
            'class' => '',
            'itemprop' => '',
        );
        
        $attributes = array_intersect_key($allowedAttributes, $attributes);

        if(!empty($thumbnailHandle)){
            $type = \Concrete\Core\File\Image\Thumbnail\Type\Type::getByHandle($thumbnailHandle);
            if($type){
                $type = \Concrete\Core\File\Image\Thumbnail\Type\Type::getByHandle($thumbnailHandle);
                $src = $this->file->getThumbnailURL($type->getBaseVersion());
                $srcset = $this->getRetinaSrcSetThumbnailUrl($type);
                if(!array_key_exists('srcset', $attributes)){
                    $attributes['srcset'] = $srcset;
                }
            }else{
                $src = $this->file->getURL();
            }
        }else{
            $src = $this->file->getURL();
        }

        $alt = $this->file->getDescription();

        $imageTag = \HtmlObject\Image::create($src, $alt, $attributes);
        return $this->wrapInNoScript($imageTag);
    }

    protected function wrapInNoScript($image){
        $el = \HtmlObject\Element::noscript();
        $el->setChild($image);
        return $el;
    }

    public function getLazyLoadingImageAndRetinaTags($thumbnailHandle = '', $attributes = array()){
        if(!empty($thumbnailHandle)){
            $type = \Concrete\Core\File\Image\Thumbnail\Type\Type::getByHandle($thumbnailHandle);
            if($type){
                // Thumbnail type is specified - so there is probably also a retina picture
                if(!array_key_exists('data-src', $attributes)){
                    $attributes['data-src'] = $this->file->getThumbnailURL($type->getBaseVersion());
                }
                if(!array_key_exists('data-src-2x', $attributes)){
                    $attributes['data-src-2x'] = $this->file->getThumbnailURL($type->getDoubledVersion());
                }
            }else{
                // just in case -> fallback for wrong written handles
                $src = $this->file->getURL();
            }
        }else{
            // not type specified get the original version - has no retina version
            $src = $this->file->getURL();
        }

        $alt = $this->file->getDescription();

        if(!array_key_exists('sizes', $attributes)){
            // add default values
            $attributes['sizes'] = '100vw';
        }

        $imageTag = \HtmlObject\Image::create('', $alt, $attributes);
        $imageTag .= $this->getLazyLoadingImageTagFallback($thumbnailHandle, $attributes);
        return $imageTag;
    }

    /**
     * Get all srcset urls as coma separated string
     * "img.jpg 200w, retina/img2.jpg 400w"
     *
     * @param \Concrete\Core\File\Image\Thumbnail\Type\Type $type
     * @return type
     */
    public function getSrcSetURLs(\Concrete\Core\File\Image\Thumbnail\Type\Type $type){
        $urls[] = $this->getSrcSetThumbnailUrl($type);
        $urls[] = $this->getRetinaSrcSetThumbnailUrl($type);
        return implode(', ', $urls);
    }

    /**
     * Get the prepared url for the retina thumbnail
     *
     * @param \Concrete\Core\File\Image\Thumbnail\Type\Type $type
     * @return string
     */
    public function getRetinaSrcSetThumbnailUrl(\Concrete\Core\File\Image\Thumbnail\Type\Type $type){
        $srcset = $this->file->getThumbnailURL($type->getDoubledVersion());
        $width = intval($type->getWidth())*2;
        $srcset .= ' ' . $width . 'w';
        return $srcset;
    }

    /**
     * Get the prepared url for the default thumbnail
     *
     * @param \Concrete\Core\File\Image\Thumbnail\Type\Type $type
     * @return string
     */
    public function getSrcSetThumbnailUrl(\Concrete\Core\File\Image\Thumbnail\Type\Type $type){
        $src = $this->file->getThumbnailURL($type->getBaseVersion());
        $src .= ' '.$type->getWidth().'w';
        return $src;
    }
}
