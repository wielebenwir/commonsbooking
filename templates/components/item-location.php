<div class="cb-box">
    <div style="float:left; margin-right: 15px;">
        <?php echo get_the_post_thumbnail( $templateData['location']->ID, 'thumbnail' ); ?>
    </div>
    <div>
        <h3>
            <a href="<?php echo get_permalink( $templateData['location']->ID); ?>"><?php echo $templateData['location']->post_title; ?></a>
        </h3>
    </div>
</div>
<div class="cb-box">
    <?php
    include __DIR__ . '/calendar-index.php';
    ?>
</div>
