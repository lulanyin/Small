<?php
namespace Small;

use Small\lib\util\Arr;
use Small\lib\util\File;
use Small\lib\util\Request;

class Lang {
    /**
     * 语言的所有文本
     * @var null
     */
    private static $languages = null;

    /**
     * 加载语言
     * @param null $language
     */
    public static function loadLanguage($language = null){
        $langSet = Config::get("server.language");
        $langList = $langSet["list"] ?? ["zh", "en"];
        $defaultLang = $langSet['default'] ?? $langList[0];
        $language = $language ?? Request::getCookie("language", $defaultLang);
        $language = in_array($language, $langList) ? $language : $langList[0];
        $languageFilesPath = Config::get("define.resources")."/languages/{$language}";
        $files = File::getFiles($languageFilesPath, "php", null);
        if(!empty($files)){
            foreach ($files as $file){
                $name = substr($file['filename'],0, -4);
                static::$languages[$name] = include $languageFilesPath."/{$file['filename']}";
            }
        }else{
            static::$languages = [];
        }
    }

    /**
     * 获取语言文本
     * @param $key
     * @param null $language
     * @return array|mixed|null|string
     */
    public static function getValue($key, $language=null){
        if(is_null(static::$languages)){
            static::loadLanguage($language);
        }
        $keys = explode(".", $key);
        if(count($keys)==1){
            $key = "default.{$key}";
        }else{
            if(in_array($keys[0], array_keys(static::$languages))){
                return Arr::get(static::$languages, $key);
            }
        }
        return Arr::get(static::$languages, $key);
    }
}