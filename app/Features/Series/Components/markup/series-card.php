<?php
/**
 * @var Iniznet\Mahout\Ui\ClassResolver           $c
 * @var Iniznet\Howdah\Features\Series\SeriesData $series
 */
?>
<article class="<?php echo esc_attr($c('series-card')); ?>">
	<h2 class="<?php echo esc_attr($c('series-card-title')); ?>">
		<a class="<?php echo esc_attr($c('series-card-link')); ?>" href="<?php echo esc_url($series->permalink); ?>"><?php echo esc_html($series->title); ?></a>
	</h2>
	<?php if (null !== $series->tagline) { ?>
	<p class="<?php echo esc_attr($c('series-card-tagline')); ?>"><?php echo esc_html($series->tagline); ?></p>
	<?php } ?>
</article>
