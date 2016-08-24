<?php

namespace modules\contentsearch;

use diversen\html;
use diversen\strings;

class display {

    public $basePath = '/contentsearch/view';
    
    /**
     * Get a link to the book, which means the first chapter
     * @param array $book
     * @return string $html link
     */
    public function getBookLink ($book) {
        
        // get menu from book id
        $mMod = new \modules\content\menu\module();
        $menu = $mMod->getSystemMenuArray($book['id']);
        
        
        $ary = reset($menu);
        $url = $this->getArticleUrl($ary['id'], $ary['title']);
        return $title = html::createLink($url, $book['title']);
    }
    
    
    private function getArticleUrl($id, $title) {
        return strings::utf8Slug("/contentsearch/view/$id", $title);
        
    }
    
    public function getArticleHtmlLink($row) {
        $url = $this->getArticleUrl($row['id'], $row['title']);
        return html::createLink($url, html::specialEncode($row['title']));
    }
    
        /**
     * /content/article/view action
     */
    public function viewAction() {


        $a = new \modules\content\article\module();
        
        $a->loadAssets();
        
        // Get article
        $id = \diversen\uri::fragment(2);
        $row = $a->get($id);
        
        // 404 if the article is not found
        if (empty($row)) {
            \diversen\moduleloader::setStatus(404);
            return;
        }

        // Check for perm moved
        $this->checkPermMoved($row);

        // Get article's book 
        $b = new \modules\content\book\module();
        $book = $b->getBookFromArticleId($row['id']);
        $book = html::specialEncode($book);

        // Check access to book
        $c = new \modules\content\acl();
        if (!$c->checkAccessBook($book['id'], 'view')) {
            return false;
        }
        
        // Set title and meta description
        $a->setMeta($row['id']);
        if (empty($row['abstract'])) {
            $row['abstract'] = $book['abstract'];
        }

        // Display article
        $row = $a->filterArticle($row);
        $v = new \modules\contentsearch\views();
        $v->view($book, $row);  
        
    }
    
    
    /**
     * Check if we have a correct url. Else we move to the correct url
     * @param array $row article row
     */
    public function checkPermMoved(&$row) {
        $url = $this->getArticleUrl($row['id'], $row['title']);
        \diversen\http::permMovedHeader($url);

        $canon = \diversen\conf::getSchemeWithServerName() . $url;

        \diversen\template\assets::setMetaAsStr('<link rel="canonical" href="' . $canon . '" />' . "\n");
        \diversen\template\assets::setMetaAsStr('<meta property="og:url" content="' . $canon . '" />' . "\n");
    }
}
