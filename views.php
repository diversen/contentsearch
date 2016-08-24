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
    
    public function getHtmlMenu($ary, $start = null, $options = array()){
        static $str = '';

        if ($start) {
            $str.= "<ol class=\"sortable\">\n";
        } else {
            $str.= "<ol>\n";
        }

        foreach ($ary as $val){
            
            $str.="<li id=\"list_$val[id]\">";
            $str.='<div class="sortable-li">';
            $str.= $this->getSortableLI($val, $options);
            $str.= "</div>";
            if (!empty($val['sub'])){
                $this->getHtmlMenu($val['sub'], false, $options);
            } 
        }

        if (!empty($ary)) {
            $str.= "</li></ol>\n";
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
    public function header($book, $row) {
        if (empty($book['abstract'])) {
            $book['abstract'] = lang::translate('No abstract');
        }
        ?>
<div class="uk-grid" data-uk-grid-margin="">
    <div class="uk-width-1-1 uk-row-first">
        <h1 class="uk-heading-large"><?=$book['title']?></h1>
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
    include conf::pathModules() . "/contentsearch/html/menu.php";
    
    ?>
    </div>
    <?php

        $mMod = new \modules\content\menu\module();
        $ary = $mMod->getSystemMenuArray($book['id']);

        $this->getHtmlMenu($ary, $start)
        
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
        
        echo '<h2 id="chapter-begin">' . $title . "</h2>";
        echo $article['article'];
        
    }

}
