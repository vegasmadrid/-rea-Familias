<?php
/**
 * Panel de la familia (página de inicio del Área Familias).
 *
 * Shown on the "Página de inicio" chosen in Área Familias › Ajustes: a greeting
 * and the options of the family:
 * - Mis hijos: course, tutor/a and class space of each child.
 * - Mis datos: emergency phone and address of each parent (editable; saved right
 *   away in the Área de Profesores, which notifies the tutor/a and Secretaría).
 * - Espacios de aula: Google Chat spaces of every class.
 * - Reservar un servicio (coming soon) and Enlaces de interés.
 *
 * The data lives in the Área de Profesores and is read through its API with the
 * e-mail of the logged-in family.
 */

if (!defined('ABSPATH')) {
    exit;
}

class AF_Panel {

    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('admin_menu', array($this, 'register_menu'), 15);
        add_action('admin_post_af_save_panel', array($this, 'handle_save_panel'));
        add_action('admin_post_af_guardar_datos', array($this, 'handle_guardar_datos'));
    }

    public static function servicios_por_defecto() {
        return "Ampliación con Desayuno\nAmpliación sin Desayuno\nComedor\nLudoteca\nTiene dieta\nSalida Anticipada\nRetraso Justificado";
    }

    /* ---------------------------------------------------------------------
     * Datos de la familia (Área de Profesores)
     * ------------------------------------------------------------------- */

    private static function api($method, $path, $args = array()) {
        $key = (string) AF_Ajustes::get('api_key');
        $base = trailingslashit((string) AF_Ajustes::get('profesores_url'));
        if ($key === '' || $base === '/') {
            return new WP_Error('af_config', __('El Área Familias todavía no está conectada con el Área de Profesores.', 'area-familias'));
        }
        $url = add_query_arg('rest_route', '/area-profesores/v1' . $path, $base);
        $req = array('timeout' => 20, 'headers' => array('X-Area-Key' => $key, 'Accept' => 'application/json'));
        if ($method === 'GET') {
            $response = wp_remote_get(add_query_arg(array_map('rawurlencode', $args), $url), $req);
        } else {
            $req['headers']['Content-Type'] = 'application/json';
            $req['body'] = wp_json_encode($args);
            $response = wp_remote_post($url, $req);
        }
        if (is_wp_error($response)) {
            // Technical detail for the administrator (PHP error log).
            error_log('[Área Familias] Error al conectar con el Área de Profesores (' . $path . '): ' . $response->get_error_message());
            return new WP_Error('af_red', __('No se ha podido conectar con el colegio. Inténtalo de nuevo en unos minutos.', 'area-familias'));
        }
        $data = json_decode(wp_remote_retrieve_body($response), true);
        $code = (int) wp_remote_retrieve_response_code($response);
        if ($code < 200 || $code >= 300 || !is_array($data)) {
            $msg = is_array($data) && !empty($data['message']) ? $data['message'] : sprintf(__('Error del Área de Profesores (HTTP %d).', 'area-familias'), $code);
            return new WP_Error('af_api', $msg);
        }
        return $data;
    }

    private static function cache_key($email) {
        return 'af_fam_' . md5(strtolower($email));
    }

    /**
     * Children and details of the logged-in family (cached for 5 minutes).
     */
    public static function get_familia($email, $force = false) {
        $ck = self::cache_key($email);
        if (!$force) {
            $c = get_transient($ck);
            if (is_array($c)) {
                return $c;
            }
        }
        $data = self::api('GET', '/familia', array('email' => $email));
        if (!is_wp_error($data)) {
            set_transient($ck, $data, 5 * MINUTE_IN_SECONDS);
        }
        return $data;
    }

    /* ---------------------------------------------------------------------
     * Mis datos: guardar
     * ------------------------------------------------------------------- */

    public function handle_guardar_datos() {
        if (!is_user_logged_in()) {
            wp_safe_redirect(AF_Acceso::get_login_url());
            exit;
        }
        check_admin_referer('af_guardar_datos');

        $user = wp_get_current_user();
        $back = wp_get_referer() ? remove_query_arg('af_ok', wp_get_referer()) : AF_Acceso::get_start_url();
        $dirs = array();
        foreach ((array) (isset($_POST['direccion']) ? wp_unslash($_POST['direccion']) : array()) as $cid => $dir) {
            $dirs[(string) absint($cid)] = sanitize_text_field($dir);
        }
        $tels = array();
        foreach ((array) (isset($_POST['telefono']) ? wp_unslash($_POST['telefono']) : array()) as $cid => $tel) {
            $tels[(string) absint($cid)] = sanitize_text_field($tel);
        }

        $r = self::api('POST', '/familia/datos', array(
            'email'       => $user->user_email,
            'familia_id'  => isset($_POST['familia_id']) ? absint($_POST['familia_id']) : 0,
            'telefonos'   => $tels,
            'direcciones' => $dirs,
        ));

        if (is_wp_error($r)) {
            set_transient('af_panel_msg_' . $user->ID, array('type' => 'error', 'text' => $r->get_error_message()), 120);
        } else {
            delete_transient(self::cache_key($user->user_email));
            set_transient('af_panel_msg_' . $user->ID, array(
                'type' => 'ok',
                'text' => !empty($r['cambios'])
                    ? __('Datos guardados. Hemos avisado a la tutora o el tutor y a secretaría.', 'area-familias')
                    : __('No había ningún cambio que guardar.', 'area-familias'),
            ), 120);
        }
        wp_safe_redirect($back . '#datos');
        exit;
    }

    /* ---------------------------------------------------------------------
     * Página
     * ------------------------------------------------------------------- */

    public static function render() {
        if (!is_user_logged_in()) {
            return '';
        }
        $user = wp_get_current_user();
        $s = AF_Ajustes::all();
        $familia = self::get_familia($user->user_email, isset($_GET['actualizar']));
        $msg = get_transient('af_panel_msg_' . $user->ID);
        if ($msg) {
            delete_transient('af_panel_msg_' . $user->ID);
        }

        $nombre = trim((string) $user->first_name) !== '' ? $user->first_name : $user->display_name;
        $servicios = array_values(array_filter(array_map('trim', explode("\n", (string) $s['panel_servicios']))));
        $enlaces = array();
        foreach (explode("\n", (string) $s['panel_enlaces']) as $line) {
            $p = array_map('trim', explode('|', $line));
            if (count($p) >= 2 && $p[0] !== '' && $p[1] !== '') {
                $enlaces[] = array('titulo' => $p[0], 'url' => $p[1], 'desc' => isset($p[2]) ? $p[2] : '');
            }
        }
        // Espacios de aula, as one more option of the panel.
        $espacios_html = AF_Espacios::render_pagina();
        $logout_url = AF_Acceso::get_logout_url();

        ob_start();
        include AREA_FAMILIAS_DIR . 'templates/panel.php';
        return ob_get_clean();
    }

    /* ---------------------------------------------------------------------
     * Backend: Área Familias › Panel de familias
     * ------------------------------------------------------------------- */

    public function register_menu() {
        add_submenu_page('area-familias', __('Panel de familias', 'area-familias'), __('Panel de familias', 'area-familias'), 'manage_options', 'area-familias-panel', array($this, 'render_admin'));
    }

    public function render_admin() {
        if (!current_user_can('manage_options')) {
            return;
        }
        $s = AF_Ajustes::all();
        $flash = AF_Ajustes::get_flash();
        $start_id = absint($s['start_page_id']);
        include AREA_FAMILIAS_DIR . 'templates/admin-panel.php';
    }

    public function handle_save_panel() {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('No tienes permisos para realizar esta acción.', 'area-familias'));
        }
        check_admin_referer('af_save_panel');
        AF_Ajustes::update(array(
            'panel_saludo'    => isset($_POST['panel_saludo']) ? sanitize_textarea_field(wp_unslash($_POST['panel_saludo'])) : '',
            'panel_enlaces'   => isset($_POST['panel_enlaces']) ? sanitize_textarea_field(wp_unslash($_POST['panel_enlaces'])) : '',
            'panel_servicios' => isset($_POST['panel_servicios']) ? sanitize_textarea_field(wp_unslash($_POST['panel_servicios'])) : '',
        ));
        AF_Ajustes::flash(__('Panel de familias guardado.', 'area-familias'));
        wp_safe_redirect(admin_url('admin.php?page=area-familias-panel'));
        exit;
    }
}
