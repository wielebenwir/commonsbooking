<div>
    <h3>
        <a href="<?php echo get_permalink( $templateData['item']->ID); ?>"><?php echo $templateData['item']->post_title; ?></a></h3>
        <?php echo get_the_post_thumbnail( $templateData['item']->ID, 'thumbnail' ); ?>
    <br>
</div>

<?php
    include __DIR__ . '/calendar-index.php';
?>
