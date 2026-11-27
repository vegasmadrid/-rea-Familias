<?php
/**
 * Página pública "Nuevas familias": solicitud de matrícula.
 *
 * Variables: $s (ajustes generales), $m (ajustes de matrícula), $niveles, $cursos,
 * $pantalla (form | cerrada | codigo | ok), $old, $errores, $aviso, $token, $pend, $referencia
 */

if (!defined('ABSPATH')) {
    exit;
}

// Old value of a field by its name ("alumnos[0][nombre]").
$val = function ($name, $def = '') use ($old) {
    $parts = preg_split('/[\[\]]+/', $name, -1, PREG_SPLIT_NO_EMPTY);
    $v = $old;
    foreach ($parts as $p) {
        if (!is_array($v) || !isset($v[$p])) {
            return $def;
        }
        $v = $v[$p];
    }
    return is_scalar($v) ? (string) $v : $def;
};
$err = function ($name) use ($errores) {
    return isset($errores[$name]) ? $errores[$name] : '';
};
$id = function ($name) {
    return 'afm-' . trim(preg_replace('/[^a-z0-9]+/i', '-', $name), '-');
};
/**
 * Field. $o: type, req, opts (select/radio), attrs, help, class, def
 */
$campo = function ($name, $label, $o = array()) use ($val, $err, $id) {
    $o = array_merge(array('type' => 'text', 'req' => true, 'opts' => array(), 'attrs' => '', 'help' => '', 'class' => '', 'def' => ''), $o);
    $v = $val($name, $o['def']);
    $e = $err($name);
    $fid = $id($name);
    $req = $o['req'] ? ' required' : '';
    $inv = $e !== '' ? ' aria-invalid="true" aria-describedby="' . esc_attr($fid) . '-e"' : '';
    echo '<div class="afm-campo ' . esc_attr($o['class']) . ($e !== '' ? ' has-error' : '') . '">';
    if ($o['type'] === 'radio') {
        echo '<fieldset class="afm-radios"><legend>' . esc_html($label) . ($o['req'] ? ' <span class="afm-req" aria-hidden="true">*</span>' : '') . '</legend>';
        foreach ($o['opts'] as $k => $l) {
            echo '<label><input type="radio" name="' . esc_attr($name) . '" value="' . esc_attr($k) . '"' . checked($v, (string) $k, false) . $req . '> ' . esc_html($l) . '</label>';
        }
        echo '</fieldset>';
    } else {
        echo '<label for="' . esc_attr($fid) . '">' . esc_html($label) . ($o['req'] ? ' <span class="afm-req" aria-hidden="true">*</span>' : '') . '</label>';
        if ($o['type'] === 'select') {
            echo '<select id="' . esc_attr($fid) . '" name="' . esc_attr($name) . '"' . $req . $inv . ' ' . $o['attrs'] . '>';
            echo '<option value="">' . esc_html__('Elige…', 'area-familias') . '</option>';
            foreach ($o['opts'] as $k => $l) {
                if (is_array($l)) {
                    echo '<optgroup label="' . esc_attr($k) . '">';
                    foreach ($l as $k2 => $l2) {
                        echo '<option value="' . esc_attr($k2) . '"' . selected($v, (string) $k2, false) . '>' . esc_html($l2) . '</option>';
                    }
                    echo '</optgroup>';
                } else {
                    echo '<option value="' . esc_attr($k) . '"' . selected($v, (string) $k, false) . '>' . esc_html($l) . '</option>';
                }
            }
            echo '</select>';
        } elseif ($o['type'] === 'textarea') {
            echo '<textarea id="' . esc_attr($fid) . '" name="' . esc_attr($name) . '" rows="3"' . $req . $inv . ' ' . $o['attrs'] . '>' . esc_textarea($v) . '</textarea>';
        } else {
            echo '<input type="' . esc_attr($o['type']) . '" id="' . esc_attr($fid) . '" name="' . esc_attr($name) . '" value="' . esc_attr($v) . '"' . $req . $inv . ' ' . $o['attrs'] . '>';
        }
    }
    if ($o['help'] !== '') {
        echo '<small class="afm-help">' . esc_html($o['help']) . '</small>';
    }
    echo '<small class="afm-error" id="' . esc_attr($fid) . '-e" role="alert">' . esc_html($e) . '</small>';
    echo '</div>';
};

$niveles_sel = array();
foreach ($niveles as $k => $n) {
    $niveles_sel[$n['etapa']][$k] = $n['label'];
}
$niveles_json = array();
foreach ($niveles as $k => $n) {
    $niveles_json[$k] = array('label' => $n['label'], 'edad' => $n['edad']);
}
$roles = array('padre' => __('Padre', 'area-familias'), 'madre' => __('Madre', 'area-familias'), 'tutor' => __('Tutor legal', 'area-familias'), 'tutora' => __('Tutora legal', 'area-familias'));
$docs = array('DNI' => 'DNI', 'NIE' => 'NIE', 'PAS' => __('Pasaporte', 'area-familias'));

$n_alumnos = max(1, isset($old['alumnos']) && is_array($old['alumnos']) ? count($old['alumnos']) : 1);
$n_hermanos = max(1, isset($old['hermanos']) && is_array($old['hermanos']) ? count($old['hermanos']) : 1);

$bloque_alumno = function ($i) use ($campo, $niveles_sel) {
    $p = 'alumnos[' . $i . ']';
    ?>
    <div class="afm-repetible afm-alumno" data-i="<?php echo esc_attr($i); ?>">
        <div class="afm-repetible-cab">
            <h3><?php esc_html_e('Alumno/a', 'area-familias'); ?> <span class="afm-num"><?php echo is_numeric($i) ? (int) $i + 1 : ''; ?></span></h3>
            <button type="button" class="afm-quitar" data-quitar><?php esc_html_e('Quitar', 'area-familias'); ?></button>
        </div>
        <div class="afm-grid">
            <?php
            $campo($p . '[nombre]', __('Nombre', 'area-familias'), array('attrs' => 'autocomplete="off"'));
            $campo($p . '[apellido1]', __('Primer apellido', 'area-familias'), array('attrs' => 'autocomplete="off"'));
            $campo($p . '[apellido2]', __('Segundo apellido', 'area-familias'), array('req' => false, 'attrs' => 'autocomplete="off"'));
            $campo($p . '[sexo]', __('Sexo', 'area-familias'), array('type' => 'radio', 'opts' => array('H' => __('Hombre', 'area-familias'), 'M' => __('Mujer', 'area-familias'))));
            $campo($p . '[fecha_nacimiento]', __('Fecha de nacimiento', 'area-familias'), array('type' => 'date', 'attrs' => 'data-nacimiento'));
            $campo($p . '[nivel]', __('Curso que solicitáis', 'area-familias'), array('type' => 'select', 'opts' => $niveles_sel, 'attrs' => 'data-nivel', 'help' => ' '));
            $campo($p . '[localidad_nacimiento]', __('Localidad de nacimiento', 'area-familias'));
            $campo($p . '[provincia_nacimiento]', __('Provincia de nacimiento', 'area-familias'), array('help' => __('Si nació fuera de España, escribe el país.', 'area-familias')));
            $campo($p . '[nacionalidad]', __('Nacionalidad', 'area-familias'), array('def' => 'Española'));
            $campo($p . '[centro_procedencia]', __('Centro educativo de procedencia', 'area-familias'), array('req' => false, 'help' => __('Colegio o escuela infantil al que va ahora (si va a alguno).', 'area-familias')));
            $campo($p . '[alergias_alimentarias]', __('Alergias o intolerancias alimentarias', 'area-familias'), array('req' => false, 'help' => __('Déjalo en blanco si no tiene. Importante para el comedor.', 'area-familias')));
            $campo($p . '[alergias_otras]', __('Otras alergias o información médica importante', 'area-familias'), array('req' => false));
            ?>
        </div>
    </div>
    <?php
};
$bloque_tutor = function ($i) use ($campo, $roles, $docs) {
    $p = 'tutores[' . $i . ']';
    ?>
    <div class="afm-repetible afm-tutor" data-tutor="<?php echo (int) $i; ?>">
        <h3><?php echo $i === 0 ? esc_html__('Padre, madre o tutor/a 1', 'area-familias') : esc_html__('Padre, madre o tutor/a 2', 'area-familias'); ?></h3>
        <div class="afm-grid">
            <?php
            $campo($p . '[rol]', __('Es el/la…', 'area-familias'), array('type' => 'select', 'opts' => $roles, 'def' => $i === 0 ? 'padre' : 'madre'));
            $campo($p . '[nombre]', __('Nombre', 'area-familias'), array('attrs' => 'autocomplete="given-name"'));
            $campo($p . '[apellidos]', __('Apellidos', 'area-familias'), array('attrs' => 'autocomplete="family-name"'));
            $campo($p . '[fecha_nacimiento]', __('Fecha de nacimiento', 'area-familias'), array('type' => 'date'));
            $campo($p . '[localidad_nacimiento]', __('Localidad de nacimiento', 'area-familias'));
            $campo($p . '[nacionalidad]', __('Nacionalidad', 'area-familias'), array('def' => 'Española'));
            $campo($p . '[documento_tipo]', __('Documento', 'area-familias'), array('type' => 'select', 'opts' => $docs, 'def' => 'DNI', 'attrs' => 'data-doc-tipo'));
            $campo($p . '[documento]', __('Número del documento', 'area-familias'), array('attrs' => 'data-doc autocomplete="off" spellcheck="false" placeholder="12345678Z"'));
            $campo($p . '[estudios]', __('Estudios realizados', 'area-familias'), array('req' => false));
            $campo($p . '[profesion]', __('Profesión actual', 'area-familias'), array('req' => false));
            $campo($p . '[movil]', __('Teléfono móvil', 'area-familias'), array('type' => 'tel', 'attrs' => 'autocomplete="tel" inputmode="tel"'));
            $campo($p . '[email]', __('Correo electrónico personal', 'area-familias'), array('type' => 'email', 'req' => false, 'attrs' => 'autocomplete="email" data-email-tutor'));
            ?>
        </div>
    </div>
    <?php
};
$bloque_hermano = function ($i) use ($campo) {
    $p = 'hermanos[' . $i . ']';
    ?>
    <div class="afm-repetible afm-hermano afm-fila" data-i="<?php echo esc_attr($i); ?>">
        <?php
        $campo($p . '[nombre]', __('Nombre y apellidos', 'area-familias'), array('req' => false));
        $campo($p . '[curso]', __('Curso actual', 'area-familias'), array('req' => false, 'attrs' => 'placeholder="' . esc_attr__('p. ej. 3º de Primaria A', 'area-familias') . '"'));
        ?>
        <button type="button" class="afm-quitar" data-quitar><?php esc_html_e('Quitar', 'area-familias'); ?></button>
    </div>
    <?php
};
?>
<div class="afm" id="afm">
    <div class="afm-bleed afm-header">
        <?php if (!empty($s['logo_url'])) : ?>
            <a href="<?php echo esc_url(AF_Acceso::get_login_url()); ?>"><img class="afm-logo" src="<?php echo esc_url($s['logo_url']); ?>" alt="<?php esc_attr_e('Colegio Casa de la Virgen', 'area-familias'); ?>"></a>
        <?php endif; ?>
    </div>
    <?php if (!empty($s['tribanda_url'])) : ?>
        <div class="afm-bleed afm-tribanda" aria-hidden="true"><img src="<?php echo esc_url($s['tribanda_url']); ?>" alt=""></div>
    <?php endif; ?>

    <div class="afm-card">
        <p class="afm-kicker"><?php esc_html_e('Colegio Casa de la Virgen', 'area-familias'); ?></p>
        <h1 class="afm-title"><?php esc_html_e('Solicitud de matrícula', 'area-familias'); ?></h1>

<?php if ($pantalla === 'cerrada') : ?>
        <p class="afm-text"><?php echo esc_html($m['cerrado']); ?></p>
        <a class="afm-link" href="<?php echo esc_url(AF_Acceso::get_login_url()); ?>">&larr; <?php esc_html_e('Volver al Área de Familias', 'area-familias'); ?></a>

<?php elseif ($pantalla === 'ok') : ?>
        <div class="afm-ok" role="status" data-borrar-borrador>
            <div class="afm-ok-icono" aria-hidden="true">✓</div>
            <h2><?php esc_html_e('¡Solicitud enviada!', 'area-familias'); ?></h2>
            <?php if ($referencia !== '' && $referencia !== '1') : ?>
                <p><?php printf(esc_html__('Vuestra referencia es %s.', 'area-familias'), '<strong>' . esc_html($referencia) . '</strong>'); ?></p>
            <?php endif; ?>
            <p><?php esc_html_e('Os hemos enviado un correo de confirmación. Secretaría revisará la solicitud y os escribirá para que vengáis al colegio a:', 'area-familias'); ?></p>
            <ul>
                <li><?php esc_html_e('Firmar la ficha de matrícula y la orden de domiciliación (las imprimimos nosotros con vuestros datos). Tienen que firmar los dos padres o tutores legales.', 'area-familias'); ?></li>
                <li><?php esc_html_e('Traer los originales del libro de familia (o partida de nacimiento) y del DNI, NIE o pasaporte de los padres o tutores.', 'area-familias'); ?></li>
                <li><?php esc_html_e('Recoger la cuenta del colegio para entrar en el Área de Familias.', 'area-familias'); ?></li>
            </ul>
        </div>

<?php elseif ($pantalla === 'codigo') : ?>
        <div class="afm-codigo">
            <h2><?php esc_html_e('Confirmad vuestro correo', 'area-familias'); ?></h2>
            <p><?php printf(esc_html__('Os hemos enviado un código de 6 números a %s. Escribidlo aquí para terminar de enviar la solicitud.', 'area-familias'), '<strong>' . esc_html($pend['datos']['email_contacto']) . '</strong>'); ?></p>
            <?php
            $msgs = array(
                'codigo'    => __('Ese código no es correcto. Revisadlo e intentadlo de nuevo.', 'area-familias'),
                'intentos'  => __('Demasiados intentos. Pulsad «Enviar otro código».', 'area-familias'),
                'api'       => __('No hemos podido entregar la solicitud al colegio en este momento. Esperad unos minutos y volved a pulsar «Confirmar y enviar» (no hace falta rellenar nada más).', 'area-familias'),
                'reenviado' => __('Os hemos enviado un código nuevo.', 'area-familias'),
                'envios'    => __('Ya os hemos enviado varios códigos. Revisad la carpeta de correo no deseado o llamad a Secretaría.', 'area-familias'),
                'caducada'  => __('La página ha caducado. Volved a escribir el código.', 'area-familias'),
            );
            if (isset($msgs[$aviso])) : ?>
                <p class="<?php echo $aviso === 'reenviado' ? 'afm-info' : 'afm-error-box'; ?>" role="alert"><?php echo esc_html($msgs[$aviso]); ?></p>
            <?php endif; ?>
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <?php wp_nonce_field('af_matricula_codigo'); ?>
                <input type="hidden" name="action" value="af_matricula_codigo">
                <input type="hidden" name="token" value="<?php echo esc_attr($token); ?>">
                <label for="afm-codigo-input" class="screen-reader-text"><?php esc_html_e('Código', 'area-familias'); ?></label>
                <input id="afm-codigo-input" class="afm-codigo-input" type="text" name="codigo" inputmode="numeric" autocomplete="one-time-code" pattern="\d{6}" maxlength="6" required placeholder="000000">
                <button type="submit" class="afm-btn"><?php esc_html_e('Confirmar y enviar', 'area-familias'); ?></button>
            </form>
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="afm-reenviar">
                <?php wp_nonce_field('af_matricula_codigo'); ?>
                <input type="hidden" name="action" value="af_matricula_reenviar">
                <input type="hidden" name="token" value="<?php echo esc_attr($token); ?>">
                <button type="submit" class="afm-link-btn"><?php esc_html_e('No me ha llegado: enviar otro código', 'area-familias'); ?></button>
            </form>
            <p class="afm-help"><?php esc_html_e('Revisad también la carpeta de correo no deseado. El código caduca en una hora.', 'area-familias'); ?></p>
        </div>

<?php else : ?>
        <?php if ($aviso === 'caducado') : ?>
            <p class="afm-error-box" role="alert"><?php esc_html_e('El código ha caducado o ya se ha usado. Si no os ha salido el mensaje de «Solicitud enviada», volved a enviar el formulario.', 'area-familias'); ?></p>
        <?php endif; ?>
        <p class="afm-text"><?php echo esc_html($m['intro']); ?></p>
        <p class="afm-help"><?php esc_html_e('Los campos con * son obligatorios. Si cerráis la página, en este dispositivo se guarda lo que habéis escrito (salvo los números de documento y el IBAN).', 'area-familias'); ?></p>

        <?php if ($errores) : ?>
            <div class="afm-error-box" id="afm-errores" role="alert" tabindex="-1">
                <?php if (isset($errores['_'])) : ?>
                    <?php echo esc_html($errores['_']); ?>
                <?php else : ?>
                    <?php printf(esc_html(_n('Hay %d dato que revisar (marcado en rojo).', 'Hay %d datos que revisar (marcados en rojo).', count($errores), 'area-familias')), count($errores)); ?>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <ol class="afm-pasos" aria-label="<?php esc_attr_e('Pasos', 'area-familias'); ?>" hidden>
            <li data-ir="1"><?php esc_html_e('Alumnos', 'area-familias'); ?></li>
            <li data-ir="2"><?php esc_html_e('Padres o tutores', 'area-familias'); ?></li>
            <li data-ir="3"><?php esc_html_e('Domicilio', 'area-familias'); ?></li>
            <li data-ir="4"><?php esc_html_e('Pago', 'area-familias'); ?></li>
            <li data-ir="5"><?php esc_html_e('Información', 'area-familias'); ?></li>
            <li data-ir="6"><?php esc_html_e('Enviar', 'area-familias'); ?></li>
        </ol>

        <form class="afm-form" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" novalidate
              data-niveles="<?php echo esc_attr(wp_json_encode($niveles_json)); ?>">
            <?php wp_nonce_field('af_matricula_enviar'); ?>
            <input type="hidden" name="action" value="af_matricula_enviar">
            <div class="afm-hp" aria-hidden="true"><label>Web <input type="text" name="web" tabindex="-1" autocomplete="off"></label></div>

            <!-- 1. Alumnos -->
            <section class="afm-paso" data-paso="1">
                <h2><span>1</span> <?php esc_html_e('Alumnos', 'area-familias'); ?></h2>
                <?php if (count($cursos) > 1) : ?>
                    <?php $campo('curso_escolar', __('Curso escolar', 'area-familias'), array('type' => 'select', 'opts' => array_combine($cursos, $cursos), 'attrs' => 'data-curso-escolar')); ?>
                <?php else : ?>
                    <p class="afm-info"><?php printf(esc_html__('Matrícula para el curso %s.', 'area-familias'), '<strong>' . esc_html($cursos[0]) . '</strong>'); ?></p>
                    <input type="hidden" name="curso_escolar" value="<?php echo esc_attr($cursos[0]); ?>" data-curso-escolar>
                <?php endif; ?>
                <p class="afm-help"><?php esc_html_e('Si matriculáis a varios hermanos a la vez, añadidlos todos en esta misma solicitud.', 'area-familias'); ?></p>
                <div class="afm-lista" data-lista="alumnos">
                    <?php for ($i = 0; $i < $n_alumnos; $i++) { $bloque_alumno($i); } ?>
                </div>
                <template data-plantilla="alumnos"><?php $bloque_alumno('__i__'); ?></template>
                <button type="button" class="afm-btn-sec" data-anadir="alumnos">+ <?php esc_html_e('Añadir otro hijo/a', 'area-familias'); ?></button>
            </section>

            <!-- 2. Padres o tutores -->
            <section class="afm-paso" data-paso="2">
                <h2><span>2</span> <?php esc_html_e('Padres o tutores legales', 'area-familias'); ?></h2>
                <?php $bloque_tutor(0); ?>
                <label class="afm-check"><input type="checkbox" name="solo_un_tutor" value="1" data-solo-uno <?php checked($val('solo_un_tutor') === '1'); ?>> <?php esc_html_e('Solo hay un padre, madre o tutor legal', 'area-familias'); ?></label>
                <div data-tutor2>
                    <?php $bloque_tutor(1); ?>
                </div>
            </section>

            <!-- 3. Domicilio y familia -->
            <section class="afm-paso" data-paso="3">
                <h2><span>3</span> <?php esc_html_e('Residencia habitual y familia', 'area-familias'); ?></h2>
                <div class="afm-grid">
                    <?php
                    $campo('domicilio[titular]', __('Titular de la correspondencia', 'area-familias'), array('help' => __('A nombre de quién enviamos las cartas.', 'area-familias'), 'attrs' => 'data-titular'));
                    $campo('domicilio[calle]', __('Calle', 'area-familias'), array('class' => 'afm-ancho', 'attrs' => 'autocomplete="address-line1"'));
                    $campo('domicilio[numero]', __('Número', 'area-familias'));
                    $campo('domicilio[piso]', __('Piso', 'area-familias'), array('req' => false));
                    $campo('domicilio[puerta]', __('Puerta', 'area-familias'), array('req' => false));
                    $campo('domicilio[cp]', __('Código postal', 'area-familias'), array('attrs' => 'inputmode="numeric" maxlength="5" autocomplete="postal-code"'));
                    $campo('domicilio[localidad]', __('Localidad', 'area-familias'), array('def' => 'Madrid'));
                    $campo('domicilio[provincia]', __('Provincia', 'area-familias'), array('def' => 'Madrid'));
                    $campo('domicilio[telefono]', __('Teléfono fijo', 'area-familias'), array('type' => 'tel', 'req' => false));
                    $campo('convivientes', __('Número de personas que conviven en la casa', 'area-familias'), array('type' => 'number', 'attrs' => 'min="2" max="30" inputmode="numeric"', 'help' => __('Contando al alumno/a.', 'area-familias')));
                    ?>
                </div>
                <label class="afm-check" data-separados><input type="checkbox" name="separados" value="1" <?php checked($val('separados') === '1'); ?>> <?php esc_html_e('Los padres están separados y cada uno necesita su propia cuenta del colegio', 'area-familias'); ?></label>

                <h3><?php esc_html_e('Hermanos que ya estudian en el colegio', 'area-familias'); ?></h3>
                <p class="afm-help"><?php esc_html_e('Si no tiene hermanos en el colegio, dejadlo en blanco.', 'area-familias'); ?></p>
                <div class="afm-lista" data-lista="hermanos">
                    <?php for ($i = 0; $i < $n_hermanos; $i++) { $bloque_hermano($i); } ?>
                </div>
                <template data-plantilla="hermanos"><?php $bloque_hermano('__i__'); ?></template>
                <button type="button" class="afm-btn-sec" data-anadir="hermanos">+ <?php esc_html_e('Añadir otro hermano/a', 'area-familias'); ?></button>
            </section>

            <!-- 4. Pago -->
            <section class="afm-paso" data-paso="4">
                <h2><span>4</span> <?php esc_html_e('Datos económicos (norma SEPA)', 'area-familias'); ?></h2>
                <?php $campo('pago[modo]', __('¿Cómo vais a pagar los recibos?', 'area-familias'), array('type' => 'radio', 'opts' => array('domiciliacion' => __('Domiciliación bancaria', 'area-familias'), 'ventanilla' => __('Pago por ventanilla', 'area-familias')), 'def' => 'domiciliacion')); ?>
                <div data-sepa>
                    <p class="afm-help"><?php esc_html_e('Al firmar la orden de domiciliación, el pagador autoriza al Colegio Casa de la Virgen a enviar instrucciones a su entidad para adeudar su cuenta. Vale para todos los hijos de esta solicitud.', 'area-familias'); ?></p>
                    <p><button type="button" class="afm-btn-sec" data-copiar-pagador="0"><?php esc_html_e('Copiar los datos del tutor/a 1', 'area-familias'); ?></button>
                       <button type="button" class="afm-btn-sec" data-copiar-pagador="1"><?php esc_html_e('Copiar los datos del tutor/a 2', 'area-familias'); ?></button></p>
                    <div class="afm-grid">
                        <?php
                        $campo('pago[pagador][nombre]', __('Nombre y apellidos del pagador', 'area-familias'), array('class' => 'afm-ancho'));
                        $campo('pago[pagador][documento]', __('DNI/NIE del pagador', 'area-familias'), array('attrs' => 'data-nif autocomplete="off" spellcheck="false"'));
                        $campo('pago[pagador][telefono]', __('Teléfono', 'area-familias'), array('type' => 'tel'));
                        $campo('pago[pagador][direccion]', __('Dirección', 'area-familias'), array('class' => 'afm-ancho'));
                        $campo('pago[pagador][cp]', __('Código postal', 'area-familias'), array('attrs' => 'inputmode="numeric" maxlength="5"'));
                        $campo('pago[pagador][localidad]', __('Localidad', 'area-familias'));
                        $campo('pago[pagador][provincia]', __('Provincia', 'area-familias'));
                        $campo('pago[iban]', __('Cuenta corriente (IBAN)', 'area-familias'), array('class' => 'afm-ancho', 'attrs' => 'data-iban autocomplete="off" spellcheck="false" placeholder="ES00 0000 0000 0000 0000 0000"', 'help' => __('Los 24 caracteres, empezando por ES.', 'area-familias')));
                        ?>
                    </div>
                </div>
            </section>

            <!-- 5. Información del centro y protección de datos -->
            <section class="afm-paso" data-paso="5">
                <h2><span>5</span> <?php esc_html_e('Información y autorizaciones', 'area-familias'); ?></h2>
                <p class="afm-help"><?php esc_html_e('Leed con calma la información del centro y la de protección de datos. Lo que marquéis aquí se imprime en las hojas que firmaréis en Secretaría.', 'area-familias'); ?></p>

                <h3><?php esc_html_e('Información del centro a la familia', 'area-familias'); ?></h3>
                <div class="afm-texto" tabindex="0" role="region" aria-label="<?php esc_attr_e('Información del centro a la familia', 'area-familias'); ?>">
                    <?php echo AF_Matricula_Textos::html($m['info_centro']); // phpcs:ignore -- escaped inside ?>
                </div>

                <h3><?php esc_html_e('Manifestaciones de los padres/tutores', 'area-familias'); ?></h3>
                <?php if (!empty($m['ideario_url'])) : ?>
                    <p class="afm-help"><a href="<?php echo esc_url($m['ideario_url']); ?>" target="_blank" rel="noopener"><?php esc_html_e('Consultar el Proyecto Educativo y el Reglamento de Régimen Interior', 'area-familias'); ?></a></p>
                <?php endif; ?>
                <?php foreach (AF_Matricula_Textos::manifestaciones() as $mk => $mf) : $mn = 'manif[' . $mk . ']'; $me = $err($mn); ?>
                    <div class="afm-campo afm-manif<?php echo $me !== '' ? ' has-error' : ''; ?>">
                        <?php if ($mf['tipo'] === 'acepta') : ?>
                            <label class="afm-check"><input type="checkbox" name="<?php echo esc_attr($mn); ?>" value="1" required <?php checked($val($mn) === '1'); ?>>
                                <span><strong><?php echo esc_html($mf['n']); ?></strong> <?php echo AF_Matricula_Textos::html($mf['texto']); // phpcs:ignore ?> <span class="afm-req">*</span></span></label>
                        <?php elseif ($mf['tipo'] === 'eleccion') : ?>
                            <fieldset class="afm-radios">
                                <legend><strong><?php echo esc_html($mf['n']); ?></strong> <?php echo esc_html($mf['texto']); ?> <span class="afm-req">*</span></legend>
                                <label><input type="radio" name="<?php echo esc_attr($mn); ?>" value="si" required <?php checked($val($mn), 'si'); ?>> <?php echo esc_html($mf['si']); ?></label>
                                <label><input type="radio" name="<?php echo esc_attr($mn); ?>" value="no" required <?php checked($val($mn), 'no'); ?>> <?php echo esc_html($mf['no']); ?></label>
                            </fieldset>
                        <?php else : ?>
                            <label for="afm-<?php echo esc_attr($mk); ?>"><strong><?php echo esc_html($mf['n']); ?></strong> <?php echo esc_html($mf['texto']); ?> <span class="afm-opcional"><?php esc_html_e('(opcional)', 'area-familias'); ?></span></label>
                            <textarea id="afm-<?php echo esc_attr($mk); ?>" name="<?php echo esc_attr($mn); ?>" rows="3" maxlength="2000"><?php echo esc_textarea($val($mn)); ?></textarea>
                        <?php endif; ?>
                        <small class="afm-error" role="alert"><?php echo esc_html($me); ?></small>
                    </div>
                <?php endforeach; ?>

                <h3><?php esc_html_e('Protección de datos (Fundación Casa de la Virgen)', 'area-familias'); ?></h3>
                <?php $ap_cons = AF_Matricula_Textos::consentimientos(); ?>
                <div class="afm-texto afm-texto-rgpd" tabindex="0" role="region" aria-label="<?php esc_attr_e('Información sobre protección de datos', 'area-familias'); ?>">
                    <?php
                    echo AF_Matricula_Textos::html($m['proteccion'], function ($marca) use ($ap_cons, $val, $err) { // phpcs:ignore
                        if (!isset($ap_cons[$marca])) {
                            return '';
                        }
                        $c = $ap_cons[$marca]['campo'];
                        $e = $err($c);
                        return '<div class="afm-campo afm-consentimiento' . ($e !== '' ? ' has-error' : '') . '"><fieldset class="afm-radios"><legend>' . esc_html($ap_cons[$marca]['label']) . ' <span class="afm-req">*</span></legend>'
                            . '<label><input type="radio" name="' . esc_attr($c) . '" value="consiento" required' . checked($val($c), 'consiento', false) . '> ' . esc_html__('Consiento', 'area-familias') . '</label>'
                            . '<label><input type="radio" name="' . esc_attr($c) . '" value="no" required' . checked($val($c), 'no', false) . '> ' . esc_html__('No consiento', 'area-familias') . '</label>'
                            . '</fieldset><small class="afm-error" role="alert">' . esc_html($e) . '</small></div>';
                    });
                    ?>
                </div>
                <div class="afm-campo<?php echo $err('proteccion_leida') !== '' ? ' has-error' : ''; ?>">
                    <label class="afm-check"><input type="checkbox" name="proteccion_leida" value="1" required <?php checked($val('proteccion_leida') === '1'); ?>> <span><?php esc_html_e('He leído la información sobre protección de datos.', 'area-familias'); ?> <span class="afm-req">*</span></span></label>
                    <small class="afm-error" role="alert"><?php echo esc_html($err('proteccion_leida')); ?></small>
                </div>
            </section>

            <!-- 6. Enviar -->
            <section class="afm-paso" data-paso="6">
                <h2><span>6</span> <?php esc_html_e('Revisar y enviar', 'area-familias'); ?></h2>
                <div class="afm-resumen" data-resumen hidden></div>
                <?php
                $campo('email_contacto', __('Correo electrónico de contacto', 'area-familias'), array('type' => 'email', 'attrs' => 'autocomplete="email" data-email-contacto', 'help' => __('Aquí os enviaremos un código para confirmar la solicitud y los avisos de Secretaría.', 'area-familias')));
                $campo('observaciones', __('Observaciones para Secretaría (opcional)', 'area-familias'), array('type' => 'textarea', 'req' => false));
                ?>
                <p class="afm-help"><?php esc_html_e('Al pulsar «Enviar» os mandaremos un código a vuestro correo. La matrícula no queda formalizada hasta que firméis en Secretaría las hojas impresas con estos datos.', 'area-familias'); ?></p>
                <button type="submit" class="afm-btn"><?php esc_html_e('Enviar la solicitud', 'area-familias'); ?></button>
            </section>

            <div class="afm-nav" hidden>
                <button type="button" class="afm-btn-sec" data-anterior>&larr; <?php esc_html_e('Anterior', 'area-familias'); ?></button>
                <button type="button" class="afm-btn" data-siguiente><?php esc_html_e('Siguiente', 'area-familias'); ?> &rarr;</button>
            </div>
        </form>
        <a class="afm-link" href="<?php echo esc_url(AF_Acceso::get_login_url()); ?>">&larr; <?php esc_html_e('Volver al Área de Familias', 'area-familias'); ?></a>
<?php endif; ?>
    </div>
</div>
