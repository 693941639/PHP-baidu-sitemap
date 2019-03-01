<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * 网站sitemap控制
 */
class Sitemap {

    private $writer;		                        // XMLWriter对象
    private $domain;		                        // 网站地图根域名
    private $xmlFile = "sitemap";					// 网站地图xml文件（不含后缀.xml）
    private $currXmlFileFullPath = "";				// 网站地图xml文件当前全路径
    private $current_item = 0;						// 网站地图item个数（序号）
    private $current_sitemap = 0;					// 网站地图的个数（序号）
    private $filePath = "sitemap_index.xml";        // 生成全部网站的sitemap路径及文件名

    const DEFAULT_PRIORITY = 0.5;
    const SITEMAP_ITEMS = 50000;
    const SITEMAP_SEPERATOR = '-';
    const INDEX_SUFFIX = 'index';
    const SITEMAP_EXT = '.xml';

    /**
     * 构造函数
     * Sitemap constructor.
     */
    public function __construct() {}

    /**
     * 设置网站地图根域名，开头用 http:// or https://, 结尾不要反斜杠/
     * @param string $domain
     */
    public function setDomain($domain) {
        if(substr($domain, -1) == "/") {
            $domain = substr($domain, 0, strlen($domain)-1);
        }
        $this->domain = $domain;
        return $this;
    }

    /**
     * 返回网站根域名
     */
    private function getDomain() {
        return $this->domain . '/';
    }

    /**
     * 设置网站地图的xml文件名
     */
    public function setXmlFile($xmlFile) {
        $base = basename($xmlFile);
        $dir = dirname($xmlFile);
        if(!is_dir($dir)) {
            $res = mkdir($dir, 0777, true);
            if($res) {
                echo "mkdir $dir success";
            } else {
                echo "mkdir $dir fail.";
            }
        }

        $this->xmlFile = $xmlFile;
        return $this;
    }

    /**
     * 返回网站地图的xml文件名
     */
    private function getXmlFile() {
        return $this->xmlFile;
    }

    /**
     * 设置XMLWriter对象
     */
    private function setWriter(XMLWriter $writer) {
        $this->writer = $writer;
    }

    /**
     * 返回XMLWriter对象
     */
    private function getWriter() {
        return $this->writer;
    }

    /**
     * 返回网站地图的当前item
     * @return int
     */
    private function getCurrentItem() {
        return $this->current_item;
    }

    /**
     * 设置网站地图的item个数加1
     */
    private function incCurrentItem() {
        $this->current_item = $this->current_item + 1;
    }

    /**
     * 返回当前网站地图（默认50000个item则新建一个网站地图）
     * @return int
     */
    private function getCurrentSitemap() {
        return $this->current_sitemap;
    }

    /**
     * 设置网站地图个数加1
     */
    private function incCurrentSitemap() {
        $this->current_sitemap = $this->current_sitemap + 1;
    }

    private function getXMLFileFullPath() {
        $xmlfileFullPath = "";
        if ($this->getCurrentSitemap()) {
            $xmlfileFullPath = $this->getXmlFile() . self::SITEMAP_SEPERATOR . $this->getCurrentSitemap() . self::SITEMAP_EXT;	// 第n个网站地图xml文件名 + -n + 后缀.xml
        } else {
            $xmlfileFullPath = $this->getXmlFile() . self::SITEMAP_EXT;	// 第一个网站地图xml文件名 + 后缀.xml
        }
        $this->setCurrXmlFileFullPath($xmlfileFullPath);		// 保存当前xml文件全路径
        return $xmlfileFullPath;
    }

    public function getCurrXmlFileFullPath() {
        return $this->currXmlFileFullPath;
    }

    private function setCurrXmlFileFullPath($currXmlFileFullPath) {
        $this->currXmlFileFullPath = $currXmlFileFullPath;
    }

    /**
     * 开始生成各部分XML文档
     */
    private function startSitemap() {
        $this->setWriter(new XMLWriter());
        $this->getWriter()->openURI($this->getXMLFileFullPath());	// 获取xml文件全路径

        $this->getWriter()->startDocument('1.0', 'UTF-8');
        $this->getWriter()->setIndentString("\t");
        $this->getWriter()->setIndent(true);
        $this->getWriter()->startElement('urlset');
        chmod($this->getXMLFileFullPath(),0777);
    }

    /**
     * 写入xml元素
     */
    public function addItem($loc, $priority = self::DEFAULT_PRIORITY, $changefreq = NULL, $lastmod = NULL) {
        if (($this->getCurrentItem() % self::SITEMAP_ITEMS) == 0) {
            if ($this->getWriter() instanceof XMLWriter) {
                $this->endSitemap();
            }
            $this->startSitemap();
            $this->incCurrentSitemap();
        }
        $this->incCurrentItem();
        $this->getWriter()->startElement('url');
        $this->getWriter()->writeElement('loc', $this->getDomain() . $loc);
        $this->getWriter()->writeElement('priority', $priority);
        if ($changefreq) {
            $this->getWriter()->writeElement('changefreq', $changefreq);
        }
        if ($lastmod) {
            $this->getWriter()->writeElement('lastmod', $this->getLastModifiedDate($lastmod));
        }
        $this->getWriter()->endElement();
        return $this;
    }

    /**
     * 返回时间格式为 Y-m-d
     */
    private function getLastModifiedDate($date) {
        if(null == $date) {
            $date = time();
        }
        if (ctype_digit($date)) {
            return date('Y-m-d H:i:s', $date);
        } else {
            $date = strtotime($date);
            return date('Y-m-d H:i:s', $date);
        }
    }

    /**
     * 结束网站各部分xml文档，配合开始xml文档使用
     */
    public function endSitemap() {
        if (!$this->getWriter()) {
            $this->startSitemap();
        }
        $this->getWriter()->endElement();
        $this->getWriter()->endDocument();
        $this->getWriter()->flush();
    }

    /**
     * 获取网站整个sitemap_index文件的路径
     * @return string
     */
    public function getFilePath()
    {
        return $this->filePath;
    }

    /**
     * 开始生成sitemap_index文件
     */
    private function createIndexSitemap()
    {
        $this->writer = new XMLWriter();
        $this->writer->openMemory();
        $this->writer->startDocument('1.0', 'UTF-8');
        $this->writer->setIndent(true);
        $this->writer->startElement('sitemapindex');
    }

    /**
     * 向sitemap_index文件中添加元素
     * @param $location
     * @param null $lastModified
     */
    public function addIndexSitemap($location, $lastModified = null)
    {
        if ($this->writer === null) {
            $this->createIndexSitemap();
        }

        $this->writer->startElement('sitemap');
        $this->writer->writeElement('loc', $location);
        $this->writer->writeElement('lastmod', $this->getLastModifiedDate($lastModified));
        $this->writer->endElement();
    }

    /**
     * 结束sitemap_index文件
     */
    public function endIndexSitemap()
    {
        if ($this->writer instanceof XMLWriter) {
            $this->writer->endElement();
            $this->writer->endDocument();
            $filePath = $this->getFilePath();
            file_put_contents($filePath, $this->writer->flush());
            chmod($filePath,0777);
        }
    }

}