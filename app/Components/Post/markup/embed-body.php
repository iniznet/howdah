<?php
/**
 * @var Iniznet\Howdah\Support\ClassResolver     $c
 * @var Iniznet\Howdah\Features\Content\PostData $post
 */
?>
<article class="<?php echo esc_attr($c('embed-body')); ?>">
<?php if ('' !== $post->thumbnail) { ?>
	<?php echo $post->thumbnail; // core's own sized image markup — the one sanctioned unescaped output?>
<?php } ?>
	<h1 class="<?php echo esc_attr($c('embed-title')); ?>">
		<a href="<?php echo esc_url($post->permalink); ?>"><?php echo esc_html($post->title); ?></a>
	</h1>
<?php if ('' !== $post->excerpt) { ?>
	<p class="<?php echo esc_attr($c('embed-excerpt')); ?>"><?php echo esc_html($post->excerpt); ?></p>
<?php } ?>
	<p class="<?php echo esc_attr($c('embed-link')); ?>"><a href="<?php echo esc_url($post->permalink); ?>"><?php esc_html_e('Read the full post', 'howdah'); ?></a></p>
</article>
