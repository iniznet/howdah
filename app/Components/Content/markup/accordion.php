<?php
/**
 * @var Iniznet\Howdah\Support\ClassResolver                     $c
 * @var list<Iniznet\Howdah\Components\Content\AccordionSection> $sections
 */
?>
<div class="<?php echo esc_attr($c('accordion')); ?>">
<?php foreach ($sections as $section) { ?>
	<details class="<?php echo esc_attr($c('accordion-item')); ?>"<?php echo $section->open ? ' open' : ''; ?>>
		<summary class="<?php echo esc_attr($c('accordion-summary')); ?>"><?php echo esc_html($section->summary); ?></summary>
		<div class="<?php echo esc_attr($c('accordion-body')); ?>"><?php echo esc_html($section->body); ?></div>
	</details>
<?php } ?>
</div>
