<?php
/**
 * Plugin Name: Área Familias - Colegio Casa de la Virgen
 * Description: Área de Familias del Colegio Casa de la Virgen: acceso solo con la cuenta del colegio (@casadelavirgen.es), páginas de inicio y de login, y espacios de aula de Google Chat leídos del Área de Profesores.
 * Version:     1.5.0
 * Author:      Colegio Casa de la Virgen
 * Text Domain: area-familias
 * Requires PHP: 7.4
 */

if (!defined('ABSPATH')) {
    exit;
}

define('AREA_FAMILIAS_VERSION', '1.5.0');
define('AREA_FAMILIAS_DIR', plugin_dir_path(__FILE__));
define('AREA_FAMILIAS_URL', plugin_dir_url(__FILE__));

require_once AREA_FAMILIAS_DIR . 'includes/class-af-ajustes.php';
require_once AREA_FAMILIAS_DIR . 'includes/class-af-acceso.php';
require_once AREA_FAMILIAS_DIR . 'includes/class-af-login.php';
require_once AREA_FAMILIAS_DIR . 'includes/class-af-espacios.php';
require_once AREA_FAMILIAS_DIR . 'includes/class-af-paginas.php';
require_once AREA_FAMILIAS_DIR . 'includes/class-af-panel.php';
require_once AREA_FAMILIAS_DIR . 'includes/class-af-reservas.php';

AF_Ajustes::get_instance();
AF_Acceso::get_instance();
AF_Login::get_instance();
AF_Espacios::get_instance();
AF_Paginas::get_instance();
AF_Panel::get_instance();
AF_Reservas::get_instance();

// Admin styles (only on the plugin screens).
add_action('admin_enqueue_scripts', function ($hook) {
    if (strpos($hook, 'area-familias') !== false) {
        wp_enqueue_style('area-familias-admin', AREA_FAMILIAS_URL . 'assets/css/admin.css', array(), AREA_FAMILIAS_VERSION);
    }
});
