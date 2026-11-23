<?php
/**
 * Reservar un servicio (panel de la familia).
 *
 * - The services and their rules are configured in "Área Familias › Reservas":
 *   name, description, schedule, price, deadline, whether several days can be
 *   booked at once, the etapas that can use it, extra e-mails to notify, and
 *   the questions of each one (desayuno, dieta, who picks the child up…).
 * - The family chooses one or several children, the service and the day (or a
 *   range of days with weekdays) and answers its questions. The reservation is
 *   saved in the Área de Profesores, which e-mails the tutor/a, Secretaría and
 *   the family (Avisos del Área de Profesores).
 * - "Mis reservas": upcoming reservations of the account, which can be
 *   cancelled until the same deadline.
 */

if (!defined('ABSPATH')) {
    exit;
}

class AF_Reservas {

    const OPTION = 'af_reservas';

    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('admin_menu', array($this, 'register_menu'), 16);
        add_action('admin_post_af_save_reservas', array($this, 'handle_save'));
        add_action('admin_post_af_reservar', array($this, 'handle_reservar'));
        add_action('admin_post_af_anular_reserva', array($this, 'handle_anular'));
    }

    /* ---------------------------------------------------------------------
     * Configuración
     * ------------------------------------------------------------------- */

    public static function etapas() {
        return array(
            'primer_ciclo' => __('Primer Ciclo', 'area-familias'),
            'infantil'     => __('Infantil', 'area-familias'),
            'primaria'     => __('Primaria', 'area-familias'),
            'secundaria'   => __('Secundaria', 'area-familias'),
        );
    }

    /**
     * Services, with their default settings. The key is fixed (it identifies
     * the service in the Área de Profesores); everything else is editable.
     */
    public static function definiciones() {
        $base = array(
            'activo'      => 'yes',
            'descripcion' => '',
            'hora_inicio' => '',
            'hora_fin'    => '',
            'precio'      => '',
            'plazo_dias'  => 0,
            'plazo_hora'  => '',
            'varios_dias' => 'yes',
            'etapas'      => array(),
            'avisar'      => '',
            'nota'        => '',
        );
        return array(
            'ampliacion' => array_merge($base, array(
                'nombre'      => __('Ampliación de horario', 'area-familias'),
                'descripcion' => __('Por la mañana, antes de empezar las clases. Puedes elegir si quieres desayuno.', 'area-familias'),
                'hora_inicio' => '07:30',
                'hora_fin'    => '09:45',
                'plazo_dias'  => 1,
                'plazo_hora'  => '20:00',
                'desayuno'    => 'yes',
                'icono'       => '☀️',
            )),
            'comedor' => array_merge($base, array(
                'nombre'      => __('Comedor', 'area-familias'),
                'descripcion' => __('Comida en el comedor del colegio.', 'area-familias'),
                'plazo_dias'  => 0,
                'plazo_hora'  => '09:30',
                'dieta'       => 'yes',
                'icono'       => '🍽️',
            )),
            'ludoteca' => array_merge($base, array(
                'nombre'      => __('Ludoteca', 'area-familias'),
                'descripcion' => __('Por la tarde, después de las clases.', 'area-familias'),
                'hora_inicio' => '16:30',
                'hora_fin'    => '18:00',
                'plazo_dias'  => 0,
                'plazo_hora'  => '14:00',
                'icono'       => '🧩',
            )),
            'dieta' => array_merge($base, array(
                'nombre'      => __('Dieta', 'area-familias'),
                'descripcion' => __('Si come en el colegio y necesita una dieta especial (por ejemplo, dieta blanda unos días).', 'area-familias'),
                'plazo_dias'  => 0,
                'plazo_hora'  => '09:30',
                'tipos_dieta' => "Dieta blanda\nSin lactosa\nSin gluten\nOtra (explícala en las instrucciones)",
                'icono'       => '🥗',
            )),
            'salida' => array_merge($base, array(
                'nombre'      => __('Salida anticipada', 'area-familias'),
                'descripcion' => __('Si tu hijo o hija tiene que salir del colegio antes de la hora.', 'area-familias'),
                'hora_inicio' => '09:00',
                'hora_fin'    => '17:00',
                'varios_dias' => 'no',
                'recoge'      => 'yes',
                'icono'       => '🚪',
            )),
            'retraso' => array_merge($base, array(
                'nombre'      => __('Retraso justificado', 'area-familias'),
                'descripcion' => __('Si va a llegar al colegio más tarde de la hora de entrada.', 'area-familias'),
                'hora_inicio' => '09:00',
                'hora_fin'    => '14:00',
                'varios_dias' => 'no',
                'icono'       => '⏰',
            )),
        );
    }

    public static function defaults_generales() {
        return array(
            'intro'          => __('Elige a tu hijo o hija, el servicio y el día. Avisaremos a su tutor/a y a secretaría y te enviaremos un correo con la confirmación.', 'area-familias'),
            'festivos'       => '',
            'max_antelacion' => 90,
        );
    }

    private static function raw() {
        $o = get_option(self::OPTION, array());
        return is_array($o) ? $o : array();
    }

    public static function generales() {
        $o = self::raw();
        return array_merge(self::defaults_generales(), isset($o['generales']) && is_array($o['generales']) ? $o['generales'] : array());
    }

    /**
     * Settings of every service (defaults + saved values).
     */
    public static function servicios() {
        $o = self::raw();
        $saved = isset($o['servicios']) && is_array($o['servicios']) ? $o['servicios'] : array();
        $out = array();
        foreach (self::definiciones() as $k => $d) {
            $s = isset($saved[$k]) && is_array($saved[$k]) ? array_intersect_key($saved[$k], $d) : array();
            $out[$k] = array_merge($d, $s, array('key' => $k, 'icono' => $d['icono']));
        }
        return $out;
    }

    public static function activos() {
        return array_filter(self::servicios(), function ($s) {
            return $s['activo'] === 'yes';
        });
    }

    /**
     * "De 7:30 a 9:45" (or '' if the service has no schedule).
     */
    public static function horario($s) {
        if ($s['hora_inicio'] !== '' && $s['hora_fin'] !== '' && !in_array($s['key'], array('salida', 'retraso'), true)) {
            return sprintf(__('De %1$s a %2$s', 'area-familias'), self::hora_txt($s['hora_inicio']), self::hora_txt($s['hora_fin']));
        }
        return '';
    }

    private static function hora_txt($h) {
        return preg_replace('/^0(\d)/', '$1', $h);
    }

    /**
     * Text of the deadline: "Hasta las 20:00 del día anterior".
     */
    public static function plazo_txt($s) {
        $d = (int) $s['plazo_dias'];
        if ($s['plazo_hora'] === '') {
            if ($d === 0) {
                return in_array($s['key'], array('salida', 'retraso'), true) ? __('Hasta la hora que indiques.', 'area-familias') : __('Hasta el mismo día.', 'area-familias');
            }
            return sprintf(_n('Hasta el día anterior.', 'Hasta %d días antes.', $d, 'area-familias'), $d);
        }
        if ($d === 0) {
            return sprintf(__('Hasta las %s del mismo día.', 'area-familias'), $s['plazo_hora']);
        }
        if ($d === 1) {
            return sprintf(__('Hasta las %s del día anterior.', 'area-familias'), $s['plazo_hora']);
        }
        return sprintf(__('Hasta las %1$s, %2$d días antes.', 'area-familias'), $s['plazo_hora'], $d);
    }

    /* ---------------------------------------------------------------------
     * Fechas
     * ------------------------------------------------------------------- */

    private static function fecha_ok($f) {
        return is_string($f) && preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $f, $m) && checkdate((int) $m[2], (int) $m[3], (int) $m[1]);
    }

    private static function parse_fecha($t) {
        $t = trim($t);
        if (preg_match('/^(\d{1,2})[\/\-.](\d{1,2})[\/\-.](\d{4})$/', $t, $m)) {
            $t = sprintf('%04d-%02d-%02d', $m[3], $m[2], $m[1]);
        }
        return self::fecha_ok($t) ? $t : '';
    }

    /**
     * Non-school days (one per line: "08/12/2026", "23/12/2026 - 07/01/2027", optional "| comment").
     *
     * @return array Y-m-d => true
     */
    public static function festivos() {
        static $cache = null;
        if ($cache !== null) {
            return $cache;
        }
        $cache = self::festivos_propios();
        // Plus the "Días sin clase" of the Área de Profesores (Comedor Diario), cached for an hour.
        $remotos = get_transient('af_dias_sin_clase');
        if (!is_array($remotos)) {
            $r = AF_Panel::api('GET', '/calendario');
            $remotos = (!is_wp_error($r) && isset($r['dias_sin_clase']) && is_array($r['dias_sin_clase'])) ? $r['dias_sin_clase'] : array();
            set_transient('af_dias_sin_clase', $remotos, is_wp_error($r) ? 10 * MINUTE_IN_SECONDS : HOUR_IN_SECONDS);
            if (!is_wp_error($r)) {
                self::sincronizar();
            }
        }
        foreach ($remotos as $f) {
            if (self::fecha_ok($f)) {
                $cache[$f] = true;
            }
        }
        return $cache;
    }

    /**
     * Non-school days written in these settings (Y-m-d => true).
     */
    public static function festivos_propios() {
        $out = array();
        foreach (preg_split('/\r\n|\r|\n/', (string) self::generales()['festivos']) as $line) {
            $line = trim(explode('|', $line)[0]);
            if ($line === '') {
                continue;
            }
            $p = preg_split('/\s+(?:-|–|al|a)\s+/u', $line);
            $ini = self::parse_fecha($p[0]);
            $fin = isset($p[1]) ? self::parse_fecha($p[1]) : $ini;
            if ($ini === '' || $fin === '' || $fin < $ini) {
                continue;
            }
            for ($t = strtotime($ini . ' 12:00:00 UTC'), $e = strtotime($fin . ' 12:00:00 UTC'), $i = 0; $t <= $e && $i < 400; $t += DAY_IN_SECONDS, $i++) {
                $out[gmdate('Y-m-d', $t)] = true;
            }
        }
        return $out;
    }

    /**
     * Send the services and the days without school to the Área de Profesores, so that
     * Portería can book from there with the same rules. Only when something changed
     * (or $forzar).
     */
    public static function sincronizar($forzar = false) {
        $servicios = array();
        foreach (self::servicios() as $k => $sv) {
            $servicios[$k] = array_intersect_key($sv, array_flip(array('activo', 'nombre', 'icono', 'hora_inicio', 'hora_fin', 'precio', 'varios_dias', 'etapas', 'avisar', 'desayuno', 'dieta', 'recoge', 'tipos_dieta')));
        }
        $datos = array('servicios' => $servicios, 'festivos' => array_keys(self::festivos_propios()));
        $hash = md5(wp_json_encode($datos));
        if (!$forzar && get_option('af_servicios_sync') === $hash) {
            return true;
        }
        $r = AF_Panel::api('POST', '/servicios', $datos);
        if (is_wp_error($r)) {
            return $r;
        }
        update_option('af_servicios_sync', $hash, false);
        return true;
    }

    private static function dow($f) {
        return (int) gmdate('N', strtotime($f . ' 12:00:00 UTC'));
    }

    public static function es_lectivo($f) {
        return self::dow($f) <= 5 && !isset(self::festivos()[$f]);
    }

    private static function sumar($f, $dias) {
        return gmdate('Y-m-d', strtotime($f . ' 12:00:00 UTC') + $dias * DAY_IN_SECONDS);
    }

    /**
     * Last moment to book (or cancel) the service for a date.
     */
    public static function limite($s, $fecha, $hora = '') {
        $tz = wp_timezone();
        $d = self::sumar($fecha, -max(0, (int) $s['plazo_dias']));
        if ($s['plazo_hora'] !== '') {
            $h = $s['plazo_hora'];
        } elseif ((int) $s['plazo_dias'] === 0 && $hora !== '') {
            $h = $hora;
        } else {
            $h = '23:59';
        }
        return new DateTimeImmutable($d . ' ' . $h, $tz);
    }

    public static function a_tiempo($s, $fecha, $hora = '') {
        return new DateTimeImmutable('now', wp_timezone()) < self::limite($s, $fecha, $hora);
    }

    /**
     * First date that can still be booked for a service (for the date picker).
     */
    public static function primera_fecha($s) {
        $f = current_time('Y-m-d');
        for ($i = 0; $i < 60; $i++, $f = self::sumar($f, 1)) {
            if (self::es_lectivo($f) && self::a_tiempo($s, $f, $s['key'] === 'salida' || $s['key'] === 'retraso' ? $s['hora_fin'] : '')) {
                return $f;
            }
        }
        return $f;
    }

    /* ---------------------------------------------------------------------
     * Reservas de la cuenta (Área de Profesores)
     * ------------------------------------------------------------------- */

    private static function cache_key($email) {
        return 'af_res_' . md5(strtolower($email));
    }

    public static function get_reservas($email) {
        $c = get_transient(self::cache_key($email));
        if (is_array($c)) {
            return $c;
        }
        $r = AF_Panel::api('GET', '/familia/reservas', array('email' => $email));
        if (is_wp_error($r)) {
            return $r;
        }
        $lista = isset($r['reservas']) && is_array($r['reservas']) ? $r['reservas'] : array();
        set_transient(self::cache_key($email), $lista, 5 * MINUTE_IN_SECONDS);
        return $lista;
    }

    /**
     * Whether the family can still cancel a reservation online.
     */
    public static function anulable($r) {
        if ($r['estado'] !== 'activa') {
            return false;
        }
        $serv = self::servicios();
        $s = isset($serv[$r['servicio']]) ? $serv[$r['servicio']] : array_merge(self::definiciones()['comedor'], array('key' => $r['servicio']));
        return self::a_tiempo($s, $r['fecha_inicio'], (string) $r['hora']);
    }

    /* ---------------------------------------------------------------------
     * Reservar
     * ------------------------------------------------------------------- */

    private static function back($type, $text, $old = null) {
        $uid = get_current_user_id();
        set_transient('af_panel_msg_' . $uid, array('type' => $type, 'text' => $text), 120);
        if ($old !== null) {
            set_transient('af_res_old_' . $uid, $old, 600);
        }
        $url = wp_get_referer() ? wp_get_referer() : AF_Acceso::get_start_url();
        wp_safe_redirect(strtok($url, '#') . '#reservar');
        exit;
    }

    public static function panel_url() {
        $id = absint(AF_Ajustes::get('start_page_id'));
        $url = ($id && get_post_status($id) === 'publish') ? get_permalink($id) : home_url('/');
        return $url . '#reservar';
    }

    /**
     * Validate the form and build the reservation for the Área de Profesores.
     *
     * @return array|WP_Error
     */
    public static function validar($p, $hijos) {
        $serv = self::activos();
        $k = isset($p['servicio']) ? sanitize_key($p['servicio']) : '';
        if (!isset($serv[$k])) {
            return new WP_Error('af', __('Elige el servicio que quieres reservar.', 'area-familias'));
        }
        $s = $serv[$k];

        // Children
        $ids = array_values(array_unique(array_map('absint', isset($p['alumnos']) ? (array) $p['alumnos'] : array())));
        $por_id = array();
        foreach ($hijos as $h) {
            $por_id[(int) $h['id']] = $h;
        }
        if (!$ids) {
            return new WP_Error('af', __('Elige para qué hijo o hija es la reserva.', 'area-familias'));
        }
        foreach ($ids as $id) {
            if (!isset($por_id[$id])) {
                return new WP_Error('af', __('Ese alumno no está asociado a tu cuenta.', 'area-familias'));
            }
            if ($s['etapas'] && !in_array($por_id[$id]['etapa_key'], (array) $s['etapas'], true)) {
                return new WP_Error('af', sprintf(__('El servicio %1$s no está disponible para %2$s.', 'area-familias'), $s['nombre'], $por_id[$id]['nombre']));
            }
        }

        $txt = function ($name, $max = 500) use ($p) {
            return isset($p[$name]) ? mb_substr(trim(sanitize_textarea_field($p[$name])), 0, $max) : '';
        };

        // Time (salida anticipada / retraso justificado)
        $hora = '';
        if ($k === 'salida' || $k === 'retraso') {
            $hora = isset($p['hora']) ? substr(sanitize_text_field($p['hora']), 0, 5) : '';
            if (!preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $hora)) {
                return new WP_Error('af', $k === 'salida' ? __('Indica a qué hora va a salir.', 'area-familias') : __('Indica a qué hora va a llegar.', 'area-familias'));
            }
            if (($s['hora_inicio'] !== '' && $hora < $s['hora_inicio']) || ($s['hora_fin'] !== '' && $hora > $s['hora_fin'])) {
                return new WP_Error('af', sprintf(__('La hora tiene que estar entre las %1$s y las %2$s.', 'area-familias'), $s['hora_inicio'], $s['hora_fin']));
            }
        }

        // Dates
        $hoy = current_time('Y-m-d');
        $max = self::sumar($hoy, max(1, (int) self::generales()['max_antelacion']));
        $varios = $s['varios_dias'] === 'yes' && isset($p['modo']) && $p['modo'] === 'varios';
        if ($varios) {
            $desde = isset($p['desde']) ? sanitize_text_field($p['desde']) : '';
            $hasta = isset($p['hasta']) ? sanitize_text_field($p['hasta']) : '';
            if (!self::fecha_ok($desde) || !self::fecha_ok($hasta) || $hasta < $desde) {
                return new WP_Error('af', __('Indica bien el primer y el último día.', 'area-familias'));
            }
            $dias = array_values(array_unique(array_filter(array_map('intval', isset($p['dias']) ? (array) $p['dias'] : array()), function ($d) {
                return $d >= 1 && $d <= 5;
            })));
            sort($dias);
            if (!$dias) {
                return new WP_Error('af', __('Marca al menos un día de la semana.', 'area-familias'));
            }
            if ($hasta > self::sumar($desde, 366)) {
                return new WP_Error('af', __('Como mucho se puede reservar un curso entero de una vez.', 'area-familias'));
            }
            // First and last real day.
            $lista = array();
            for ($f = $desde, $i = 0; $f <= $hasta && $i < 400; $f = self::sumar($f, 1), $i++) {
                if (self::es_lectivo($f) && in_array(self::dow($f), $dias, true)) {
                    $lista[] = $f;
                }
            }
            if (!$lista) {
                return new WP_Error('af', __('En esas fechas no hay ningún día lectivo con los días de la semana marcados.', 'area-familias'));
            }
            $ini = $lista[0];
            $fin = end($lista);
            if (count($dias) === 5) {
                $dias = array();
            }
        } else {
            $ini = isset($p['fecha']) ? sanitize_text_field($p['fecha']) : '';
            if (!self::fecha_ok($ini)) {
                return new WP_Error('af', __('Indica el día.', 'area-familias'));
            }
            if (!self::es_lectivo($ini)) {
                return new WP_Error('af', __('Ese día no hay clase. Elige otro día.', 'area-familias'));
            }
            $fin = $ini;
            $dias = array();
        }
        if ($ini < $hoy) {
            return new WP_Error('af', __('Esa fecha ya ha pasado.', 'area-familias'));
        }
        if ($ini > $max) {
            return new WP_Error('af', sprintf(__('Solo se puede reservar con %d días de antelación como máximo.', 'area-familias'), (int) self::generales()['max_antelacion']));
        }
        if (!self::a_tiempo($s, $ini, $hora)) {
            return new WP_Error('af', sprintf(__('Ya ha pasado el plazo para reservar %1$s el %2$s (%3$s). Si es urgente, llama al colegio.', 'area-familias'), $s['nombre'], date_i18n('j/n/Y', strtotime($ini . ' 12:00:00')), rtrim(lcfirst(self::plazo_txt($s)), '.')));
        }

        // Details
        $det = array();
        $add = function ($label, $valor) use (&$det) {
            if (trim((string) $valor) !== '') {
                $det[] = array('label' => $label, 'valor' => $valor);
            }
        };
        if ($h = self::horario($s)) {
            $add(__('Horario', 'area-familias'), $h);
        }
        $pide_dieta = false;
        switch ($k) {
            case 'ampliacion':
                if ($s['desayuno'] === 'yes') {
                    if (!isset($p['desayuno']) || !in_array($p['desayuno'], array('si', 'no'), true)) {
                        return new WP_Error('af', __('Indica si quieres desayuno.', 'area-familias'));
                    }
                    $add(__('Desayuno', 'area-familias'), $p['desayuno'] === 'si' ? __('Sí', 'area-familias') : __('No', 'area-familias'));
                }
                break;
            case 'comedor':
                if ($s['dieta'] === 'yes') {
                    if (!isset($p['dieta']) || !in_array($p['dieta'], array('si', 'no'), true)) {
                        return new WP_Error('af', __('Indica si necesita dieta.', 'area-familias'));
                    }
                    $add(__('Dieta', 'area-familias'), $p['dieta'] === 'si' ? __('Sí', 'area-familias') : __('No', 'area-familias'));
                    $pide_dieta = $p['dieta'] === 'si';
                }
                break;
            case 'dieta':
                $pide_dieta = true;
                break;
            case 'salida':
                $add(__('Hora de salida', 'area-familias'), $hora);
                if ($s['recoge'] === 'yes') {
                    $add(__('Quién le recoge', 'area-familias'), $txt('recoge', 150));
                }
                break;
            case 'retraso':
                $add(__('Hora de llegada', 'area-familias'), $hora);
                break;
        }
        if ($pide_dieta) {
            $tipo = $txt('tipo_dieta', 150);
            $instr = $txt('instrucciones', 1000);
            if ($tipo === '' && $instr === '') {
                return new WP_Error('af', __('Indica qué dieta necesita (el tipo o las instrucciones).', 'area-familias'));
            }
            $add(__('Tipo de dieta', 'area-familias'), $tipo);
            $add(__('Instrucciones especiales', 'area-familias'), $instr);
        }
        if ($pide_dieta || in_array($k, array('salida', 'retraso'), true)) {
            $add(__('Motivo', 'area-familias'), $txt('motivo', 500));
        }
        $add(__('Observaciones', 'area-familias'), $txt('observaciones', 1000));
        if ($s['precio'] !== '') {
            $add(__('Precio', 'area-familias'), $s['precio']);
        }

        return array(
            'alumnos'         => $ids,
            'servicio'        => $k,
            'servicio_nombre' => $s['nombre'],
            'fecha_inicio'    => $ini,
            'fecha_fin'       => $fin,
            'dias'            => $dias,
            'hora'            => $hora,
            'detalles'        => $det,
            'avisar'          => array_values(array_filter(array_map('trim', preg_split('/[,;\s]+/', (string) $s['avisar'])), 'is_email')),
        );
    }

    public function handle_reservar() {
        if (!is_user_logged_in()) {
            wp_safe_redirect(AF_Acceso::get_login_url());
            exit;
        }
        check_admin_referer('af_reservar');
        $user = wp_get_current_user();
        $post = wp_unslash($_POST);
        unset($post['_wpnonce'], $post['_wp_http_referer']);

        $familia = AF_Panel::get_familia($user->user_email);
        if (is_wp_error($familia) || empty($familia['hijos'])) {
            self::back('error', is_wp_error($familia) ? $familia->get_error_message() : __('No encontramos alumnos asociados a tu cuenta.', 'area-familias'), $post);
        }
        $datos = self::validar($post, $familia['hijos']);
        if (is_wp_error($datos)) {
            self::back('error', $datos->get_error_message(), $post);
        }
        $datos['email'] = $user->user_email;
        $datos['panel_url'] = self::panel_url();

        $r = AF_Panel::api('POST', '/familia/reservas', $datos);
        if (is_wp_error($r)) {
            self::back('error', $r->get_error_message(), $post);
        }
        set_transient(self::cache_key($user->user_email), isset($r['reservas']) ? $r['reservas'] : array(), 5 * MINUTE_IN_SECONDS);
        self::back('ok', sprintf(__('¡Reserva hecha! %s Hemos avisado a su tutor/a y a secretaría, y te hemos enviado un correo con los datos.', 'area-familias'), $datos['servicio_nombre'] . ':' . ' ' . self::resumen_fechas($datos) . '.'));
    }

    private static function resumen_fechas($d) {
        if ($d['fecha_inicio'] === $d['fecha_fin']) {
            return date_i18n('l j \d\e F', strtotime($d['fecha_inicio'] . ' 12:00:00'));
        }
        return sprintf(__('del %1$s al %2$s', 'area-familias'), date_i18n('j \d\e F', strtotime($d['fecha_inicio'] . ' 12:00:00')), date_i18n('j \d\e F', strtotime($d['fecha_fin'] . ' 12:00:00')));
    }

    public function handle_anular() {
        if (!is_user_logged_in()) {
            wp_safe_redirect(AF_Acceso::get_login_url());
            exit;
        }
        $id = isset($_POST['id']) ? absint($_POST['id']) : 0;
        check_admin_referer('af_anular_reserva_' . $id);
        $user = wp_get_current_user();

        delete_transient(self::cache_key($user->user_email));
        $lista = self::get_reservas($user->user_email);
        $res = null;
        foreach (is_wp_error($lista) ? array() : $lista as $r) {
            if ((int) $r['id'] === $id) {
                $res = $r;
            }
        }
        if (!$res) {
            self::back('error', __('No encontramos esa reserva.', 'area-familias'));
        }
        if (!self::anulable($res)) {
            self::back('error', __('Ya no se puede anular esta reserva desde aquí porque ha pasado el plazo. Si lo necesitas, llama o escribe a secretaría.', 'area-familias'));
        }
        $r = AF_Panel::api('POST', '/familia/reservas/anular', array('email' => $user->user_email, 'id' => $id, 'panel_url' => self::panel_url()));
        if (is_wp_error($r)) {
            self::back('error', $r->get_error_message());
        }
        set_transient(self::cache_key($user->user_email), isset($r['reservas']) ? $r['reservas'] : array(), 5 * MINUTE_IN_SECONDS);
        self::back('ok', sprintf(__('Reserva anulada: %1$s de %2$s. Hemos avisado al colegio y te hemos enviado un correo.', 'area-familias'), $res['servicio_nombre'], $res['alumno']));
    }

    /* ---------------------------------------------------------------------
     * Panel: sección "Reservar un servicio"
     * ------------------------------------------------------------------- */

    /**
     * @param array $hijos children of the family (API)
     * @return array { html, proximas }
     */
    public static function render_seccion($hijos, $user) {
        $servicios = self::activos();
        $gen = self::generales();
        $reservas = $servicios ? self::get_reservas($user->user_email) : array();
        $error_reservas = is_wp_error($reservas) ? $reservas->get_error_message() : '';
        $reservas = is_wp_error($reservas) ? array() : $reservas;
        $hoy = current_time('Y-m-d');
        $proximas = array_values(array_filter($reservas, function ($r) use ($hoy) {
            return $r['estado'] === 'activa' && $r['fecha_fin'] >= $hoy;
        }));
        $pasadas = array_values(array_filter($reservas, function ($r) use ($hoy) {
            return !($r['estado'] === 'activa' && $r['fecha_fin'] >= $hoy);
        }));
        $old = get_transient('af_res_old_' . $user->ID);
        if ($old) {
            delete_transient('af_res_old_' . $user->ID);
        }
        $old = is_array($old) ? $old : array();
        $festivos = array_keys(self::festivos());
        $max_fecha = self::sumar($hoy, max(1, (int) $gen['max_antelacion']));

        ob_start();
        include AREA_FAMILIAS_DIR . 'templates/reservar.php';
        $html = ob_get_clean();

        if ($servicios && $hijos) {
            add_action('wp_footer', function () use ($festivos) {
                include AREA_FAMILIAS_DIR . 'templates/reservar-js.php';
            }, 50);
        }
        return array('html' => $html, 'proximas' => count($proximas));
    }

    /* ---------------------------------------------------------------------
     * Backend: Área Familias › Reservas
     * ------------------------------------------------------------------- */

    public function register_menu() {
        add_submenu_page('area-familias', __('Reservas de servicios', 'area-familias'), __('Reservas de servicios', 'area-familias'), 'manage_options', 'area-familias-reservas', array($this, 'render_admin'));
    }

    public function render_admin() {
        if (!current_user_can('manage_options')) {
            return;
        }
        $servicios = self::servicios();
        $gen = self::generales();
        $flash = AF_Ajustes::get_flash();
        $etapas = self::etapas();
        include AREA_FAMILIAS_DIR . 'templates/admin-reservas.php';
    }

    private static function hora_post($v) {
        $v = substr(sanitize_text_field((string) $v), 0, 5);
        return preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $v) ? $v : '';
    }

    public function handle_save() {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('No tienes permisos para realizar esta acción.', 'area-familias'));
        }
        check_admin_referer('af_save_reservas');
        $p = wp_unslash($_POST);
        $o = self::raw();

        if (!empty($p['restablecer']) && isset(self::definiciones()[$p['restablecer']])) {
            unset($o['servicios'][$p['restablecer']]);
            update_option(self::OPTION, $o, false);
            self::sincronizar(true);
            AF_Ajustes::flash(__('Servicio restablecido a sus valores iniciales.', 'area-familias'));
            wp_safe_redirect(admin_url('admin.php?page=area-familias-reservas#servicio-' . $p['restablecer']));
            exit;
        }

        $g = isset($p['generales']) && is_array($p['generales']) ? $p['generales'] : array();
        $o['generales'] = array(
            'intro'          => isset($g['intro']) ? sanitize_textarea_field($g['intro']) : '',
            'festivos'       => isset($g['festivos']) ? sanitize_textarea_field($g['festivos']) : '',
            'max_antelacion' => isset($g['max_antelacion']) ? max(1, min(366, absint($g['max_antelacion']))) : 90,
        );

        $errores = array();
        $in = isset($p['servicios']) && is_array($p['servicios']) ? $p['servicios'] : array();
        foreach (self::definiciones() as $k => $d) {
            $v = isset($in[$k]) && is_array($in[$k]) ? $in[$k] : array();
            $s = array(
                'activo'      => !empty($v['activo']) ? 'yes' : 'no',
                'nombre'      => isset($v['nombre']) && trim($v['nombre']) !== '' ? sanitize_text_field($v['nombre']) : $d['nombre'],
                'descripcion' => isset($v['descripcion']) ? sanitize_textarea_field($v['descripcion']) : '',
                'hora_inicio' => isset($v['hora_inicio']) ? self::hora_post($v['hora_inicio']) : '',
                'hora_fin'    => isset($v['hora_fin']) ? self::hora_post($v['hora_fin']) : '',
                'precio'      => isset($v['precio']) ? sanitize_text_field($v['precio']) : '',
                'plazo_dias'  => isset($v['plazo_dias']) ? min(30, absint($v['plazo_dias'])) : 0,
                'plazo_hora'  => isset($v['plazo_hora']) ? self::hora_post($v['plazo_hora']) : '',
                'varios_dias' => !empty($v['varios_dias']) ? 'yes' : 'no',
                'etapas'      => array_values(array_intersect(isset($v['etapas']) ? (array) $v['etapas'] : array(), array_keys(self::etapas()))),
                'avisar'      => isset($v['avisar']) ? implode(', ', array_filter(array_map('trim', preg_split('/[,;\s]+/', $v['avisar'])), 'is_email')) : '',
                'nota'        => isset($v['nota']) ? sanitize_textarea_field($v['nota']) : '',
            );
            if (count($s['etapas']) === count(self::etapas())) {
                $s['etapas'] = array();
            }
            foreach (array('desayuno', 'dieta', 'recoge') as $opt) {
                if (isset($d[$opt])) {
                    $s[$opt] = !empty($v[$opt]) ? 'yes' : 'no';
                }
            }
            if (isset($d['tipos_dieta'])) {
                $s['tipos_dieta'] = isset($v['tipos_dieta']) ? sanitize_textarea_field($v['tipos_dieta']) : '';
            }
            if ($s['hora_inicio'] !== '' && $s['hora_fin'] !== '' && $s['hora_fin'] < $s['hora_inicio']) {
                $errores[] = sprintf(__('%s: la hora de fin es anterior a la de inicio.', 'area-familias'), $s['nombre']);
            }
            $o['servicios'][$k] = $s;
        }
        update_option(self::OPTION, $o, false);
        $sync = self::sincronizar(true);
        if (is_wp_error($sync)) {
            $errores[] = __('No se han podido enviar los servicios al Área de Profesores (Portería los usa para reservar):', 'area-familias') . ' ' . $sync->get_error_message();
        }
        AF_Ajustes::flash($errores ? __('Guardado, pero revisa: ', 'area-familias') . implode(' ', $errores) : __('Reservas guardadas.', 'area-familias'), $errores ? 'warning' : 'success');
        wp_safe_redirect(admin_url('admin.php?page=area-familias-reservas'));
        exit;
    }
}
