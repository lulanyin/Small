<?php
namespace Small;

interface IHttpController{

    /**
     * 获取GET参数值
     * @param string $name
     * @param string|null $default
     * @param string|null $message
     * @return mixed | string
     */
    public function getQueryString(string $name, string $default = null, string $message = null);

    /**
     * 获取POST参数值
     * @param string $name
     * @param string|null $default
     * @param string|null $message
     * @return mixed
     */
    public function getPostData(string $name, string $default = null, string $message = null);

    /**
     * 获取Cookie
     * @param string $name
     * @param string|null $default
     * @return mixed
     */
    public function getCookie(string $name, string $default = null);

    /**
     * 是不是AJAX请求
     * @return mixed
     */
    public function isAjaxMethod();

    /**
     * 地址跳转
     * @param $route
     * @return mixed
     */
    public function redirect(string $route);
}