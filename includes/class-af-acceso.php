<?php
/**
 * Acceso al Área Familias.
 *
 * - Only accounts of the school's domain (@casadelavirgen.es) can register and
 *   use the site. Editors and administrators are exempt.
 * - "Restringir toda la web": visitors without a session go to the login page
 *   (except the login page and the pages marked as public).
 * - After logging in, families go to the start page (or back to the page they wanted).
 * - Families do not see the admin bar nor /wp-admin.
 */

if (!defined('ABSPATH')) {
    exit;
}

class AF_Acceso {

    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        // Domain restriction
        add_filter('nsl_can_register', array($this, 'filter_nsl_can_register'), 20, 3);
        add_filter('authenticate', array($this, 'filter_authenticate'), 100, 1);
        add_action('init', array($this, 'enforce_domain'), 1);

        // Whole-site restriction
        add_action('template_redirect', array($this, 'restrict_frontend'), 0);
        add_action('template_redirect', array($this, 'redirigir_mantenimiento'), 1);
        add_filter('rest_authentication_errors', array($this, 'restrict_rest_api'), 99);
        foreach (array('do_feed', 'do_feed_rdf', 'do_feed_rss', 'do_feed_rss2', 'do_feed_atom') as $feed) {
            add_action($feed, array($this, 'restrict_feed'), 0);
        }

        // Redirects and dashboard
        add_filter('login_redirect', array($this, 'filter_login_redirect'), 20, 3);
        add_filter('show_admin_bar', array($this, 'filter_admin_bar'), 20);
        add_action('admin_init', array($this, 'block_wp_admin'), 1);
    }

    /* ---------------------------------------------------------------------
     * Dominio
     * ------------------------------------------------------------------- */

    public static function get_domain() {
        return strtolower(trim(str_replace('@', '', (string) AF_Ajustes::get('allowed_domain'))));
    }

    public static function is_allowed_email($email) {
        $domain = self::get_domain();
        if ($domain === '') {
            return true;
        }
        $parts = explode('@', strtolower(trim((string) $email)));
        return isset($parts[1]) && $parts[1] === $domain;
    }

    /**
     * Editors/administrators may use any account (they manage the website).
     */
    private static function is_staff($user) {
        return $user instanceof WP_User && user_can($user, 'edit_posts');
    }

    public function filter_nsl_can_register($can_register, $provider = null, $user_data = null) {
        $email = '';
        if (is_array($user_data) && isset($user_data['email'])) {
            $email = $user_data['email'];
        } elseif (is_object($user_data) && isset($user_data->email)) {
            $email = $user_data->email;
        }
        if ($email === '' || !self::is_allowed_email($email)) {
            return false;
        }
        return $can_register;
    }

    /**
     * Username/password logins (wp-login.php) with another domain are rejected.
     */
    public function filter_authenticate($user) {
        if ($user instanceof WP_User && !self::is_staff($user) && !self::is_allowed_email($user->user_email)) {
            return new WP_Error('af_domain', sprintf(
                /* translators: %s: domain */
                __('Solo se puede entrar con una cuenta @%s.', 'area-familias'),
                self::get_domain()
            ));
        }
        return $user;
    }

    /**
     * Any session of another domain (e.g. Google login of an existing account) is closed.
     */
    public function enforce_domain() {
        if (!is_user_logged_in() || wp_doing_cron() || (defined('WP_CLI') && WP_CLI)) {
            return;
        }
        $user = wp_get_current_user();
        if (self::is_staff($user) || self::is_allowed_email($user->user_email)) {
            return;
        }
        wp_logout();
        if (wp_doing_ajax() || (defined('REST_REQUEST') && REST_REQUEST)) {
            return;
        }
        wp_safe_redirect(add_query_arg('af_error', 'dominio', self::get_login_url()));
        exit;
    }

    /* ---------------------------------------------------------------------
     * Páginas de login e inicio
     * ------------------------------------------------------------------- */

    public static function get_login_page_id() {
        $id = absint(AF_Ajustes::get('login_page_id'));
        return ($id && get_post_status($id) === 'publish') ? $id : 0;
    }

    public static function get_login_url($redirect_to = '') {
        $id = self::get_login_page_id();
        if ($id) {
            $url = get_permalink($id);
            return $redirect_to !== '' ? add_query_arg('redirect_to', rawurlencode($redirect_to), $url) : $url;
        }
        return wp_login_url($redirect_to);
    }

    /**
     * Maintenance mode active (families start on another page).
     */
    public static function en_mantenimiento() {
        $id = absint(AF_Ajustes::get('mantenimiento_page_id'));
        return AF_Ajustes::get('mantenimiento') === 'yes' && $id && get_post_status($id) === 'publish';
    }

    public static function get_start_url() {
        // During maintenance families start on the maintenance page; editors and
        // administrators keep the real start page to test the changes.
        if (self::en_mantenimiento() && !self::is_staff(wp_get_current_user())) {
            return get_permalink(absint(AF_Ajustes::get('mantenimiento_page_id')));
        }
        $id = absint(AF_Ajustes::get('start_page_id'));
        if ($id && get_post_status($id) === 'publish') {
            return get_permalink($id);
        }
        return home_url('/');
    }

    public static function get_logout_url() {
        return wp_logout_url(self::get_login_url());
    }

    /**
     * Where to go after logging in: the requested internal page or the start page.
     */
    public static function resolve_redirect($requested = '') {
        $start = self::get_start_url();
        if ($requested === '' || AF_Ajustes::get('respect_redirect_to') !== 'yes') {
            return $start;
        }
        $requested = wp_validate_redirect($requested, '');
        if ($requested === '' || strpos($requested, admin_url()) === 0) {
            return $start;
        }
        $norm = function ($url) {
            $p = wp_parse_url($url);
            // The query is part of the page (plain permalinks: /?page_id=6).
            return strtolower((isset($p['host']) ? $p['host'] : '') . (isset($p['path']) ? untrailingslashit($p['path']) : '') . (isset($p['query']) ? '?' . $p['query'] : ''));
        };
        $generic = array($norm(home_url('/')));
        if (self::get_login_page_id()) {
            $generic[] = $norm(get_permalink(self::get_login_page_id()));
        }
        return in_array($norm($requested), $generic, true) ? $start : $requested;
    }

    public function filter_login_redirect($redirect_to, $requested, $user) {
        if (!($user instanceof WP_User) || self::is_staff($user)) {
            return $redirect_to;
        }
        return self::resolve_redirect((string) $requested);
    }

    /* ---------------------------------------------------------------------
     * Restricción de la web
     * ------------------------------------------------------------------- */

    public static function is_restricted() {
        return AF_Ajustes::get('restrict_site') === 'yes';
    }

    public static function get_public_page_ids() {
        $ids = array_map('absint', (array) AF_Ajustes::get('public_page_ids'));
        if (self::get_login_page_id()) {
            $ids[] = self::get_login_page_id();
        }
        return array_values(array_unique(array_filter($ids)));
    }

    private function is_request_allowed() {
        if (is_user_logged_in() || !self::is_restricted()) {
            return true;
        }
        if (wp_doing_ajax() || wp_doing_cron() || is_admin() || (defined('WP_CLI') && WP_CLI)) {
            return true;
        }
        if (is_robots() || (function_exists('is_favicon') && is_favicon())) {
            return true;
        }
        // Nextend Social Login callbacks must reach Google.
        if (isset($_GET['loginSocial'])) {
            return true;
        }
        $public = self::get_public_page_ids();
        if ($public && (in_array((int) get_queried_object_id(), $public, true) || is_page($public))) {
            return true;
        }
        return (bool) apply_filters('area_familias_is_public_request', false);
    }

    /**
     * During maintenance, families who open the panel page go to the maintenance page.
     */
    public function redirigir_mantenimiento() {
        if (!self::en_mantenimiento() || !is_user_logged_in() || self::is_staff(wp_get_current_user())) {
            return;
        }
        $start = absint(AF_Ajustes::get('start_page_id'));
        if ($start && (int) get_queried_object_id() === $start) {
            wp_safe_redirect(get_permalink(absint(AF_Ajustes::get('mantenimiento_page_id'))));
            exit;
        }
    }

    public function restrict_frontend() {
        if ($this->is_request_allowed()) {
            return;
        }
        $scheme = is_ssl() ? 'https://' : 'http://';
        $host = isset($_SERVER['HTTP_HOST']) ? wp_unslash($_SERVER['HTTP_HOST']) : wp_parse_url(home_url(), PHP_URL_HOST);
        $uri = isset($_SERVER['REQUEST_URI']) ? wp_unslash($_SERVER['REQUEST_URI']) : '/';
        nocache_headers();
        wp_safe_redirect(self::get_login_url(esc_url_raw($scheme . $host . $uri)), 302);
        exit;
    }

    public function restrict_feed() {
        if (!is_user_logged_in() && self::is_restricted()) {
            wp_safe_redirect(self::get_login_url(), 302);
            exit;
        }
    }

    public function restrict_rest_api($result) {
        if (!empty($result) || is_user_logged_in() || !self::is_restricted()) {
            return $result;
        }
        if (apply_filters('area_familias_is_public_rest_request', false)) {
            return $result;
        }
        return new WP_Error('rest_not_logged_in', __('Debes iniciar sesión para acceder a este contenido.', 'area-familias'), array('status' => 401));
    }

    /* ---------------------------------------------------------------------
     * Barra y escritorio de WordPress
     * ------------------------------------------------------------------- */

    public function filter_admin_bar($show) {
        if (AF_Ajustes::get('hide_admin_bar') === 'yes' && is_user_logged_in() && !self::is_staff(wp_get_current_user())) {
            return false;
        }
        return $show;
    }

    public function block_wp_admin() {
        if (AF_Ajustes::get('block_wp_admin') !== 'yes' || wp_doing_ajax() || !is_user_logged_in() || self::is_staff(wp_get_current_user())) {
            return;
        }
        global $pagenow;
        if ($pagenow === 'admin-post.php') {
            return;
        }
        wp_safe_redirect(self::get_start_url());
        exit;
    }
}
