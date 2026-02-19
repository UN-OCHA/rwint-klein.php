<?php

/**
 * @file
 * Layout.
 */

?>
<h1><?php echo ucfirst($this->sharedData->get('title')); ?></h1>
<?php $this->yieldView(); ?>
<div>footer</div>
