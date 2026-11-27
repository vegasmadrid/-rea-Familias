<?php
/**
 * Nuevas familias: solicitud de matrícula online.
 *
 * - A public page (chosen in Área Familias › Nuevas familias) with the form of
 *   the three paper sheets: enrolment form, place reservation (Primer Ciclo) and
 *   SEPA mandate. The login page shows a button to it.
 * - Everything is validated here (DNI/NIE letter, IBAN check digits, dates…).
 * - Before sending, the family confirms its e-mail with a 6-digit code (avoids
 *   spam and typos in the address the school will use).
 * - Then the request is sent to the Área de Profesores (POST /matriculas), which
 *   stores it encrypted and notifies Secretaría, Dirección and Administración.
 *   Nothing is stored here once it has been sent.
 */

if (!defined('ABSPATH')) {
    exit;
}

class AF_Matricula {

    const OPTION = 'af_matricula';

    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('admin_menu', array($this, 'register_menu'), 20);
        add_action('admin_post_af_save_matricula', array($this, 'handle_save'));
        foreach (array('enviar', 'codigo', 'reenviar') as $a) {
            add_action('admin_post_af_matricula_' . $a, array($this, 'handle_' . $a));
            add_action('admin_post_nopriv_af_matricula_' . $a, array($this, 'handle_' . $a));
        }
        add_filter('area_familias_is_public_request', array($this, 'es_publica'));
        add_action('wp_enqueue_scripts', array($this, 'assets'));
        add_action('template_redirect', array($this, 'sin_cache'), 0);
    }

    /* ---------------------------------------------------------------------
     * Ajustes
     * ------------------------------------------------------------------- */

    /** School year that starts next September ("2027/2028" in November 2026). */
    public static function proximo_curso() {
        $y = (int) current_time('Y');
        $y = (int) current_time('n') >= 9 ? $y + 1 : $y;
        return $y . '/' . ($y + 1);
    }

    public static function defaults() {
        return array(
            'page_id'          => 0,
            'abierta'          => 'no',
            'cursos_escolares' => self::proximo_curso(),
            'niveles'          => array_keys(self::niveles()),
            'boton_login'      => '¿Sois una familia nueva? Solicitad aquí la matrícula',
            'intro'            => 'Rellenad este formulario para solicitar la matrícula en el Colegio Casa de la Virgen. Son los mismos datos que las hojas en papel: cuando Secretaría lo revise, os avisará para venir a firmar la ficha impresa y traer la documentación original (libro de familia y DNI/NIE/pasaporte de los padres o tutores).',
            'cerrado'          => 'Ahora mismo no está abierto el plazo de matrícula online. Para cualquier duda, llamad a Secretaría: 914041786.',
            'ideario_url'      => '',
            'info_centro'      => AF_Matricula_Textos::info_centro(),
            'proteccion'       => AF_Matricula_Textos::proteccion(),
            'email_titulo'     => 'Código para confirmar la solicitud de matrícula',
        );
    }

    public static function all() {
        $s = get_option(self::OPTION, array());
        return array_merge(self::defaults(), is_array($s) ? $s : array());
    }

    public static function get($k) {
        $s = self::all();
        return isset($s[$k]) ? $s[$k] : '';
    }

    /** Levels (the keys are the same as in the Área de Profesores). */
    public static function niveles() {
        return array(
            'PC1'   => array('label' => '1 año', 'etapa' => 'Primer Ciclo de Infantil', 'edad' => 1),
            'PC2'   => array('label' => '2 años', 'etapa' => 'Primer Ciclo de Infantil', 'edad' => 2),
            'I3'    => array('label' => '3 años', 'etapa' => 'Infantil', 'edad' => 3),
            'I4'    => array('label' => '4 años', 'etapa' => 'Infantil', 'edad' => 4),
            'I5'    => array('label' => '5 años', 'etapa' => 'Infantil', 'edad' => 5),
            'EP1'   => array('label' => '1º de Primaria', 'etapa' => 'Primaria', 'edad' => 6),
            'EP2'   => array('label' => '2º de Primaria', 'etapa' => 'Primaria', 'edad' => 7),
            'EP3'   => array('label' => '3º de Primaria', 'etapa' => 'Primaria', 'edad' => 8),
            'EP4'   => array('label' => '4º de Primaria', 'etapa' => 'Primaria', 'edad' => 9),
            'EP5'   => array('label' => '5º de Primaria', 'etapa' => 'Primaria', 'edad' => 10),
            'EP6'   => array('label' => '6º de Primaria', 'etapa' => 'Primaria', 'edad' => 11),
            'ESO1'  => array('label' => '1º de ESO', 'etapa' => 'Secundaria (ESO)', 'edad' => 12),
            'ESO2'  => array('label' => '2º de ESO', 'etapa' => 'Secundaria (ESO)', 'edad' => 13),
            'ESO3'  => array('label' => '3º de ESO', 'etapa' => 'Secundaria (ESO)', 'edad' => 14),
            'ESO4'  => array('label' => '4º de ESO', 'etapa' => 'Secundaria (ESO)', 'edad' => 15),
            'BACH1' => array('label' => '1º de Bachillerato', 'etapa' => 'Bachillerato', 'edad' => 16),
            'BACH2' => array('label' => '2º de Bachillerato', 'etapa' => 'Bachillerato', 'edad' => 17),
        );
    }

    public static function niveles_abiertos() {
        $todos = self::niveles();
        $out = array();
        foreach ((array) self::get('niveles') as $k) {
            if (isset($todos[$k])) {
                $out[$k] = $todos[$k];
            }
        }
        return $out;
    }

    public static function cursos_escolares() {
        $out = array();
        foreach (preg_split('/[,;\n]+/', (string) self::get('cursos_escolares')) as $c) {
            $c = trim($c);
            if (preg_match('/^\d{4}\/\d{4}$/', $c)) {
                $out[] = $c;
            }
        }
        return $out ? $out : array(self::proximo_curso());
    }

    public static function page_id() {
        $id = absint(self::get('page_id'));
        return ($id && get_post_status($id) === 'publish') ? $id : 0;
    }

    public static function page_url($args = array()) {
        $id = self::page_id();
        return $id ? add_query_arg($args, get_permalink($id)) : '';
    }

    /** The button on the login page (only while it is open). */
    public static function url_boton_login() {
        return (self::get('abierta') === 'yes' && self::page_id()) ? self::page_url() : '';
    }

    public function es_publica($allow) {
        $id = self::page_id();
        return ($id && (int) get_queried_object_id() === $id) ? true : $allow;
    }

    public function sin_cache() {
        $id = self::page_id();
        if ($id && (int) get_queried_object_id() === $id) {
            nocache_headers();
            if (!defined('DONOTCACHEPAGE')) {
                define('DONOTCACHEPAGE', true);
            }
        }
    }

    public function assets() {
        $id = self::page_id();
        if (!$id || !is_page($id)) {
            return;
        }
        wp_enqueue_style('af-matricula', AREA_FAMILIAS_URL . 'assets/css/matricula.css', array(), AREA_FAMILIAS_VERSION);
        wp_enqueue_script('af-matricula', AREA_FAMILIAS_URL . 'assets/js/matricula.js', array(), AREA_FAMILIAS_VERSION, true);
    }

    /* ---------------------------------------------------------------------
     * Página
     * ------------------------------------------------------------------- */

    public static function render() {
        $s = AF_Ajustes::all();
        $m = self::all();
        $niveles = self::niveles_abiertos();
        $cursos = self::cursos_escolares();
        $abierta = $m['abierta'] === 'yes' && $niveles;
        $pantalla = 'form';
        $old = array();
        $errores = array();
        $aviso = '';
        $token = '';
        $pend = null;

        if (!empty($_GET['af_ok'])) {
            $pantalla = 'ok';
            $referencia = sanitize_text_field(wp_unslash($_GET['af_ok']));
        } elseif (!empty($_GET['af_mp'])) {
            $token = sanitize_key(wp_unslash($_GET['af_mp']));
            $pend = get_transient('af_mp_' . $token);
            if (is_array($pend)) {
                $pantalla = 'codigo';
                $aviso = isset($_GET['af_e']) ? sanitize_key(wp_unslash($_GET['af_e'])) : '';
            } else {
                $aviso = 'caducado';
            }
        } elseif (!empty($_GET['af_mt'])) {
            $t = get_transient('af_mt_' . sanitize_key(wp_unslash($_GET['af_mt'])));
            if (is_array($t)) {
                $old = $t['old'];
                $errores = $t['errores'];
            }
        }
        if ($pantalla === 'form' && !$abierta) {
            $pantalla = 'cerrada';
        }

        ob_start();
        include AREA_FAMILIAS_DIR . 'templates/matricula.php';
        return ob_get_clean();
    }

    /* ---------------------------------------------------------------------
     * Validación
     * ------------------------------------------------------------------- */

    public static function dni_valido($tipo, $doc) {
        $doc = strtoupper(preg_replace('/[\s\-.]/', '', (string) $doc));
        $letras = 'TRWAGMYFPDXBNJZSQVHLCKE';
        if ($tipo === 'DNI') {
            return (bool) preg_match('/^(\d{8})([A-Z])$/', $doc, $m) && $letras[(int) $m[1] % 23] === $m[2];
        }
        if ($tipo === 'NIE') {
            if (!preg_match('/^([XYZ])(\d{7})([A-Z])$/', $doc, $m)) {
                return false;
            }
            $n = (int) (strpos('XYZ', $m[1]) . $m[2]);
            return $letras[$n % 23] === $m[3];
        }
        return (bool) preg_match('/^[A-Z0-9]{5,20}$/', $doc);
    }

    /** Spanish NIF/NIE for the SEPA payer (DNI or NIE). */
    public static function nif_valido($doc) {
        $doc = strtoupper(preg_replace('/[\s\-.]/', '', (string) $doc));
        return self::dni_valido(preg_match('/^[XYZ]/', $doc) ? 'NIE' : 'DNI', $doc);
    }

    public static function iban_valido($iban) {
        $iban = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', (string) $iban));
        if (!preg_match('/^[A-Z]{2}\d{2}[A-Z0-9]{10,30}$/', $iban) || (substr($iban, 0, 2) === 'ES' && strlen($iban) !== 24)) {
            return false;
        }
        $r = substr($iban, 4) . substr($iban, 0, 4);
        $num = '';
        foreach (str_split($r) as $ch) {
            $num .= ctype_alpha($ch) ? (string) (ord($ch) - 55) : $ch;
        }
        $mod = 0;
        foreach (str_split($num, 7) as $part) {
            $mod = (int) (($mod . $part) % 97);
        }
        return $mod === 1;
    }

    private static function tel_valido($t) {
        $d = preg_replace('/\D/', '', (string) $t);
        return strlen($d) >= 9 && strlen($d) <= 15;
    }

    /**
     * Date between $max_anios years ago and $min_anios years ago. With $min_anios = 0
     * a date up to one year ahead is accepted (place reservation for a baby not born yet).
     */
    private static function fecha_valida($f, $min_anios, $max_anios) {
        if (!preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', (string) $f, $m) || !checkdate((int) $m[2], (int) $m[3], (int) $m[1])) {
            return false;
        }
        $hoy = current_time('Y-m-d') . ' 12:00:00';
        $t = strtotime($f . ' 12:00:00');
        $hasta = $min_anios === 0 ? strtotime($hoy . ' +1 year') : strtotime($hoy . ' -' . $min_anios . ' years');
        return $t <= $hasta && $t >= strtotime($hoy . ' -' . $max_anios . ' years');
    }

    /**
     * Validate the posted form.
     *
     * @return array [ datos (for the Área de Profesores), errores (field => message) ]
     */
    public static function validar($p) {
        $e = array();
        $txt = function ($arr, $k, $max = 150) {
            return isset($arr[$k]) && is_scalar($arr[$k]) ? mb_substr(trim(sanitize_text_field((string) $arr[$k])), 0, $max) : '';
        };
        $obl = __('Este dato es obligatorio.', 'area-familias');

        $cursos = self::cursos_escolares();
        $curso = $txt($p, 'curso_escolar', 20);
        if (!in_array($curso, $cursos, true)) {
            $curso = $cursos[0];
        }
        $niveles = self::niveles_abiertos();

        // Alumnos
        $alumnos = array();
        $lista = isset($p['alumnos']) && is_array($p['alumnos']) ? array_values($p['alumnos']) : array();
        foreach (array_slice($lista, 0, 6) as $i => $a) {
            if (!is_array($a)) {
                continue;
            }
            $al = array(
                'nombre'               => $txt($a, 'nombre', 80),
                'apellido1'            => $txt($a, 'apellido1', 80),
                'apellido2'            => $txt($a, 'apellido2', 80),
                'sexo'                 => $txt($a, 'sexo', 1),
                'fecha_nacimiento'     => $txt($a, 'fecha_nacimiento', 10),
                'localidad_nacimiento' => $txt($a, 'localidad_nacimiento', 100),
                'provincia_nacimiento' => $txt($a, 'provincia_nacimiento', 100),
                'nacionalidad'         => $txt($a, 'nacionalidad', 80),
                'centro_procedencia'   => $txt($a, 'centro_procedencia', 150),
                'nivel'                => $txt($a, 'nivel', 10),
                'alergias_alimentarias'=> $txt($a, 'alergias_alimentarias', 300),
                'alergias_otras'       => $txt($a, 'alergias_otras', 300),
            );
            if (implode('', $al) === '' ) {
                continue; // empty block
            }
            $k = 'alumnos[' . $i . ']';
            foreach (array('nombre', 'apellido1', 'localidad_nacimiento', 'provincia_nacimiento', 'nacionalidad') as $c) {
                if ($al[$c] === '') {
                    $e[$k . '[' . $c . ']'] = $obl;
                }
            }
            if (!in_array($al['sexo'], array('H', 'M'), true)) {
                $e[$k . '[sexo]'] = $obl;
            }
            if (!self::fecha_valida($al['fecha_nacimiento'], 0, 25)) {
                $e[$k . '[fecha_nacimiento]'] = __('Escribe una fecha de nacimiento válida.', 'area-familias');
            }
            if (!isset($niveles[$al['nivel']])) {
                $e[$k . '[nivel]'] = __('Elige el curso que solicitáis.', 'area-familias');
            }
            $alumnos[] = $al;
        }
        if (!$alumnos) {
            $e['alumnos[0][nombre]'] = __('Añadid al menos un alumno o alumna.', 'area-familias');
        }

        // Padres o tutores
        $tutores = array();
        $tl = isset($p['tutores']) && is_array($p['tutores']) ? $p['tutores'] : array();
        $solo_uno = !empty($p['solo_un_tutor']);
        foreach (array(0, 1) as $i) {
            if ($i === 1 && $solo_uno) {
                continue;
            }
            $t = isset($tl[$i]) && is_array($tl[$i]) ? $tl[$i] : array();
            $tu = array(
                'rol'                  => $txt($t, 'rol', 10),
                'nombre'               => $txt($t, 'nombre', 80),
                'apellidos'            => $txt($t, 'apellidos', 150),
                'fecha_nacimiento'     => $txt($t, 'fecha_nacimiento', 10),
                'localidad_nacimiento' => $txt($t, 'localidad_nacimiento', 100),
                'nacionalidad'         => $txt($t, 'nacionalidad', 80),
                'documento_tipo'       => $txt($t, 'documento_tipo', 3),
                'documento'            => strtoupper(preg_replace('/[\s\-.]/', '', $txt($t, 'documento', 30))),
                'estudios'             => $txt($t, 'estudios', 150),
                'profesion'            => $txt($t, 'profesion', 150),
                'movil'                => $txt($t, 'movil', 30),
                'email'                => strtolower($txt($t, 'email', 190)),
            );
            $k = 'tutores[' . $i . ']';
            if (!in_array($tu['rol'], array('padre', 'madre', 'tutor', 'tutora'), true)) {
                $e[$k . '[rol]'] = $obl;
            }
            foreach (array('nombre', 'apellidos', 'localidad_nacimiento', 'nacionalidad') as $c) {
                if ($tu[$c] === '') {
                    $e[$k . '[' . $c . ']'] = $obl;
                }
            }
            if (!self::fecha_valida($tu['fecha_nacimiento'], 14, 100)) {
                $e[$k . '[fecha_nacimiento]'] = __('Escribe una fecha de nacimiento válida.', 'area-familias');
            }
            if (!in_array($tu['documento_tipo'], array('DNI', 'NIE', 'PAS'), true)) {
                $e[$k . '[documento_tipo]'] = $obl;
            } elseif (!self::dni_valido($tu['documento_tipo'], $tu['documento'])) {
                $e[$k . '[documento]'] = $tu['documento_tipo'] === 'PAS'
                    ? __('Escribe el número de pasaporte (solo letras y números).', 'area-familias')
                    : sprintf(__('El %s no es correcto: revisa los números y la letra.', 'area-familias'), $tu['documento_tipo']);
            }
            if (!self::tel_valido($tu['movil'])) {
                $e[$k . '[movil]'] = __('Escribe un teléfono móvil válido.', 'area-familias');
            }
            if ($tu['email'] !== '' && !is_email($tu['email'])) {
                $e[$k . '[email]'] = __('El correo no es válido.', 'area-familias');
            }
            $tutores[] = $tu;
        }

        // Residencia
        $dp = isset($p['domicilio']) && is_array($p['domicilio']) ? $p['domicilio'] : array();
        $dom = array();
        foreach (array('titular' => 150, 'calle' => 200, 'numero' => 20, 'piso' => 20, 'puerta' => 20, 'cp' => 10, 'localidad' => 100, 'provincia' => 100, 'telefono' => 30) as $c => $max) {
            $dom[$c] = $txt($dp, $c, $max);
        }
        foreach (array('titular', 'calle', 'numero', 'localidad', 'provincia') as $c) {
            if ($dom[$c] === '') {
                $e['domicilio[' . $c . ']'] = $obl;
            }
        }
        if (!preg_match('/^\d{5}$/', $dom['cp'])) {
            $e['domicilio[cp]'] = __('El código postal tiene 5 números.', 'area-familias');
        }
        if ($dom['telefono'] !== '' && !self::tel_valido($dom['telefono'])) {
            $e['domicilio[telefono]'] = __('Escribe un teléfono válido.', 'area-familias');
        }
        $conv = isset($p['convivientes']) ? absint($p['convivientes']) : 0;
        if ($conv < 2 || $conv > 30) {
            $e['convivientes'] = __('Indica cuántas personas viven en la casa (contando al alumno/a).', 'area-familias');
        }

        $hermanos = array();
        foreach (array_slice(isset($p['hermanos']) && is_array($p['hermanos']) ? array_values($p['hermanos']) : array(), 0, 8) as $h) {
            if (is_array($h) && $txt($h, 'nombre') !== '') {
                $hermanos[] = array('nombre' => $txt($h, 'nombre'), 'curso' => $txt($h, 'curso', 60));
            }
        }

        // Pago
        $pg = isset($p['pago']) && is_array($p['pago']) ? $p['pago'] : array();
        $modo = $txt($pg, 'modo', 20);
        $pago = array('modo' => $modo === 'ventanilla' ? 'ventanilla' : 'domiciliacion', 'pagador' => array(), 'iban' => '');
        if (!in_array($modo, array('ventanilla', 'domiciliacion'), true)) {
            $e['pago[modo]'] = __('Elige cómo vais a pagar.', 'area-familias');
        } elseif ($modo === 'domiciliacion') {
            $pp = isset($pg['pagador']) && is_array($pg['pagador']) ? $pg['pagador'] : array();
            foreach (array('nombre' => 150, 'documento' => 30, 'telefono' => 30, 'direccion' => 200, 'cp' => 10, 'localidad' => 100, 'provincia' => 100) as $c => $max) {
                $pago['pagador'][$c] = $txt($pp, $c, $max);
            }
            $pago['pagador']['documento'] = strtoupper(preg_replace('/[\s\-.]/', '', $pago['pagador']['documento']));
            foreach (array('nombre', 'direccion', 'localidad', 'provincia') as $c) {
                if ($pago['pagador'][$c] === '') {
                    $e['pago[pagador][' . $c . ']'] = $obl;
                }
            }
            if (!self::nif_valido($pago['pagador']['documento'])) {
                $e['pago[pagador][documento]'] = __('Escribe un DNI o NIE válido.', 'area-familias');
            }
            if (!self::tel_valido($pago['pagador']['telefono'])) {
                $e['pago[pagador][telefono]'] = __('Escribe un teléfono válido.', 'area-familias');
            }
            if (!preg_match('/^\d{5}$/', $pago['pagador']['cp'])) {
                $e['pago[pagador][cp]'] = __('El código postal tiene 5 números.', 'area-familias');
            }
            $iban = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $txt($pg, 'iban', 40)));
            if (!self::iban_valido($iban)) {
                $e['pago[iban]'] = __('El IBAN no es correcto: revisa los 24 caracteres (empieza por ES).', 'area-familias');
            }
            $pago['iban'] = $iban;
        }

        // Contacto y aceptaciones
        $email = strtolower($txt($p, 'email_contacto', 190));
        if (!is_email($email)) {
            $e['email_contacto'] = __('Escribe un correo válido: os enviaremos un código para confirmarlo.', 'area-familias');
        }
        $ahora = current_time('d/m/Y H:i');

        // Manifestaciones de los padres/tutores (hoja "Información del centro a la familia").
        $mp = isset($p['manif']) && is_array($p['manif']) ? $p['manif'] : array();
        $manif = array();
        foreach (AF_Matricula_Textos::manifestaciones() as $k => $mf) {
            $campo = 'manif[' . $k . ']';
            if ($mf['tipo'] === 'acepta') {
                if (empty($mp[$k])) {
                    $e[$campo] = sprintf(__('Hay que aceptar la manifestación %s.', 'area-familias'), $mf['n']);
                }
                $manif[$k] = 'si';
            } elseif ($mf['tipo'] === 'eleccion') {
                $v = $txt($mp, $k, 2);
                if (!in_array($v, array('si', 'no'), true)) {
                    $e[$campo] = __('Elegid una de las dos opciones.', 'area-familias');
                }
                $manif[$k] = $v;
            } else {
                $manif[$k] = isset($mp[$k]) && is_scalar($mp[$k]) ? mb_substr(sanitize_textarea_field((string) $mp[$k]), 0, 2000) : '';
            }
        }

        // Protección de datos: read, and the consent boxes that the text has.
        $proteccion = (string) self::get('proteccion');
        if (empty($p['proteccion_leida'])) {
            $e['proteccion_leida'] = __('Hay que leer y aceptar la información sobre protección de datos.', 'area-familias');
        }
        $rgpd = array();
        $cons = AF_Matricula_Textos::consentimientos();
        foreach (AF_Matricula_Textos::marcas($proteccion) as $marca) {
            $c = $cons[$marca]['campo'];
            $v = $txt($p, $c, 10);
            if (!in_array($v, array('consiento', 'no'), true)) {
                $e[$c] = __('Marcad «Consiento» o «No consiento».', 'area-familias');
            }
            $rgpd[$c] = $v;
        }

        $datos = array(
            'curso_escolar' => $curso,
            'alumnos'       => $alumnos,
            'tutores'       => $tutores,
            'domicilio'     => $dom,
            'convivientes'  => $conv,
            'hermanos'      => $hermanos,
            'separados'     => !$solo_uno && !empty($p['separados']),
            'pago'          => $pago,
            'email_contacto'=> $email,
            'observaciones' => isset($p['observaciones']) && is_scalar($p['observaciones']) ? mb_substr(sanitize_textarea_field((string) $p['observaciones']), 0, 2000) : '',
            'aceptaciones'  => array(
                'ideario'         => $ahora,
                'privacidad'      => $ahora,
                'imagen'          => isset($manif['m4']) ? $manif['m4'] : '',
                'manifestaciones' => $manif,
                'rgpd'            => $rgpd,
            ),
            // The exact texts the family accepted (printed with its answers in Secretaría).
            'textos'        => array(
                'info_centro'     => (string) self::get('info_centro'),
                'manifestaciones' => AF_Matricula_Textos::manifestaciones(),
                'proteccion'      => $proteccion,
                'consentimientos' => $cons,
            ),
        );
        return array($datos, $e);
    }

    /* ---------------------------------------------------------------------
     * Envío
     * ------------------------------------------------------------------- */

    private static function ip() {
        return isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR'])) : '';
    }

    /** At most $max actions per IP and hour. */
    private static function limite($que, $max) {
        $k = 'af_mlim_' . $que . '_' . md5(self::ip());
        $n = (int) get_transient($k);
        if ($n >= $max) {
            return false;
        }
        set_transient($k, $n + 1, HOUR_IN_SECONDS);
        return true;
    }

    private static function volver_form($old, $errores) {
        $token = strtolower(wp_generate_password(20, false));
        // The answers are kept for 30 minutes so that the family only corrects the errors.
        set_transient('af_mt_' . $token, array('old' => $old, 'errores' => $errores), 30 * MINUTE_IN_SECONDS);
        wp_safe_redirect(self::page_url(array('af_mt' => $token)) . '#afm-errores');
        exit;
    }

    public function handle_enviar() {
        if (!self::page_id()) {
            wp_safe_redirect(home_url('/'));
            exit;
        }
        $post = wp_unslash($_POST);
        unset($post['_wpnonce'], $post['_wp_http_referer'], $post['action']);
        // Blocks removed in the browser leave gaps (0, 2…): renumber them.
        foreach (array('alumnos', 'hermanos') as $l) {
            $post[$l] = isset($post[$l]) && is_array($post[$l]) ? array_values(array_slice($post[$l], 0, 8)) : array();
        }
        if (!wp_verify_nonce(isset($_POST['_wpnonce']) ? sanitize_text_field(wp_unslash($_POST['_wpnonce'])) : '', 'af_matricula_enviar')) {
            self::volver_form($post, array('_' => __('La página ha caducado. Revisa los datos y vuelve a pulsar «Enviar».', 'area-familias')));
        }
        // Honeypot: robots fill every field.
        if (!empty($post['web'])) {
            wp_safe_redirect(self::page_url());
            exit;
        }
        if (self::get('abierta') !== 'yes') {
            wp_safe_redirect(self::page_url());
            exit;
        }
        list($datos, $errores) = self::validar($post);
        if ($errores) {
            self::volver_form($post, $errores);
        }
        if (!self::limite('enviar', 8)) {
            self::volver_form($post, array('_' => __('Se han enviado demasiadas solicitudes desde esta conexión. Espera una hora o llama a Secretaría.', 'area-familias')));
        }

        $token = strtolower(wp_generate_password(24, false));
        $codigo = (string) random_int(100000, 999999);
        $datos['origen'] = array(
            'ip'        => self::ip(),
            'navegador' => isset($_SERVER['HTTP_USER_AGENT']) ? mb_substr(sanitize_text_field(wp_unslash($_SERVER['HTTP_USER_AGENT'])), 0, 255) : '',
        );
        set_transient('af_mp_' . $token, array(
            'datos'    => $datos,
            'codigo'   => wp_hash_password($codigo),
            'intentos' => 0,
            'envios'   => 1,
        ), HOUR_IN_SECONDS);

        if (!self::enviar_codigo($datos['email_contacto'], $codigo, $datos)) {
            delete_transient('af_mp_' . $token);
            self::volver_form($post, array('email_contacto' => __('No hemos podido enviar el correo con el código. Revisa la dirección o inténtalo más tarde.', 'area-familias')));
        }
        wp_safe_redirect(self::page_url(array('af_mp' => $token)) . '#afm');
        exit;
    }

    private static function enviar_codigo($email, $codigo, $datos) {
        $s = AF_Ajustes::all();
        $nombres = implode(', ', array_map(function ($a) {
            return trim($a['nombre'] . ' ' . $a['apellido1']);
        }, $datos['alumnos']));
        $logo = !empty($s['logo_url']) ? '<img src="' . esc_url($s['logo_url']) . '" alt="Colegio Casa de la Virgen" style="max-height:60px;margin-bottom:16px;">' : '';
        $html = '<div style="font-family:Arial,sans-serif;max-width:520px;margin:0 auto;color:#1f2240;">' . $logo
            . '<h2 style="color:#313280;">' . esc_html__('Confirmad vuestro correo', 'area-familias') . '</h2>'
            . '<p>' . sprintf(esc_html__('Estáis enviando la solicitud de matrícula de %s en el Colegio Casa de la Virgen. Escribid este código en la página para terminar:', 'area-familias'), '<strong>' . esc_html($nombres) . '</strong>') . '</p>'
            . '<p style="font-size:32px;font-weight:700;letter-spacing:8px;color:#313280;background:#eef3fe;padding:16px;text-align:center;border-radius:12px;">' . esc_html($codigo) . '</p>'
            . '<p style="color:#6b7091;font-size:13px;">' . esc_html__('El código caduca en una hora. Si no habéis pedido nada, ignorad este correo.', 'area-familias') . '</p></div>';
        $from = function () {
            return __('Área de Familias', 'area-familias');
        };
        add_filter('wp_mail_from_name', $from, 99);
        $ok = wp_mail($email, (string) self::get('email_titulo'), $html, array('Content-Type: text/html; charset=UTF-8'));
        remove_filter('wp_mail_from_name', $from, 99);
        return $ok;
    }

    private static function volver_codigo($token, $err) {
        wp_safe_redirect(self::page_url(array('af_mp' => $token, 'af_e' => $err)) . '#afm');
        exit;
    }

    public function handle_codigo() {
        $token = isset($_POST['token']) ? sanitize_key(wp_unslash($_POST['token'])) : '';
        $pend = $token !== '' ? get_transient('af_mp_' . $token) : false;
        if (!is_array($pend)) {
            wp_safe_redirect(self::page_url(array('af_mp' => 'x')) . '#afm');
            exit;
        }
        if (!wp_verify_nonce(isset($_POST['_wpnonce']) ? sanitize_text_field(wp_unslash($_POST['_wpnonce'])) : '', 'af_matricula_codigo')) {
            self::volver_codigo($token, 'caducada');
        }
        $codigo = preg_replace('/\D/', '', isset($_POST['codigo']) ? (string) wp_unslash($_POST['codigo']) : '');
        if ($pend['intentos'] >= 5) {
            self::volver_codigo($token, 'intentos');
        }
        if (!wp_check_password($codigo, $pend['codigo'])) {
            $pend['intentos']++;
            set_transient('af_mp_' . $token, $pend, HOUR_IN_SECONDS);
            self::volver_codigo($token, $pend['intentos'] >= 5 ? 'intentos' : 'codigo');
        }

        $datos = $pend['datos'];
        $datos['origen']['verificado'] = current_time('d/m/Y H:i');
        // Login page of this website: printed (with a QR code) on the credentials sheet.
        $datos['familias_url'] = AF_Acceso::get_login_url();
        $r = AF_Panel::api('POST', '/matriculas', $datos);
        if (is_wp_error($r)) {
            error_log('[Área Familias] Matrícula: ' . $r->get_error_message());
            self::volver_codigo($token, 'api');
        }
        delete_transient('af_mp_' . $token);
        wp_safe_redirect(self::page_url(array('af_ok' => isset($r['referencia']) ? $r['referencia'] : '1')) . '#afm');
        exit;
    }

    public function handle_reenviar() {
        $token = isset($_POST['token']) ? sanitize_key(wp_unslash($_POST['token'])) : '';
        $pend = $token !== '' ? get_transient('af_mp_' . $token) : false;
        if (!is_array($pend) || !wp_verify_nonce(isset($_POST['_wpnonce']) ? sanitize_text_field(wp_unslash($_POST['_wpnonce'])) : '', 'af_matricula_codigo')) {
            wp_safe_redirect(self::page_url(array('af_mp' => 'x')) . '#afm');
            exit;
        }
        if ($pend['envios'] >= 3 || !self::limite('reenviar', 6)) {
            self::volver_codigo($token, 'envios');
        }
        $codigo = (string) random_int(100000, 999999);
        $pend['codigo'] = wp_hash_password($codigo);
        $pend['intentos'] = 0;
        $pend['envios']++;
        set_transient('af_mp_' . $token, $pend, HOUR_IN_SECONDS);
        self::enviar_codigo($pend['datos']['email_contacto'], $codigo, $pend['datos']);
        self::volver_codigo($token, 'reenviado');
    }

    /* ---------------------------------------------------------------------
     * Backend
     * ------------------------------------------------------------------- */

    public function register_menu() {
        add_submenu_page('area-familias', __('Nuevas familias', 'area-familias'), __('Nuevas familias', 'area-familias'), 'manage_options', 'area-familias-matricula', array($this, 'render_admin'));
    }

    public function render_admin() {
        if (!current_user_can('manage_options')) {
            return;
        }
        $m = self::all();
        $flash = AF_Ajustes::get_flash();
        $pages = get_pages(array('sort_column' => 'post_title'));
        $niveles = self::niveles();
        $conectada = (string) AF_Ajustes::get('api_key') !== '';
        include AREA_FAMILIAS_DIR . 'templates/admin-matricula.php';
    }

    public function handle_save() {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('No tienes permisos para realizar esta acción.', 'area-familias'));
        }
        check_admin_referer('af_save_matricula');
        $p = wp_unslash($_POST);
        $txt = function ($k) use ($p) {
            return isset($p[$k]) ? sanitize_text_field($p[$k]) : '';
        };
        $area = function ($k) use ($p) {
            return isset($p[$k]) ? sanitize_textarea_field($p[$k]) : '';
        };
        $cursos = array();
        foreach (preg_split('/[,;\n]+/', $txt('cursos_escolares')) as $c) {
            $c = str_replace('-', '/', trim($c));
            if (preg_match('/^\d{4}\/\d{4}$/', $c)) {
                $cursos[] = $c;
            }
        }
        $s = array(
            'page_id'          => isset($p['page_id']) ? absint($p['page_id']) : 0,
            'abierta'          => !empty($p['abierta']) ? 'yes' : 'no',
            'cursos_escolares' => $cursos ? implode(', ', $cursos) : self::proximo_curso(),
            'niveles'          => array(),
            'boton_login'      => $txt('boton_login'),
            'intro'            => $area('intro'),
            'cerrado'          => $area('cerrado'),
            'ideario_url'      => isset($p['ideario_url']) ? esc_url_raw($p['ideario_url']) : '',
            'info_centro'      => trim($area('info_centro')) !== '' ? $area('info_centro') : AF_Matricula_Textos::info_centro(),
            'proteccion'       => trim($area('proteccion')) !== '' ? $area('proteccion') : AF_Matricula_Textos::proteccion(),
            'email_titulo'     => $txt('email_titulo') !== '' ? $txt('email_titulo') : self::defaults()['email_titulo'],
        );
        $elegidos = isset($p['niveles']) ? array_map('strval', (array) $p['niveles']) : array();
        $s['niveles'] = array_values(array_intersect(array_keys(self::niveles()), $elegidos));
        update_option(self::OPTION, $s);

        // The page must be public (also with "Restringir toda la web").
        $msg = __('Ajustes guardados.', 'area-familias');
        if ($s['page_id'] && in_array($s['page_id'], array(absint(AF_Ajustes::get('login_page_id')), absint(AF_Ajustes::get('start_page_id'))), true)) {
            update_option(self::OPTION, array_merge($s, array('page_id' => 0)));
            AF_Ajustes::flash(__('Elige para el formulario una página distinta de la de login y de la de inicio.', 'area-familias'), 'error');
        } else {
            AF_Ajustes::flash($msg);
        }
        wp_safe_redirect(admin_url('admin.php?page=area-familias-matricula'));
        exit;
    }
}
