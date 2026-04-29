<?php
/**
 * Legacy full-document opening (minimal canvas). Used when relayforge_use_theme_shell is false.
 *
 * @package RelayForgeWordPress
 */

if (! defined('ABSPATH')) {
    exit;
}

RelayForge_Theme_Compat::isolate_head_callbacks();

?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo('charset'); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1">
<?php wp_head(); ?>
</head>
<body <?php body_class('rfp-canvas-page rfp-page'); ?>>
<?php wp_body_open(); ?>
