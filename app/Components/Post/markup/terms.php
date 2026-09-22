<?php
/**
 * @var Iniznet\Howdah\Support\ClassResolver           $c
 * @var list<Iniznet\Howdah\Features\Content\PostTerm> $terms
 * @var string                                         $label
 */
?>
<?php if ([] !== $terms) { ?>
<ul class="<?php echo esc_attr($c('post-terms')); ?>">
	<li class="<?php echo esc_attr($c('post-terms-label')); ?>"><?php echo esc_html($label); ?></li>
<?php foreach ($terms as $term) { ?>
	<li class="<?php echo esc_attr($c('post-term')); ?>"><a href="<?php echo esc_url($term->link); ?>"><?php echo esc_html($term->name); ?></a></li>
<?php } ?>
</ul>
<?php } ?>
