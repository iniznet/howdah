<?php
/**
 * @var Iniznet\Howdah\Support\ClassResolver $c
 * @var string                               $content
 */
?>
<div class="<?php echo esc_attr($c('post-body')); ?>">
<?php echo $content; // the_content pipeline's own sanitised bytes — the one sanctioned unescaped output?>
</div>
