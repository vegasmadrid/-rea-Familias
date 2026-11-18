<?php
/**
 * Ajustes del Área Familias y menú del backend.
 *
 * Menu "Área Familias":
 * - Ajustes: login and start pages, access only with the school's accounts.
 * - Espacios de aula: page that shows the Chat spaces read from the Área de Profesores.
 */

if (!defined('ABSPATH')) {
    exit;
}

class AF_Ajustes {

    const OPTION = 'af_settings';

    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('admin_menu', array($this, 'register_menu'));
        add_action('admin_post_af_save_ajustes', array($this, 'handle_save'));
    }

    public static function defaults() {
        return array(
            // Acceso
            'allowed_domain'     => 'casadelavirgen.es',
            'restrict_site'      => 'yes',
            'login_page_id'      => 0,
            'start_page_id'      => 0,
            'respect_redirect_to'=> 'yes',
            'public_page_ids'    => array(),
            'hide_admin_bar'     => 'yes',
            'block_wp_admin'     => 'yes',
            // Imagen
            'logo_url'           => 'https://madrid.casadelavirgen.es/wp-content/uploads/elementor/thumbs/Logo_Color_Casa-de-la-Virgen_Colegio-copia-q58if27vst5qhnn7a4gmb1ham9rcffq7jtrdjo1n9c.png',
            'tribanda_url'       => 'https://madrid.casadelavirgen.es/wp-content/uploads/2019/01/Tribanda-corporativa-SUP-e1611846970284-1024x87.png',
            'login_titulo'       => 'Área de Familias',
            'login_texto'        => 'Acceso para las familias del Colegio Casa de la Virgen.',
            'login_boton'        => 'Accede con tu cuenta del colegio',
            // Espacios de aula
            'profesores_url'     => 'https://profesores.casadelavirgen.es/',
            'api_key'            => '',
            'cache_minutos'      => 60,
            'espacios_page_id'   => 0,
            'espacios_titulo'    => 'Espacios de aula',
            'espacios_intro'     => 'Busca el curso de tu hijo o hija y pulsa **Abrir en Google Chat**. Entra con la cuenta del colegio con la que se te ha añadido al espacio.',
            'espacios_tribanda'  => 'yes',
            'espacios_tutor'     => 'yes',
            'espacios_foto'      => 'yes',
            'espacios_anteriores'=> 'yes',
            'espacios_otros'     => 'yes',
            'espacios_ayuda'     => 'yes',
        );
    }

    public static function get($key) {
        $s = get_option(self::OPTION, array());
        $d = self::defaults();
        if (is_array($s) && array_key_exists($key, $s)) {
            return $s[$key];
        }
        return isset($d[$key]) ? $d[$key] : '';
    }

    public static function all() {
        $s = get_option(self::OPTION, array());
        return array_merge(self::defaults(), is_array($s) ? $s : array());
    }

    public static function update($values) {
        $s = get_option(self::OPTION, array());
        update_option(self::OPTION, array_merge(is_array($s) ? $s : array(), $values));
    }

    public static function flash($text, $type = 'success') {
        set_transient('af_flash_' . get_current_user_id(), array('type' => $type, 'text' => $text), 120);
    }

    public static function get_flash() {
        $f = get_transient('af_flash_' . get_current_user_id());
        if ($f) {
            delete_transient('af_flash_' . get_current_user_id());
        }
        return $f;
    }

    /* ---------------------------------------------------------------------
     * Menú
     * ------------------------------------------------------------------- */

    public function register_menu() {
        add_menu_page(
            __('Área Familias', 'area-familias'),
            __('Área Familias', 'area-familias'),
            'manage_options',
            'area-familias',
            array($this, 'render_page'),
            'dashicons-groups',
            26
        );
        add_submenu_page('area-familias', __('Ajustes', 'area-familias'), __('Ajustes', 'area-familias'), 'manage_options', 'area-familias', array($this, 'render_page'));
    }

    public function render_page() {
        if (!current_user_can('manage_options')) {
            return;
        }
        $s = self::all();
        $flash = self::get_flash();
        $pages = get_pages(array('sort_column' => 'post_title'));
        $nextend = class_exists('NextendSocialLogin');
        include AREA_FAMILIAS_DIR . 'templates/admin-ajustes.php';
    }

    public function handle_save() {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('No tienes permisos para realizar esta acción.', 'area-familias'));
        }
        check_admin_referer('af_save_ajustes');

        $txt = function ($k) {
            return isset($_POST[$k]) ? sanitize_text_field(wp_unslash($_POST[$k])) : '';
        };
        $yes = function ($k) {
            return !empty($_POST[$k]) ? 'yes' : 'no';
        };

        $domain = strtolower(trim(str_replace('@', '', $txt('allowed_domain'))));

        self::update(array(
            'allowed_domain'      => $domain,
            'restrict_site'       => $yes('restrict_site'),
            'login_page_id'       => isset($_POST['login_page_id']) ? absint($_POST['login_page_id']) : 0,
            'start_page_id'       => isset($_POST['start_page_id']) ? absint($_POST['start_page_id']) : 0,
            'respect_redirect_to' => $yes('respect_redirect_to'),
            'public_page_ids'     => isset($_POST['public_page_ids']) ? array_values(array_filter(array_map('absint', (array) wp_unslash($_POST['public_page_ids'])))) : array(),
            'hide_admin_bar'      => $yes('hide_admin_bar'),
            'block_wp_admin'      => $yes('block_wp_admin'),
            'logo_url'            => isset($_POST['logo_url']) ? esc_url_raw(wp_unslash($_POST['logo_url'])) : '',
            'tribanda_url'        => isset($_POST['tribanda_url']) ? esc_url_raw(wp_unslash($_POST['tribanda_url'])) : '',
            'login_titulo'        => $txt('login_titulo'),
            'login_texto'         => $txt('login_texto'),
            'login_boton'         => $txt('login_boton'),
        ));

        self::flash(__('Ajustes guardados.', 'area-familias'));
        wp_safe_redirect(admin_url('admin.php?page=area-familias'));
        exit;
    }
}
