<?php
/**
 * Pantalla de acceso del Área Familias (inyectada en la página de login).
 * Google button of Nextend Social Login (without the Google logo) and the
 * reminder that only @casadelavirgen.es accounts can enter. New families see a
 * button to the online enrolment request (AF_Matricula) while it is open.
 */

if (!defined('ABSPATH')) {
    exit;
}

class AF_Login {

    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_shortcode('login_familias', array($this, 'render'));
    }

    /**
     * Google login URL of Nextend Social Login.
     */
    public static function get_google_login_url($redirect) {
        if (class_exists('NextendSocialLogin') && method_exists('NextendSocialLogin', 'getProvider')) {
            $provider = NextendSocialLogin::getProvider('google');
            if ($provider && method_exists($provider, 'getLoginUrl')) {
                return $provider->getLoginUrl($redirect);
            }
        }
        return add_query_arg(array('loginSocial' => 'google', 'redirect' => rawurlencode($redirect)), wp_login_url());
    }

    public static function render_static() {
        return self::get_instance()->render();
    }

    public function render() {
        $s = AF_Ajustes::all();
        $requested = !empty($_GET['redirect_to']) ? esc_url_raw(wp_unslash($_GET['redirect_to'])) : '';
        $redirect = AF_Acceso::resolve_redirect($requested);
        $google_url = self::get_google_login_url($redirect);
        $domain = AF_Acceso::get_domain();
        $error = isset($_GET['af_error']) ? sanitize_key($_GET['af_error']) : '';
        $user = is_user_logged_in() ? wp_get_current_user() : null;
        $start_url = AF_Acceso::get_start_url();
        $logout_url = AF_Acceso::get_logout_url();
        // New families (without an account yet): button to the enrolment request.
        $matricula_url = AF_Matricula::url_boton_login();
        $matricula_boton = (string) AF_Matricula::get('boton_login');

        ob_start();
        include AREA_FAMILIAS_DIR . 'templates/login.php';
        return ob_get_clean();
    }
}
