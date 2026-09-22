<?php
/**
 * @var Iniznet\Howdah\Support\ClassResolver           $c
 * @var list<Iniznet\Howdah\Components\Navigation\Tab> $tabs
 */
?>
<div class="<?php echo esc_attr($c('tabs')); ?>" role="tablist">
<?php foreach ($tabs as $tab) { ?>
	<button class="<?php echo esc_attr($c('tab')); ?>" type="button" role="tab" id="<?php echo esc_attr($tab->id); ?>-tab" aria-controls="<?php echo esc_attr($tab->id); ?>" aria-selected="<?php echo $tab->selected ? 'true' : 'false'; ?>"<?php echo $tab->selected ? '' : ' tabindex="-1"'; ?>><?php echo esc_html($tab->label); ?></button>
<?php } ?>
</div>
