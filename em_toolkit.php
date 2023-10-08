<?php
/*
Plugin Name: 小工具
Version: 0.0.2
Plugin URL: https://www.emlog.net/plugin/detail/622
Description: 一些常用的小工具
Author: emlog
Author URL: https://www.emlog.net/plugin/index/author/577
*/

!defined('EMLOG_ROOT') && exit('access deined!');


class EmToolKit {

    //插件标识
    const ID = 'em_toolkit';
    const NAME = '工具箱插件';
    const VERSION = '0.0.1';

    //实例
    private static $_instance;

    //数据库连接实例
    private $_db;

    //是否初始化
    private $_inited = false;

    /**
     * 单例入口
     * @return EmToolKit
     */
    public static function getInstance() {
        if (self::$_instance === null) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    /**
     * 私有构造函数，保证单例
     */
    private function __construct() {
    }

    /**
     * 初始化函数
     * @return void
     */
    public function init() {
        if ($this->_inited === true) {
            return;
        }
        $this->_inited = true;

        addAction('adm_menu_ext', function () {
            EmToolKit::getInstance()->hookSidebar();
        });
    }

    /**
     * 获取数据表
     */
    public function getTable($table = null) {
        return DB_PREFIX . 'em_stats_' . $table;
    }

    /**
     * 获取数据库连接
     */
    public function getDb() {
        if ($this->_db !== null) {
            return $this->_db;
        }
        $this->_db = Database::getInstance();
        return $this->_db;
    }

    /**
     * 菜单栏挂载函数
     * @return void
     */
    public function hookSidebar() {
        print('<a class="collapse-item" id="menu_plug_em_toolkit" href="plugin.php?plugin=em_toolkit">小工具</a>');
    }

    public function changeDomain($oldDomain, $newDomain) {
        if (!$this->isValidUrl($oldDomain)) {
            Output::error('原站点地址格式不正确');
        }
        if (!$this->isValidUrl($newDomain)) {
            Output::error('新的站点地址格式不正确');
        }

        // 去除地址最后的 /
        if (substr($oldDomain, strlen($oldDomain) - 1, 1) == '/') {
            $oldDomain = strtolower(substr($oldDomain, 0, strlen($oldDomain) - 1));
        }
        if (substr($newDomain, strlen($newDomain) - 1, 1) == '/') {
            $newDomain = strtolower(substr($newDomain, 0, strlen($newDomain) - 1));
        }
        $sql = 'UPDATE ' . DB_PREFIX . "blog SET content = replace(content,'$oldDomain','$newDomain'), excerpt = replace(excerpt,'$oldDomain','$newDomain')";
        $this->getDb()->query($sql);
        $sql = 'UPDATE ' . DB_PREFIX . "options SET option_value = '$newDomain/' WHERE option_name = 'blogurl'";
        $this->getDb()->query($sql);
        $CACHE = Cache::getInstance();
        $CACHE->updateCache();
        Output::ok('站点域名更换成功!');
    }

    function rssImport($rssurl, $blogtype) {
        // 增加程序的最大执行时间
        ini_set('max_execution_time', '600');
        $items = array();
        $rss = new rss_php;
        if (!empty($rssurl)) {
            if (!preg_match('/https?:\/\/([\w-]+\.)+[\w-]+(\/[\w\-.\/?%&=]*)?/i', $rssurl)) {
                Output::error('RSS地址格式不正确');
            }
            $rss->loadFromUrl($rssurl);
            $items = $rss->getItems();
            if (empty($items)) {
                Output::error('RSS数据未读取到!');
            }
        } else {
            Output::error('请填写rss地址');
        }

        // 分类缓存
        $categorycache = array();
        $query = $this->getDb()->query('SELECT sid,sortname FROM ' . DB_PREFIX . 'sort');
        while ($row = $this->getDb()->fetch_array($query)) {
            $categorycache[$row['sid']] = $row['sortname'];
        }
        // 用户缓存
        $usercache = array();
        $query = $this->getDb()->query('SELECT uid,username FROM ' . DB_PREFIX . 'user');
        while ($row = $this->getDb()->fetch_array($query)) {
            $usercache[$row['uid']] = $row['username'];
        }

        $foreignblogapp = array('wordpress', 'Movable Type', 'Typecho');
        $importcount = 0;
        foreach ($items as $key => $item) {
            // 标题
            if (isset($item['title'])) {
                $title = addslashes($item['title']);
            }
            // 内容
            if (isset($item['description'])) {
                $content = addslashes($item['description']);
            }
            // 针对 wordpress RSS的处理
            if (in_array($blogtype, $foreignblogapp)) {
                // 摘要
                if (isset($item['description'])) {
                    $excerpt = addslashes($item['description']);
                }
                // 内容
                if (isset($item['content:encoded'])) {
                    $content = addslashes($item['content:encoded']);
                }
            }

            // 分类
            $sortid = -1;
            if (isset($item['category'])) {
                // 多个分类默认取第一个
                if (is_array($item['category'])) {
                    $category = $item['category'][0];
                }

                if (is_string($item['category'])) {
                    $category = $item['category'];
                }
                // 判断该分类是否已经添加了
                if (in_array($category, $categorycache)) {
                    $sortid = array_search($category, $categorycache);
                    if (empty($sortid)) {
                        $sortid = -1;
                    }
                } else {
                    // 自动增加分类
                    $sql = 'INSERT INTO ' . DB_PREFIX . "sort (sortname,taxis,description) VALUES ('$category',0,'')";
                    $this->getDb()->query($sql);
                    $tmpid = (string)$this->getDb()->insert_id();
                    // 增加到分类缓存中
                    $categorycache[$tmpid] = $category;
                    $sortid = $tmpid;
                }
            }
            // 发布时间
            if (isset($item['pubDate'])) {
                $date = strtotime($item['pubDate']);
            }
            // 发布人
            if (isset($item['author'])) {
                if (in_array($item['author'], $usercache)) {
                    $author = array_search($item['author'], $usercache);
                } else {
                    $useid = array_keys($usercache);
                    $author = $useid[0];
                }
            } else {
                $useid = array_keys($usercache);
                $author = $useid[0];
            }
            $sql = 'INSERT INTO ' . DB_PREFIX . "blog (title, date, content, excerpt, author, sortid, type, views, comnum, attnum, top, hide, allow_remark, password) VALUES ('$title', '$date', '$content', '$excerpt', $author, $sortid,'blog',0,0,0,'n','n','y','')";
            $this->getDb()->query($sql);
            $importcount++;
        }
        Output::ok("$importcount 条日志被成功导入!<br>请到emlog后台更新缓存!");
    }

    function repairTables() {
        $db = MySqlii::getInstance();
        $tables = $db->listTables();
        $msg = '';
        foreach ($tables as $table) {
            if (empty($table)) {
                continue;
            }
            if (!empty($table)) {
                $db->query('REPAIR TABLE `' . $table . '`');
                $msg .= '数据表: ' . $table . ' 修复完成<br>';
            }
        }
        Output::ok($msg);
    }

    private function isValidUrl($url) {
        // 使用filter_var函数验证URL格式
        if (filter_var($url, FILTER_VALIDATE_URL) !== false) {
            // 检查URL是否以http或https开头
            if (strpos($url, 'http://') === 0 || strpos($url, 'https://') === 0) {
                return true;
            }
        }
        return false;
    }
}

/*
	RSS_PHP - the PHP DOM based RSS Parser
	Author: <rssphp.net>
	Published: 200801 :: blacknet :: via rssphp.net

	RSS_PHP is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY.

	Usage:
		See the documentation at http://rssphp.net/documentation
	Examples:
		Can be found online at http://rssphp.net/examples
*/

class rss_php {

    public $document;
    public $channel;
    public $items;

    # load RSS by URL
    public function loadFromUrl($url = false, $unblock = true) {
        if ($url) {
            $content = $this->fetchUrlContent($url);
            $content = strip_invalid_xml_chars2($content);
            $this->loadParser($content);
        }
    }

    private function fetchUrlContent($url) {
        $ch = curl_init($url);

        // Set cURL options
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // You may want to handle SSL verification better in production
        // Add any other cURL options you need...

        $content = curl_exec($ch);

        if ($content === false) {
            // Handle cURL error
            throw new Exception('cURL error: ' . curl_error($ch));
        }

        curl_close($ch);

        return $content;
    }

    # load raw RSS data
    public function loadRSS($rawxml = false) {
        if ($rawxml) {
            $this->loadParser($rawxml);
        }
    }

    # return full rss array
    public function getRSS($includeAttributes = false) {
        if ($includeAttributes) {
            return $this->document;
        }
        return $this->valueReturner();
    }

    # return channel data
    public function getChannel($includeAttributes = false) {
        if ($includeAttributes) {
            return $this->channel;
        }
        return $this->valueReturner($this->channel);
    }

    # return rss items
    public function getItems($includeAttributes = false) {
        if ($includeAttributes) {
            return $this->items;
        }
        return $this->valueReturner($this->items);
    }


    private function loadParser($rss = false) {
        if ($rss) {
            $this->document = array();
            $this->channel = array();
            $this->items = array();
            $DOMDocument = new DOMDocument;
            $DOMDocument->strictErrorChecking = false;
            $DOMDocument->loadXML($rss);
            $this->document = $this->extractDOM($DOMDocument->childNodes);
        }
    }

    private function valueReturner($valueBlock = false) {
        if (!$valueBlock) {
            $valueBlock = $this->document;
        }
        foreach ($valueBlock as $valueName => $values) {
            if (isset($values['value'])) {
                $values = $values['value'];
            }
            if (is_array($values) && !empty($values)) {
                $valueBlock[$valueName] = $this->valueReturner($values);
            } else {
                $valueBlock[$valueName] = $values;
            }
        }
        return $valueBlock;
    }

    private function extractDOM($nodeList, $parentNodeName = false) {
        $itemCounter = 0;
        $tempNode = array();
        foreach ($nodeList as $values) {
            if (substr($values->nodeName, 0, 1) != '#') {
                if ($values->nodeName == 'item') {
                    $nodeName = $values->nodeName . ':' . $itemCounter;
                    $itemCounter++;
                } else {
                    $nodeName = $values->nodeName;
                }
                if (is_array($tempNode) == false)
                    $tempNode = array();
                $tempNode[$nodeName] = array();
                if ($values->attributes) {
                    for ($i = 0; $values->attributes->item($i); $i++) {
                        $tempNode[$nodeName]['properties'][$values->attributes->item($i)->nodeName] = $values->attributes->item($i)->nodeValue;
                    }
                }
                if (!$values->firstChild) {
                    $tempNode[$nodeName]['value'] = $values->textContent;
                } else {
                    $tempNode[$nodeName]['value'] = $this->extractDOM($values->childNodes, $values->nodeName);
                }
                if (in_array($parentNodeName, array('channel', 'rdf:RDF'))) {
                    if ($values->nodeName == 'item') {
                        $this->items[] = $tempNode[$nodeName]['value'];
                    } elseif (!in_array($values->nodeName, array('rss', 'channel'))) {
                        $this->channel[$values->nodeName] = $tempNode[$nodeName];
                    }
                }
            } elseif (substr($values->nodeName, 1) == 'text') {
                $tempValue = trim(preg_replace('/\s\s+/', ' ', str_replace("\n", ' ', $values->textContent)));
                if ($tempValue) {
                    $tempNode = $tempValue;
                }
            } elseif (substr($values->nodeName, 1) == 'cdata-section') {
                $tempNode = $values->textContent;
            }
        }
        return $tempNode;
    }

}

// 过滤xml中的非法字符
function strip_invalid_xml_chars2($in) {
    $out = "";
    $length = strlen($in);
    for ($i = 0; $i < $length; $i++) {
        $current = ord($in[$i]);
        if (($current == 0x9) || ($current == 0xA) || ($current == 0xD) || (($current >= 0x20) && ($current <= 0xD7FF)) || (($current >= 0xE000) && ($current <= 0xFFFD)) || (($current >= 0x10000) && ($current <= 0x10FFFF))) {
            $out .= chr($current);
        } else {
            $out .= " ";
        }
    }
    return $out;
}

EmToolKit::getInstance()->init();
