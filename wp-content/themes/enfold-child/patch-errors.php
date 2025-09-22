<?php
// Patch temporaire pour masquer les erreurs WordPress 6.7+
// À supprimer après mise à jour du thème Enfold

// Supprimer les hooks problématiques du thème parent
add_action('after_setup_theme', function() {
    // Supprimer le filtre wp_targeted_link_rel qui cause les erreurs deprecated
    remove_filter('wp_targeted_link_rel', 'handler_wp_targeted_link_rel', 10);

    // Empêcher les erreurs de chargement de traduction trop précoce
    remove_action('plugins_loaded', 'avia_lang_setup', 1);
}, 1);

// Masquer toutes les erreurs deprecated et notices
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    if (in_array($errno, [E_DEPRECATED, E_USER_DEPRECATED, E_NOTICE, E_USER_NOTICE])) {
        return true; // Masquer l'erreur
    }
    return false; // Laisser passer les autres erreurs
}, E_ALL);