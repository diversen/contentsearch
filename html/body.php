<div class="uk-grid" data-uk-grid-margin="">
    <div class="uk-width-1-1 uk-row-first">
        <h1 class="uk-heading-large"><?= $vars['title'] ?></h1>
        <p class="uk-text-large"><?= $vars['abstract'] ?></p>
    </div>
</div>

<div class="uk-grid" data-uk-grid-margin="">
    <div class="uk-width-medium-1-4">

        <div class="uk-sticky-placeholder">
            <div class="uk-panel uk-panel-box uk-overflow-container" data-uk-sticky="{top:35, boundary: true, boundary:false, media: 768}">
                <ul class="uk-nav uk-nav-side">
                    <!-- data-uk-scrollspy-nav="{closest:'li', smoothscroll:true}" -->

                    <li class="uk-parent">
                        <a href="#templatesettings">Parent</a>
                        <ul class="uk-nav-sub">
                            <li><a href="#">Sub 1</a></li>
                            <li><a href="#">Sub 2</a></li>
                        </ul>
                    </li>
                    <li class="uk-nav-header">For Beginners</li>
                    <li><a href="#getstarted">Get Started</a></li>
                    <li class=""><a href="#templatesettings">Template Settings</a></li>
                    <li class=""><a href="#customizer">Customizer</a></li>
                    <li class="uk-nav-header">For Developers</li>
                    <li class=""><a href="#customization">Customizaton</a></li>
                    <li class=""><a href="#troubleshooting">Her er så en mget lang titel Troubleshooting</a></li>


                    <li class="uk-nav-divider"></li>
                    <li class=""><a href="#faq"><i class="uk-icon-info-circle uk-margin-small-right"></i>FAQ</a></li>
                </ul>
            </div></div>

    </div>

    <div class="uk-width-medium-3-4 uk-row-first">
        <?= $vars['content'] ?>
    </div>

</div>