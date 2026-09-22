<?php
/**
 * @var Iniznet\Howdah\Support\ClassResolver              $c
 * @var Iniznet\Howdah\Components\Typography\HeadingLevel $level
 * @var string                                            $text
 */
?>
<<?php echo esc_html($level->tag()); ?> class="<?php echo esc_attr(trim($c('heading').' '.$c('heading--'.$level->value))); ?>"><?php echo esc_html($text); ?></<?php echo esc_html($level->tag()); ?>>
