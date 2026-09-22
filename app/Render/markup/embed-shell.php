<?php
/**
 * @var Iniznet\Howdah\Support\ClassResolver $c
 * @var Iniznet\Howdah\Render\Component      $content
 */
?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo('charset'); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<?php wp_head(); ?>
</head>
<body <?php body_class($c('embed')); ?>>
<?php echo $content->render(); // the content component's own escaped bytes?>
<?php wp_footer(); ?>
</body>
</html>
