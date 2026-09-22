<?php
/**
 * @var Iniznet\Howdah\Support\ClassResolver     $c
 * @var Iniznet\Howdah\Features\Content\PostData $post
 */
?>
<article class="<?php echo esc_attr($c('post-card')); ?>">
	<h2 class="<?php echo esc_attr($c('post-card-title')); ?>">
		<a href="<?php echo esc_url($post->permalink); ?>"><?php echo esc_html($post->title); ?></a>
	</h2>
	<time class="<?php echo esc_attr($c('post-card-date')); ?>" datetime="<?php echo esc_attr($post->publishedAt->format('c')); ?>"><?php echo esc_html($post->dateDisplay); ?></time>
<?php if ('' !== $post->excerpt) { ?>
	<p class="<?php echo esc_attr($c('post-card-excerpt')); ?>"><?php echo esc_html($post->excerpt); ?></p>
<?php } ?>
</article>
