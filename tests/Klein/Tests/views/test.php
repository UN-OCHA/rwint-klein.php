<?php

/**
 * @file
 * Test template.
 */
?>
My name is <?php echo ucwords($this->sharedData->get('name')); ?>.
<?php echo strtoupper($this->sharedData->get('verb')); ?>!
