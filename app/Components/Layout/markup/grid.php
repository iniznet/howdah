<?php
/**
 * @var Iniznet\Howdah\Support\ClassResolver  $c
 * @var Iniznet\Howdah\Components\Layout\Size $gap
 * @var int                                   $columns
 * @var list<string>                          $children
 */
?>
<div class="<?php echo esc_attr(trim($c('grid').' '.$c('grid--'.$gap->value))); ?>" style="--howdah-grid-columns: <?php echo (int) $columns; ?>;">
<?php foreach ($children as $child) { ?>
	<?php echo $child; ?>
<?php } ?>
</div>
