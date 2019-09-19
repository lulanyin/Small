<?php
namespace Small\View;

use Exception;
use Small\Config;
use Small\Http\HttpController;
use Smarty;

/**
 * 视图类，用于输出模板
 * Class View
 * @package Small\View
 */
class View{

    /**
     * 赋值到模板上使用的数据
     * @var array
     */
    public $data = [];

    /**
     * 控制器
     * @var HttpController
     */
    public $controller = null;

    /**
     * 模板
     * @var string
     */
    public $template = null;

    /**
     * 原始地址路径，用于自动匹配模板
     * @var array|null
     */
    public $path = null;

    /**
     * @var Smarty
     */
    public $smarty = null;

    /**
     * View constructor.
     * @param $controller
     * @param null|string $method
     * @param null|array $path
     */
    public function __construct($controller = null, string $method = null, array $path = [])
    {
        $this->init($controller, $method, $path);
    }

    /**
     * 赋值变量到模板使用
     * @param string $name
     * @param $value
     * @return View
     */
    public function assign(string $name, $value)
    {
        $this->data[$name] = $value;
        return $this;
    }

    /**
     * @param $controller
     * @param string|null $method
     * @param array $path
     */
    public function init($controller = null, string $method = null, array $path = [])
    {
        $this->controller = $controller;
        $this->path = $path;
        $this->assign('method', $method);
        $self_url_path = join("/", $path).(!empty($method) && $method!='index' ? "/{$method}" : '');
        $this->assign("url_path", $self_url_path);
        $this->assign("self_url", $self_url_path);
        if(is_null($this->smarty)){
            $setting = Config::get("server.smarty");
            $setting = is_array($setting) ? $setting : [];
            $this->smarty = new Smarty();
            $this->smarty->left_delimiter = $setting['left_delimiter'] ?? "{";
            $this->smarty->right_delimiter = $setting['right_delimiter'] ?? "}";
            $this->smarty->setCacheDir(defined("CACHE") ? CACHE."/cache" : __DIR__."/../../runtime/cache/cache");
            $this->smarty->setCompileDir(defined("CACHE") ? CACHE."/compile" : __DIR__."/../../runtime/cache/compile");
            //赋值一些数据
            $configs = Config::get("public");
            $domain = Config::get("domain");
            $this->assign('configs', $configs);
            $this->assign('domain', $domain);
        }
        $templatePath = str_replace("_", "", $this->path[0] ?? "");
        if(!empty($this->path)){
            try{
                $this->smarty->addTemplateDir(Config::get("define.views")."/".$templatePath);
            }catch (Exception $exception){
                //..
            }
        }
    }

    /**
     * 展示模板
     * @param string|null $template
     * @return string
     */
    public function fetch($template = null)
    {
        if(!is_null($this->controller) && property_exists($this->controller, 'user')){
            $this->assign('me', $this->controller->user);
        }
        if(is_null($template)){
            $this->template = (!is_null($this->controller) && property_exists($this->controller, 'template')) && !empty($this->controller->template) ? $this->controller->template : $this->template;
            $template = !empty($this->template) ? $this->template : (join("/", array_slice($this->path, 1)));
        }
        $template = strrchr($template, ".html") == ".html"
        || strrchr($template, ".htm") == ".htm"
        || strrchr($template, ".tpl") == ".tpl" ? $template : ($template.".html");
        $this->assign('get', $_GET);
        $this->assign('post', $_POST);
        $this->assign('globals', $GLOBALS);
        foreach ($this->data as $key=>$value){
            $this->smarty->assign($key, $value);
        }
        try{
            return $this->smarty->fetch($template);
        }catch (Exception $e){
            return $e->getMessage();
        }
    }

    /**
     * 添加模板目录
     * @param $dir
     */
    public function addTemplateDir($dir){
        if(is_dir($dir)){
            try{
                $this->smarty->addTemplateDir($dir);
            }catch (Exception $e){
                //无法添加
                //echo $e->getMessage();
            }
        }
    }

    public function templateExists($template){
        $template = strrchr($template, ".html") == ".html"
        || strrchr($template, ".htm") == ".htm"
        || strrchr($template, ".tpl") == ".tpl" ? $template : ($template.".html");
        return $this->smarty->templateExists($template);
    }

}