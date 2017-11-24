<?php
namespace Concrete\Package\Concrete5LazyLoading;

use \Concrete\Core\Asset\AssetList;
use \Concrete\Core\Asset\Asset;
use \Concrete\Core\Http\ResponseAssetGroup;

defined('C5_EXECUTE') or die(_("Access Denied."));
/**
 * Package Controller
 *
 * @author Markus Liechti <markus@liechti.io>
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class Controller extends \Concrete\Core\Package\Package{
    //put your code here

    protected $pkgHandle = 'concrete5_lazy_loading';
    protected $appVersionRequired = '8.0.0';
    protected $pkgVersion = '1.0.0';
    
    /**
     * @var \Concrete\Core\Support\Facade\Application
     */
    protected $app;

    protected $pkgAutoloaderRegistries = array(
        'src/Kaapiii/Concrete/Core' => '\Kaapiii\Concrete\Core'
    );

    public function getPackageDescription() {
        return t("Adds lazy loading support for images for background images");
    }

    public function getPackageName(){
        return t("Lazy loading for images");
    }

    public function install(){
        $pkg = parent::install();
    }

    public function on_start(){

        // Override the default image binding of 'html/image' in the
        // Concrete\Core\Html\HtmlServiceProvider
        \Concrete\Core\Support\Facade\Application::bind('html/image', '\Kaapiii\Concrete\Core\Html\Image');

        $al = AssetList::getInstance();
        $this->registerAssets($al);
        $this->groupAssets($al);
        
        // Only load the assets before a page is rendered
        // Fixes parsing error on the page tree (dynatree.js) on dashboard/sitemap/full
        $pkg = $this;
        \Events::addListener('on_before_render', function($event) use ($pkg, $al) {
            $pkg->loadAssets($al);
        });
    }

    /**
     *
     * @param AssetList $al
     */
    private function registerAssets(AssetList $al){

        // JS
        $al->register(
            'javascript', 'lazyloadxt', 'node_modules/lazyloadxt/dist/jquery.lazyloadxt.min.js',
            array('version' => '1.1.0', 'position' => Asset::ASSET_POSITION_FOOTER,
            'minify' => false, 'combine' => true), $this->pkgHandle
        );
        $al->register(
            'javascript', 'lazyloadxt-bg', 'node_modules/lazyloadxt/dist/jquery.lazyloadxt.bg.min.js',
            array('version' => '1.1.0', 'position' => Asset::ASSET_POSITION_FOOTER,
            'minify' => false, 'combine' => true), $this->pkgHandle
        );
        $al->register(
            'javascript', 'lazyloadxt-main', 'js/lazyloadxt.js',
            array('version' => '1.0.0', 'position' => Asset::ASSET_POSITION_FOOTER,
            'minify' => true, 'combine' => true), $this->pkgHandle
        );

        // CSS
        $al->register(
            'css', 'lazyloadxt-fadein', '/node_modules/lazyloadxt/dist/jquery.lazyloadxt.fadein.min.css',
            array('version' => '1.1.0', 'position' => Asset::ASSET_POSITION_HEADER,
            'minify' => true, 'combine' => true), $this->pkgHandle
        );
        $al->register(
            'css', 'lazyloadxt-css', '/css/lazyloadxt.css',
            array('version' => '1.1.0', 'position' => Asset::ASSET_POSITION_HEADER,
            'minify' => true, 'combine' => true), $this->pkgHandle
        );
    }

    /**
     * Group all related assets
     *
     * @param AssetList $al
     */
    private function groupAssets(AssetList $al){
       $al->registerGroup('lazyloadxt', array(
           array('css', 'lazyloadxt-fadein'),
           array('css', 'lazyloadxt-css'),
           array('javascript', 'lazyloadxt'),
           array('javascript', 'lazyloadxt-main'),
           array('javascript', 'lazyloadxt-bg'),
       ));
    }

    /**
     * Load related assets
     */
    private function loadAssets(AssetList $al){
       // Load assetes

       // first get the AssetGroup
       $assetGroup = $al->getAssetGroup('lazyloadxt');

       $req = ResponseAssetGroup::get();
       $req->requireAsset($assetGroup);
    }
}
