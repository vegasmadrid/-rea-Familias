<?php
/**
 * Espacios de aula
 *
 * Reads the families' Google Chat spaces from the Área de Profesores
 * (profesores.casadelavirgen.es) through its API, keeps a cached copy and shows
 * them in the "Espacios de aula" option of the family panel (home page).
 *
 * If the Área de Profesores cannot be reached, the last copy received is shown.
 */

if (!defined('ABSPATH')) {
    exit;
}

class AF_Espacios {

    const CACHE    = 'af_espacios_cache';     // transient (fresh copy)
    const ULTIMA   = 'af_espacios_ultima';    // option (last good copy, fallback)
    const ROUTE    = '/area-profesores/v1/espacios-aula';

    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('admin_menu', array($this, 'register_menu'), 20);
        add_action('admin_post_af_save_espacios', array($this, 'handle_save'));
        add_action('admin_post_af_refresh_espacios', array($this, 'handle_refresh'));
        add_shortcode('espacios_aula', array($this, 'render_shortcode'));
    }

    /* ---------------------------------------------------------------------
     * Datos
     * ------------------------------------------------------------------- */

    public static function get_endpoint() {
        $base = trailingslashit((string) AF_Ajustes::get('profesores_url'));
        return add_query_arg('rest_route', self::ROUTE, $base);
    }

    /**
     * Ask the Área de Profesores for the spaces.
     *
     * @return array|WP_Error
     */
    public static function fetch() {
        $key = (string) AF_Ajustes::get('api_key');
        if ($key === '' || trim((string) AF_Ajustes::get('profesores_url')) === '') {
            return new WP_Error('af_config', __('Falta la dirección del Área de Profesores o la clave de conexión.', 'area-familias'));
        }

        $response = wp_remote_get(self::get_endpoint(), array(
            'timeout' => 15,
            'headers' => array('X-Area-Key' => $key, 'Accept' => 'application/json'),
        ));
        if (is_wp_error($response)) {
            return new WP_Error('af_red', sprintf(__('No se pudo conectar con el Área de Profesores: %s', 'area-familias'), $response->get_error_message()));
        }

        $code = (int) wp_remote_retrieve_response_code($response);
        $data = json_decode(wp_remote_retrieve_body($response), true);
        if ($code === 403) {
            return new WP_Error('af_clave', __('El Área de Profesores ha rechazado la clave. Copia de nuevo la clave de "Espacios de Chat › Conexión con el Área Familias".', 'area-familias'));
        }
        if ($code === 404) {
            return new WP_Error('af_404', __('No se encuentra la conexión en el Área de Profesores. Comprueba la dirección y que el plugin Área Profesores esté actualizado (versión 1.6.0 o posterior).', 'area-familias'));
        }
        if ($code !== 200 || !is_array($data) || !isset($data['grupos'])) {
            return new WP_Error('af_respuesta', sprintf(__('Respuesta inesperada del Área de Profesores (HTTP %d).', 'area-familias'), $code));
        }

        $data['recibido'] = time();
        return $data;
    }

    /**
     * Spaces to show: cached copy, fresh copy or (if the connection fails) the last one received.
     *
     * @return array { data: array|null, error: string, desde_copia: bool }
     */
    public static function get_data($force = false) {
        if (!$force) {
            $cache = get_transient(self::CACHE);
            if (is_array($cache)) {
                return array('data' => $cache, 'error' => '', 'desde_copia' => false);
            }
        }

        $data = self::fetch();
        if (!is_wp_error($data)) {
            $minutos = max(5, (int) AF_Ajustes::get('cache_minutos'));
            set_transient(self::CACHE, $data, $minutos * MINUTE_IN_SECONDS);
            update_option(self::ULTIMA, $data, false);
            return array('data' => $data, 'error' => '', 'desde_copia' => false);
        }

        $ultima = get_option(self::ULTIMA);
        // Try again in 5 minutes instead of on every visit.
        if (is_array($ultima)) {
            set_transient(self::CACHE, $ultima, 5 * MINUTE_IN_SECONDS);
        }
        return array(
            'data'        => is_array($ultima) ? $ultima : null,
            'error'       => $data->get_error_message(),
            'desde_copia' => is_array($ultima),
        );
    }

    /* ---------------------------------------------------------------------
     * Backend
     * ------------------------------------------------------------------- */

    public function register_menu() {
        add_submenu_page('area-familias', __('Espacios de aula', 'area-familias'), __('Espacios de aula', 'area-familias'), 'manage_options', 'area-familias-espacios', array($this, 'render_admin'));
    }

    public function render_admin() {
        if (!current_user_can('manage_options')) {
            return;
        }
        $s = AF_Ajustes::all();
        $flash = AF_Ajustes::get_flash();
        $ultima = get_option(self::ULTIMA);
        include AREA_FAMILIAS_DIR . 'templates/admin-espacios.php';
    }

    public function handle_save() {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('No tienes permisos para realizar esta acción.', 'area-familias'));
        }
        check_admin_referer('af_save_espacios');

        $yes = function ($k) {
            return !empty($_POST[$k]) ? 'yes' : 'no';
        };
        $key = isset($_POST['api_key']) ? sanitize_text_field(wp_unslash($_POST['api_key'])) : '';

        $values = array(
            'profesores_url'      => isset($_POST['profesores_url']) ? esc_url_raw(trim(wp_unslash($_POST['profesores_url'])), array('https', 'http')) : '',
            'cache_minutos'       => isset($_POST['cache_minutos']) ? max(5, min(1440, absint($_POST['cache_minutos']))) : 60,
            'espacios_intro'      => isset($_POST['espacios_intro']) ? sanitize_textarea_field(wp_unslash($_POST['espacios_intro'])) : '',
            'espacios_tutor'      => $yes('espacios_tutor'),
            'espacios_foto'       => $yes('espacios_foto'),
            'espacios_anteriores' => $yes('espacios_anteriores'),
            'espacios_otros'      => $yes('espacios_otros'),
            'espacios_ayuda'      => $yes('espacios_ayuda'),
        );
        if ($key !== '') {
            $values['api_key'] = $key; // Empty field = keep the saved key.
        }

        AF_Ajustes::update($values);
        delete_transient(self::CACHE);

        // Check the connection right away.
        $r = self::get_data(true);
        if ($r['error'] === '') {
            AF_Ajustes::flash(sprintf(__('Guardado. Conexión correcta: %d espacios recibidos del Área de Profesores.', 'area-familias'), self::contar($r['data'])));
        } else {
            AF_Ajustes::flash(__('Guardado, pero la conexión ha fallado: ', 'area-familias') . $r['error'], 'error');
        }
        wp_safe_redirect(admin_url('admin.php?page=area-familias-espacios'));
        exit;
    }

    public function handle_refresh() {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('No tienes permisos para realizar esta acción.', 'area-familias'));
        }
        check_admin_referer('af_refresh_espacios');
        $r = self::get_data(true);
        if ($r['error'] === '') {
            AF_Ajustes::flash(sprintf(__('Espacios actualizados: %d recibidos del Área de Profesores.', 'area-familias'), self::contar($r['data'])));
        } else {
            AF_Ajustes::flash($r['error'], 'error');
        }
        wp_safe_redirect(admin_url('admin.php?page=area-familias-espacios'));
        exit;
    }

    public static function contar($data) {
        $n = 0;
        foreach ((array) (isset($data['grupos']) ? $data['grupos'] : array()) as $g) {
            $n += count($g['espacios']);
        }
        return $n + count((array) (isset($data['anteriores']) ? $data['anteriores'] : array()));
    }

    /* ---------------------------------------------------------------------
     * Página
     * ------------------------------------------------------------------- */

    public static function render_pagina() {
        $s = AF_Ajustes::all();
        $r = self::get_data();
        $data = $r['data'];

        $mostrar_tutor = $s['espacios_tutor'] === 'yes';
        $mostrar_foto = $mostrar_tutor && $s['espacios_foto'] === 'yes';
        $grupos = array();
        foreach ((array) ($data ? $data['grupos'] : array()) as $g) {
            if (empty($g['es_etapa']) && $s['espacios_otros'] !== 'yes') {
                continue;
            }
            $grupos[] = $g;
        }
        $anteriores = ($data && $s['espacios_anteriores'] === 'yes') ? (array) $data['anteriores'] : array();
        $intro = preg_replace('/\*\*(.+?)\*\*/s', '<strong>$1</strong>', esc_html($s['espacios_intro']));
        $mostrar_ayuda = $s['espacios_ayuda'] === 'yes';
        $sin_conexion = $data === null;

        ob_start();
        include AREA_FAMILIAS_DIR . 'templates/espacios.php';
        return ob_get_clean();
    }

    public function render_shortcode() {
        return self::render_pagina();
    }
}
