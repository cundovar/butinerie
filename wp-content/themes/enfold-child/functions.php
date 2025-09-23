<?php
/**
 * Enfold Child Theme Functions
 *
 * @package WordPress
 * @subpackage Enfold_Child
 */

// Sécurité : empêcher l'accès direct
if (!defined('ABSPATH')) {
    exit;
}

/*
* Add your own functions here. You can also copy some of the theme functions into this file.
* Wordpress will use those functions instead of the original functions then.
*/

/**
 * Enregistrer et charger les styles du thème enfant
 */
function enfold_child_enqueue_styles() {
    // Charger le style du thème parent
    wp_enqueue_style('enfold-parent-style', get_template_directory_uri() . '/style.css');

    // Charger le style du thème enfant
    wp_enqueue_style('enfold-child-style',
        get_stylesheet_directory_uri() . '/style.css',
        array('enfold-parent-style'), // Dépendance au style parent
        wp_get_theme()->get('Version') // Version pour le cache
    );
}
add_action('wp_enqueue_scripts', 'enfold_child_enqueue_styles');

/* Autoriser les fichiers SVG */
function wpc_mime_types($mimes) {
	$mimes['svg'] = 'image/svg+xml';
	return $mimes;
}
add_filter('upload_mimes', 'wpc_mime_types');

// Inclure le patch temporaire pour les erreurs
require_once get_stylesheet_directory() . '/patch-errors.php';

// Désactiver spécifiquement les erreurs de wp_targeted_link_rel du thème Enfold
add_action('init', function() {
    // Supprimer le filtre problématique du thème Enfold
    if (function_exists('handler_wp_targeted_link_rel')) {
        remove_filter('wp_targeted_link_rel', 'handler_wp_targeted_link_rel', 10);
    }
}, 1);

// Gestionnaire d'erreurs personnalisé pour masquer les deprecated notices
function custom_error_handler($errno, $errstr, $errfile, $errline) {
    // Masquer uniquement les erreurs deprecated et notices
    if ($errno === E_DEPRECATED || $errno === E_USER_DEPRECATED ||
        $errno === E_NOTICE || $errno === E_USER_NOTICE) {
        return true;
    }
    return false;
}
set_error_handler('custom_error_handler', E_DEPRECATED | E_USER_DEPRECATED | E_NOTICE | E_USER_NOTICE);


