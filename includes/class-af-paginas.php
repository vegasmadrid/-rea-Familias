<?php
/**
 * Inyecta las pantallas del plugin en las páginas elegidas en el backend
 * (login y espacios de aula), sin necesidad de shortcodes.
 */

if (!defined('ABSPATH')) {
    exit;
}

class AF_Paginas {

    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        // Late priority so page builders do not alter the generated HTML.
        add_filter('the_content', array($this, 'inject'), 999);
    }

    private function get_map() {
        $map = array();
        $login = absint(AF_Ajustes::get('login_page_id'));
        $espacios = absint(AF_Ajustes::get('espacios_page_id'));
        if ($login) {
            $map[$login] = array('AF_Login', 'render_static');
        }
        if ($espacios && !isset($map[$espacios])) {
            $map[$espacios] = array('AF_Espacios', 'render_pagina');
        }
        $inicio = absint(AF_Ajustes::get('start_page_id'));
        if ($inicio && !isset($map[$inicio])) {
            $map[$inicio] = array('AF_Panel', 'render');
        }
        return $map;
    }

    public function inject($content) {
        if (is_admin() || is_feed() || !is_singular() || doing_filter('get_the_excerpt')) {
            return $content;
        }
        $post_id = (int) get_the_ID();
        if (!$post_id || $post_id !== (int) get_queried_object_id()) {
            return $content;
        }
        $map = $this->get_map();
        if (!isset($map[$post_id])) {
            return $content;
        }

        // Render once per request (some themes call the_content several times).
        static $done = array();
        if (!isset($done[$post_id])) {
            $done[$post_id] = call_user_func($map[$post_id]);
        }
        return $done[$post_id];
    }
}
