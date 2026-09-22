<?php
/**
 * @var Iniznet\Howdah\Support\ClassResolver   $c
 * @var Iniznet\Howdah\Components\Layout\Width $width
 * @var list<string>                           $children
 */
?>
<div class="<?php echo esc_attr(trim($c('container').' '.$c('container--'.$width->value))); ?>">
<?php foreach ($children as $child) { ?>
	<?php echo $child; ?>
<?php } ?>
</div>
