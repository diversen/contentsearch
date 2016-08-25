<?php

namespace modules\contentsearch;

use diversen\conf;
use diversen\html;
use diversen\lang;

use diversen\session;
use diversen\strings;
use diversen\time;
use diversen\user;

use modules\content\export\module as exportModule;

/**
 * view file for content/article
 *
 * @package content
 */
class views extends \modules\contentsearch\display {


    /**
     * Get user profile
     * @param int $user_id
     * @created string £created timestamp
     */
    public static function user($user_id, $created) {

        $date = time::getDateString($created);
        return user::getProfile($user_id, $date);
    }

    /**
     * Get a book menu from an article id 
     * @param int $article_id
     * @return string $html
     */
    public function getArticleMenu ($book_id) {

        
    }
    
    public function menuBegin () { ?>
<div class="uk-sticky-placeholder">
    <div class="uk-panel uk-panel-box uk-overflow-container" data-uk-sticky="{top:25, boundary: true, boundary:false, media: 768}">
        <ul class="uk-nav uk-nav-side"><?php
    }
    
    public function menuEnd() { ?>
         </ul>
    </div>
</div><?php
    }
    
    public function getHtmlMenu($ary){
        
        $str = '';
        foreach ($ary as $key => $val){
            $link = $this->getArticleHtmlLink($val);
            $str.= "<li>$link";
            // $str.= $this->getSortableLI($val, $options);
            if (!empty($val['sub'])){
                $str.='<ul class="uk-nav-sub">';
                $str.=$this->getHtmlMenu($val['sub']);
                $str.= '</ul>';
            } 
            
            $str.= '</li>';
        }

        
        return $str;
    }
    
    public function getSortableLI($val, $options) {
        print_r($val);
        
        return $str;
    }
    
    /**
     * display header of chapter
     * @param array $row article
     */
    public function header($book, $row = array()) {
        if (empty($book['abstract'])) {
            $book['abstract'] = lang::translate('No abstract');
        }
        ?>
<div class="uk-grid" data-uk-grid-margin="">
    <div class="uk-width-1-1 uk-row-first">
        <h1 id="begin-pub" class="uk-heading-large"><?=$book['title']?></h1>
        <p class="uk-text-large"><?=$book['abstract']?></p>
    </div>
</div>
        
    <?php }


    /**
     * generate header section of article
     * @param string $toggle_link
     * @param array $book
     * @return string $str html
     */
    public static function headerSection($book, $article = array()) {

        $title = $book['title'];
        
        $str = '';
        $str.= html::getHeadline($title);
        
        $str.= self::user($book['user_id'], $book['created']);
        $str.= self::getHeaderAbstract($book);
        $str.= '<hr />';
        $str.= self::headerExports($book);
        $str.= '<hr />';
        
        if (!empty($article)) {
            $str.= self::getArticleMenu($article['id']);
        }
        
        
        return $str;
    }

    /**
     * Get book abstract
     * @param array $book book
     * @return string $str html
     */
    public static function getHeaderAbstract($book) {
        $str = '';

        if (!empty($book['abstract'])) {
            $str.= strings::substr2($book['abstract'], 255) . "<br />";
        } else {
            $str.= "" . lang::translate('No abstract yet') . "<br />";
        }
        
        return '<p class="uk-article-lead">' . $str . '</p>';
        // return html::getHeadline($str, 'h5');
    }

    /**
     * Get exports for display in header
     * @param array $book book
     * @return string $str html
     */
    public static function headerExports($book) {
        $str = '';
        $str.= '<div class = "content_export">';

        // Allow to download
        $x = new exportModule();
        $user_id = session::getUserId();
        if ($user_id == $book['user_id']) {
            $allow_download = 1;
        }

        if ($book['download_allow'] == 1 OR isset($allow_download)) {
            $export_html = $x->getExportsHTML($book);
        } else {
            $export_html = lang::translate('Only HTML format is available');
        }

        $str.= $export_html;
        $str.= "</div>";
        return $str;
    }



    /**
     * View complete article
     * @param array $row article row
     */
    public function view($book, $row) {
        
        //include conf::pathModules() . "/contentsearch/html/test.html";
        //return;
        $this->header($book, $row);
        
        ?>
<div class="uk-grid" data-uk-grid-margin="">
    <div class="uk-width-medium-1-4"><?php
        
    // $this->menu();
    // include conf::pathModules() . "/contentsearch/html/menu.php";
    $this->menuBegin();
            $mMod = new \modules\content\menu\module();
        $ary = $mMod->getSystemMenuArray($book['id']);
        
// print_r($ary); die;
        echo $this->getHtmlMenu($ary);
    $this->menuEnd();
    ?>
    </div>
    <?php


        
        ?>
        <div class="uk-width-medium-3-4 uk-row-first">
        <?=$this->article($row)?>
    </div>

</div><?php
        
    }

    /**
     * View article
     * @param array $article article row
     */
    public function article($article) {
        
        $title = $article['title'];
        
        echo '<h2 id="begin-article">' . $title . "</h2>";
        echo $article['article'];
        
    }

}
