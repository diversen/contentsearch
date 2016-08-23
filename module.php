<?php

namespace modules\contentSearch;

use diversen\db\fulltext;
use diversen\html;
use diversen\lang;
use diversen\conf;
use diversen\pagination;
use diversen\strings\ext;
use diversen\uri;
use modules\content\article\module as article;
use modules\content\book\module as book;
use modules\content\export\module as export;
use diversen\db\admin;

class module {

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
    
    /**
     * Generate search index
     */
    public function genereateIndex () {
        admin::dublicateTable('content_article', 'content_article_search');
        admin::generateIndex('content_article_search', array ('title, abstract, article'));
    }

    /**
     * /content/search/index action
     * Display all results from public books
     */
    public function indexAction() {
        echo $this->form();

        if (isset($_GET['search'])) {
            echo $q_extra = $this->getExtraFromPublic();
            $this->displayResults($q_extra);
        }
    }

    /**
     * User action. Display search by $user_id
     */
    public function userAction() {
  
        echo $this->form();

        if (isset($_GET['search'])) {
            $user_id = uri::fragment(3);
            $q_extra = $this->getExtraFromUserId($user_id);
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
        $books = $b->getUserBooksPublic($user_id, 1);
        $ids = array_column($books, 'id');
        $query = '';

        if (!empty($ids)) {
            $ids_str = implode($ids, ',');
            $query = "AND parent_id IN ($ids_str)";
        }

        return $query;
    }
    
    public function getExtraFromPublic () {
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

        $this->displayMatches($rows);
        echo $p->getPagerHTML();
    }

    /**
     * Display matches
     * @param array $rows
     */
    public function displayMatches($rows) {

        foreach ($rows as $row) {
            
            $row = html::specialEncode($row);
            $header = $this->getHeaderLink($row);
            echo html::getHeadline($header, 'h4');

            $art = new article();
            $article = $art->filterArticle($row);

            $part = ext::substr2_min($article['article'], 600, 3);
            echo $str = strip_tags($part);

            echo "<hr />";
        }
    }
    
    public function getHeaderLink ($row) {
        $b = new book();
        $a = new article();
        $e = new export();
        
        // Get article filtered
        $article = $a->filterArticle($row);
        
        // Get book filtered
        $book = $b->getBook($row['parent_id']);
        $book = html::specialEncode($book);
        
        // Get link type, e.g. 'html'
        $type = conf::getModuleIni('content_search_link');
        
        $header ='';
        if ($type == 'html') {
            $ary = $e->getExportsAry($book);
            
            if (isset($ary['html'])) {
                
                // If HTML export exists 
                $url = $ary['html'] . "#" . $this->generatePandocLink($row['title']);
                $header = html::createLink($url, $article['title']);
                $header.= MENU_SUB_SEPARATOR_SEC;
                $header.= html::createLink($ary['html'],$book['title'] );
            } else {
                
                // If html export does not exist
                $header = $a->getArticleHtmlLink($row);
                $header.= MENU_SUB_SEPARATOR_SEC;
                $header.= \modules\content\book\views::getBookLink($book);
            }
            
            return $header;
            
        }
        
        $header.= $a->getArticleHtmlLink($row);
        $header.= MENU_SUB_SEPARATOR_SEC;
        $header.= \modules\content\book\views::getBookLink($book);
        return $header;
    }
    
    /**
     * Search form for all public books
     * List of all public books
     */
    public function publicAction () {

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
    public function getAllCollabBooks ($user_id) {
        $rows = q::select('contentusers', 'book_id')->filter('user_id =', $user_id)->fetch();
        return $rows;
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
    public function generatePandocLink($title) {

        $title = preg_replace('/(\?+)/', '', $title);
        $title = preg_replace('/[–]/', '', $title);
        $title = preg_replace('/[\:,\/!”]/', '', $title);
        $title = preg_replace("/[^[:alnum:][:space:][-]]/", '', $title);

        $title = preg_replace('/\s+/','-', $title);
        $title = preg_replace('/\s+/','-', $title);
        $title = str_replace(' ', '-', $title);
        $title = trim($title, '-');
        $title = mb_strtolower($title, 'UTF8');
        return $title;
    }
}
