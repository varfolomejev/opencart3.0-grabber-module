<?php

namespace viv;

use PHPHtmlParser\Dom;

abstract class Parser
{
    /**
     * @var Dom
     */
    protected $dom;

    protected $url;

    protected $db;

    public function __construct($url, $db)
    {
        $this->db = $db;
        $this->url = $url;
        $dom = new Dom();
        $this->dom = $dom->loadFromUrl($this->url);
    }

    public function getFullUrl($url)
    {
        if(substr($url, 0, 1) == '.') {
            return $url->url . substr($url, 0, -1);
        } else if(strpos($url, 'http://') === false || strpos($url, 'https://') === false) {
            return parse_url($this->url, PHP_URL_SCHEME) . '://' . parse_url($this->url, PHP_URL_HOST) . (substr($url, 0, 1) == '/' ? $url : '/' . $url);
        } else {
            return $url;
        }
    }

    public function downloadFile($url, $destination, $replace = false) {
        if(!$replace && file_exists(DIR_IMAGE . $destination)) {
            return $destination;
        }
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST,false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER,false);
        $data = curl_exec ($ch);
//        $error = curl_error($ch);
        curl_close ($ch);
        $file = fopen(DIR_IMAGE . $destination, "w+");
        $bites = fputs($file, $data);
        fclose($file);
        return $bites ? $destination : null;
    }

    public function slugify($text)
    {
        $text = mb_strtolower(trim($text));
        $manualTransliterate = $this->transliterate($text);
        if($manualTransliterate) {
            return $manualTransliterate;
        }
        $text = mb_convert_encoding(mb_strtolower($text), 'utf-8');

        // replace non letter or digits by -
        $text = preg_replace('~[^\pL\d]+~u', '-', $text);

        // transliterate
        $text = iconv('utf-8', 'ASCII//TRANSLIT', $text);

        // remove unwanted characters
        $text = preg_replace('~[^-\w]+~', '', $text);

        // trim
        $text = trim($text, '-');

        // remove duplicate -
        $text = preg_replace('~-+~', '-', $text);

        // lowercase
        $text = strtolower($text);

        if (empty($text)) {
            return 'n-a';
        }

        return $text;
    }

    public function transliterate($textcyr = null, $textlat = null) {
        $cyr = array(
            'ж',  'ч',  'щ',   'ш',  'ю',  'а', 'б', 'в', 'г', 'д', 'е', 'з', 'и', 'й', 'к', 'л', 'м', 'н', 'о', 'п', 'р', 'с', 'т', 'у', 'ф', 'х', 'ц', 'ъ', 'ь', 'я',
            'Ж',  'Ч',  'Щ',   'Ш',  'Ю',  'А', 'Б', 'В', 'Г', 'Д', 'Е', 'З', 'И', 'Й', 'К', 'Л', 'М', 'Н', 'О', 'П', 'Р', 'С', 'Т', 'У', 'Ф', 'Х', 'Ц', 'Ъ', 'Ь', 'Я');
        $lat = array(
            'zh', 'ch', 'sht', 'sh', 'yu', 'a', 'b', 'v', 'g', 'd', 'e', 'z', 'i', 'j', 'k', 'l', 'm', 'n', 'o', 'p', 'r', 's', 't', 'u', 'f', 'h', 'c', 'y', 'x', 'q',
            'Zh', 'Ch', 'Sht', 'Sh', 'Yu', 'A', 'B', 'V', 'G', 'D', 'E', 'Z', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'R', 'S', 'T', 'U', 'F', 'H', 'c', 'Y', 'X', 'Q');
        if($textcyr) return str_replace($cyr, $lat, $textcyr);
        else if($textlat) return str_replace($lat, $cyr, $textlat);
        else return null;
    }
    abstract public function parse($data = null);
}