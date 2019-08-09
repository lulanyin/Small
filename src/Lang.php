<?php
namespace Small;

use Small\Util\Arr;
use Small\Util\File;
use Small\Http\Request;

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
                self::$languages[$name] = include $languageFilesPath."/{$file['filename']}";
            }
        }else{
            self::$languages = [];
        }
    }

    /**
     * 获取语言文本
     * @param $key
     * @param null $language
     * @return array|mixed|null|string
     */
    public static function getValue($key, $language=null){
        if(is_null(self::$languages)){
            self::loadLanguage($language);
        }
        $keys = explode(".", $key);
        if(count($keys)==1){
            $key = "default.{$key}";
        }else{
            if(in_array($keys[0], array_keys(self::$languages))){
                return Arr::get(self::$languages, $key);
            }
        }
        return Arr::get(self::$languages, $key);
    }
}