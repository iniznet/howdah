<?php
/**
 * @var Iniznet\Howdah\Support\ClassResolver $c
 * @var string                               $reference
 */
?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo('charset'); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<?php wp_head(); ?>
</head>
<body <?php body_class($c('error')); ?>>
<section class="<?php echo esc_attr($c('error-state')); ?>">
	<h1 class="<?php echo esc_attr($c('error-heading')); ?>"><?php echo esc_html__('Something went wrong.', 'howdah'); ?></h1>
	<p class="<?php echo esc_attr($c('error-reference')); ?>"><?php echo esc_html(sprintf(esc_html__('The support reference for this failure is %s.', 'howdah'), $reference)); ?></p>
</section>
<?php wp_footer(); ?>
</body>
</html>
