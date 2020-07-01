<div class="cb-box">
    <div class="cb-cols col-30-70">
        <div>
            <?php echo get_the_post_thumbnail( $templateData['item']->ID, 'thumbnail' ); ?>
        </div>
        <div>
        <h3>
            <a href="<?php echo get_permalink( $templateData['item']->ID); ?>"><?php echo $templateData['item']->post_title; ?></a></h3>
        </div>
    </div>

<?php
    include __DIR__ . '/calendar-index.php';
?>
</div>
