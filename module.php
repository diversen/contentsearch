<?php

namespace modules\contentSearch;

use diversen\conf;
use diversen\db\admin;
use diversen\db\fulltext;
use diversen\db\q;
use diversen\html;
use diversen\lang;
use diversen\moduleloader;
use diversen\pagination;
use diversen\strings\ext;
use diversen\uri;
use modules\content\article\module as article;
use modules\content\book\module as book;
use modules\content\export\module as export;
use modules\contentsearch\display;

class module {

    public function __construct() {
        moduleloader::setModuleIniSettings('content');
    }

    public function form() {
        $f = new html();
        $f->init(array(), 'submit', true);

        $f->formStart('search', 'get');
        $f->legend(lang::translate('Search'));
        $f->text('search');
        $f->submit('submit', lang::translate('Search'));
        $f->formEnd();
        return $f->getStr();
    }
    
    public function indexAction () {
        
        $per_page = 50;
        $num_rows = q::numRows('content_book')->filter('public =', 1)->fetch();
        
        $p = new pagination($num_rows, $per_page);
        $rows = q::select('content_book')->filter('public =', 1)->order('created', 'DESC')->limit($p->from, $per_page)->fetch();
        $rows = html::specialEncode($rows);
        $this->displayBooksIndex($rows);
        
        echo $p->getPagerHTML();
    }
    
    public function displayBooksSearch ($books) {
        $v = new \modules\contentsearch\views();
        foreach($books as $book) {
            
            $mMod = new \modules\content\menu\module();
            $menu = $mMod->getSystemMenuArray($book['id']);
        
            $type = \diversen\conf::getModuleIni('contentsearch_link');
        
        // Get first article
            $ary = reset($menu);
            
            echo $this->getPandocHeaderLinks($book, $ary);

        }  
    }
    
    public function displayBooksIndex ($books) {
        $v = new \modules\contentsearch\views();
        // $d = new \modules\contentsearch\display();
        $d = new display();
        $e = new export();
        //$chapter = $d->getArticleHtmlLink($row);
        
        // return $this->getLink($chapter, $pub);
        
        foreach($books as $book) {
            
            $mMod = new \modules\content\menu\module();
            $menu = $mMod->getSystemMenuArray($book['id']);
            
            $ary = reset($menu);
            $type = \diversen\conf::getModuleIni('contentsearch_link');
            if (empty($ary)) {
                echo html::getHeadline($book['title']);
                continue;
            }
            if ($type == 'html') {
                $pub = $this->getPandocBookLink($book, $ary);
            } else {
                $d = new display();
                $pub = $d->getBookLink($book);
            }
            
            echo html::getHeadline($pub);
            // echo $e->getExportsHTML($book);
        // Get first article
            //$ary = reset($menu);
            
            //echo $this->getPandocHeaderLink($book, $ary);

        }  
    }
    


    /**
     * Generate search index
     */
    public function genereateIndex() {
        admin::dublicateTable('content_article', 'content_article_search');
        admin::generateIndex('content_article_search', array('title, abstract, article'));
    }

    /**
     * /content/search/index action
     * Display all results from public books
     */
    public function searchAction() {
        echo $this->form();

        if (isset($_GET['search'])) {
            $q_extra = $this->getExtraFromPublic();
            $this->displayResults($q_extra);
        }
    }

    /**
     * User action. Display search by $user_id
     */
    public function userAction() {

        echo $this->form();

        
        if (isset($_GET['search'])) {
            $user_id = \diversen\session::getUserId();
            $q_extra = $this->getExtraFromUserId($user_id);
            // die;
            if (empty($q_extra)) {
                return;
            }

            $this->displayResults($q_extra);
        }
    }

    /**
     * Get extra query part from user_id
     * From user_id public books user_ids are extracted,
     * and a id IN query part created 
     * @param int $user_id
     * @return string $query SQL
     */
    public function getExtraFromUserId($user_id) {

        $b = new book();
        
        // Own books
        $books = $b->getUserBooks($user_id);
        $user_ids = array_column($books, 'id');
        
        // Collab books
        $collab = q::select('contentusers')->filter('user_id =', $user_id)->fetch();

        $collab_ids = array_column($collab, 'book_id');
   
        $ids = array_merge($user_ids, $collab_ids);
        $query = '';

        if (!empty($ids)) {
            $ids_str = implode($ids, ',');
            $query = "AND parent_id IN ($ids_str)";
        }

        return $query;
    }

    public function getExtraFromPublic() {
        $b = new book();
        $books = $b->getAllBooksPublic();
        $ids = array_column($books, 'id');
        $query = '';

        if (!empty($ids)) {
            $ids_str = implode($ids, ',');
            $query = "AND parent_id IN ($ids_str)";
        }

        return $query;
    }

    /**
     * Display search results
     * @param string $extra extra SQL e.g. books needs to be public
     *                      or books needs to belong to a user
     */
    public function displayResults($extra = '') {

        $search = $_GET['search'];
        $match = 'title, abstract, article';
        $table = 'content_article_search';

        $f = new fulltext();

        $num_rows = $f->simpleSearchCount($table, $match, $search, $extra);
        $per_page = 10;

        $p = new pagination($num_rows, $per_page);

        $rows = $f->simpleSearch($table, $match, $search, '*', $extra, $p->from, 10);

        echo "<hr/>";
        echo $num_rows . ' ' . lang::translate('Results found');
        echo "<hr />";

        $this->displaySearchMatches($rows);
        echo $p->getPagerHTML();
    }

    /**
     * Display matches
     * @param array $rows
     */
    public function displaySearchMatches($rows) {

        foreach ($rows as $row) {

            $row = html::specialEncode($row);
            $header = $this->getHeaderSearch($row);
            echo html::getHeadline($header, 'h3');

            $art = new article();
            $article = $art->filterArticle($row);

            $part = ext::substr2_min($article['article'], 600, 3);
            echo $str = strip_tags($part);

            echo "<hr />";
        }
    }

    public function getHeaderSearch($row) {
        $b = new book();
        $a = new article();


        // Get article filtered
        $row = $a->filterArticle($row);

        // Get book filtered
        $book = $b->getBook($row['parent_id']);
        $book = html::specialEncode($book);

        // Get link type, e.g. 'html'
        $type = conf::getModuleIni('contentsearch_link');


        $header = '';
        if ($type == 'html') {
            $header = $this->getPandocHeaderLinks($book, $row);
            return $header;
        }

        
        $header = $this->getInlineSearchHeaderLinks($book, $row);
        return $header;
    }

    /**
     * Get a link to an exported HTML book
     * @param array $book
     * @param array $row
     * @return string $html
     */
    public function getPandocHeaderLinks($book, $row) {
        $e = new export();
        $ary = $e->getExportsAry($book);

        if (isset($ary['html'])) {

            // If HTML export exists 
            $url = $ary['html'] . "#" . $this->getPandocLink($row['title']);
            $chapter = html::createLink($url, $row['title'], array('target' => '_blank'));
            $pub = html::createLink($ary['html'], $book['title'], array('target' => '_blank'));
            $header = $this->getLink($chapter, $pub);
        } else {
            
            // Get inline link
            $header = $this->getInlineSearchHeaderLinks($book, $row);
        }
        return $header;
    }
    
    public function getPandocBookLink($book, $row) {
        $e = new export();
        $ary = $e->getExportsAry($book);
        $d = new display();

        if (isset($ary['html'])) {

            // If HTML export exists 
            // $url = $ary['html'] . "#" . $this->getPandocLink($row['title']);
            // $chapter = html::createLink($url, $row['title']);
            $book_link = html::createLink($ary['html'], $book['title'], array('target' => '_blank'));
            // $header = $this->getLink($chapter, $pub);
        } else {

            // Get inline link
            $book_link = $d->getBookLink($book);
        }
        return $book_link;
    }

    /**
     * Get a normal chapter / book link
     * @param array $book
     * @param array $row
     * @return string $html
     */
    public function getInlineSearchHeaderLinks($book, $row) {
        
        $d = new \modules\contentsearch\display();
        $d = new display();
        $chapter = $d->getArticleHtmlLink($row);
        $pub = $d->getBookLink($book);
        return $this->getLink($chapter, $pub);
    }
    
    /**
     * Get a inline header link
     * @param array $book
     * @param array $row
     * @return string $html
     */
    /*
    public function getInlineHeaderLink($book, $row) {
        $d = new display();
        $chapter = $d->getArticleHtmlLink($row);
        $pub = $d->getBookLink($book);
        return $this->getLink($chapter, $pub);
    }*/
    
    public function viewAction () {
        $d = new display();
        $d->viewAction();
    }
    
    /**
     * @param string $chapter html link
     * @param string $pub html link
     * @return string $header html
     */
    public function getLink ($chapter, $pub) {
        $header = lang::translate("Chapter") . ' ' . $chapter . '. ';
        $header.= lang::translate("Publication") . ' '; 
        $header.= $pub;
        return $header;
    }
    
    // Get:  minder-fra-cuba--foråret-2012
    // Real: minder-fra-cuba---foråret-2012
    /**
     * Fra HTML Output
     * musikbladet-september-2014
     * skriftlig-eksamen-2014---og-især-opgave-3
     * jens-dalsgaard-1939-2014
     * innovation-et-fyord
     * 
     * Musikbladet-–-september-2014
     * Skriftlig-eksamen-2014---og-især-opgave-3
     * 
     * referat-af-bestyrelsesmøde,-mandag-d.-18/3-i-århus.
     * referat-af-bestyrelsesmøde-mandag-d.-183-i-århus.
     * 
     * Generate a pandoc hash link
     * @param type $title
     * @return type
     */
    public function getPandocLink($title) {

        $title = preg_replace('/(\?+)/', '', $title);
        $title = preg_replace('/[–]/', '', $title);
        $title = preg_replace('/[\:,\/!”]/', '', $title);
        $title = preg_replace("/[^[:alnum:][:space:][-]]/", '', $title);

        $title = preg_replace('/\s+/', '-', $title);
        $title = preg_replace('/\s+/', '-', $title);
        $title = str_replace(' ', '-', $title);
        $title = trim($title, '-');
        $title = mb_strtolower($title, 'UTF8');
        return $title;
    }


    /**
     * Search form for all public books
     * List of all public books
     */
    public function publicAction() {

        echo $this->form();

        if (isset($_GET['search'])) {
            $q_extra = $this->getExtraFromPublic();
            $this->displayResults($q_extra);
        }
    }

    /**
     * Get all books a user collaborates on
     * @param int $user_id
     * @return array $rows
     */
    public function getAllCollabBooks($user_id) {
        $rows = q::select('contentusers', 'book_id')->filter('user_id =', $user_id)->fetch();
        return $rows;
    }
}
