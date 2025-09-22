<?php
/**
 * Plugin Name: Hide All Errors
 * Description: Cache toutes les erreurs PHP et WordPress
 * Must Use: true
 */

// Désactiver complètement l'affichage des erreurs
error_reporting(0);
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
ini_set('log_errors', 0);

// Hook très précoce pour intercepter les erreurs
add_action('muplugins_loaded', function() {
    error_reporting(0);
    ini_set('display_errors', 0);
}, 1);

// Intercepter les sorties d'erreurs
ob_start();

// Fonction de nettoyage de sortie
function clean_error_output($buffer) {
    // Supprimer toutes les erreurs Notice, Warning, Deprecated
    $buffer = preg_replace('/<br\s*\/?>\s*<b>(Notice|Warning|Deprecated|Fatal error|Parse error)[^<]*<\/b>[^<]*<br\s*\/?>/i', '', $buffer);
    return $buffer;
}

// Appliquer le nettoyage à la sortie
add_action('shutdown', function() {
    $output = ob_get_clean();
    echo clean_error_output($output);
});