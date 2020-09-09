<?php
    global $templateData;
    $location = $templateData['location'];
    echo $location->thumbnail(); // div.thumbnail is printed by function
?>
<div class="cb-list-info">
  <h4 class="cb-title cb-location-title"><?php echo $location->post_title; ?></h4>
</div>
