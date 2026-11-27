<?php
/**
 * Panel de la familia: "Reservar un servicio" + "Mis reservas".
 *
 * Variables: $hijos, $servicios (active), $gen, $proximas, $pasadas, $old,
 *            $festivos, $max_fecha, $hoy, $error_reservas
 */

if (!defined('ABSPATH')) {
    exit;
}

$afr_old = function ($k, $default = '') use ($old) {
    return isset($old[$k]) && !is_array($old[$k]) ? (string) $old[$k] : $default;
};
$afr_old_arr = function ($k) use ($old) {
    return isset($old[$k]) ? array_map('strval', (array) $old[$k]) : array();
};
$afr_sel_hijos = $afr_old_arr('alumnos');
if (!$afr_sel_hijos && count($hijos) === 1) {
    $afr_sel_hijos = array((string) $hijos[0]['id']);
}
$afr_serv_sel = $afr_old('servicio');
$afr_todos = AF_Reservas::servicios();
$afr_tipos_dieta = array_values(array_filter(array_map('trim', explode("\n", (string) $afr_todos['dieta']['tipos_dieta']))));
$afr_dias = array(1 => __('Lunes', 'area-familias'), 2 => __('Martes', 'area-familias'), 3 => __('Miércoles', 'area-familias'), 4 => __('Jueves', 'area-familias'), 5 => __('Viernes', 'area-familias'));
$afr_dias_sel = $afr_old_arr('dias') ? $afr_old_arr('dias') : array('1', '2', '3', '4', '5');
$afr_iconos = array();
foreach ($afr_todos as $k => $s) {
    $afr_iconos[$k] = $s['icono'];
}
?>
<style>
.afp .afp-res-form .afp-paso { border: 0; padding: 0; margin: 0 0 24px; min-width: 0; }
.afp .afp-res-form .afp-paso[hidden] { display: none; }
.afp .afp-res-form legend { display: flex; align-items: center; gap: 10px; font-size: 16px; font-weight: 800; color: var(--afp-navy); margin: 0 0 12px; padding: 0; }
.afp .afp-paso-n { flex: 0 0 28px; width: 28px; height: 28px; border-radius: 50%; background: var(--afp-orange); color: #fff; font-size: 14px; display: inline-flex; align-items: center; justify-content: center; }
.afp .afp-opciones { display: grid; grid-template-columns: repeat(auto-fill, minmax(210px, 1fr)); gap: 10px; }
.afp .afp-opt { position: relative; display: flex; gap: 10px; align-items: flex-start; border: 2px solid var(--afp-line); border-radius: 14px; padding: 12px 14px; cursor: pointer; background: #fff; transition: border-color .15s, box-shadow .15s; margin: 0; }
.afp .afp-opt:hover { border-color: var(--afp-sky); }
.afp .afp-opt input { position: absolute; opacity: 0; pointer-events: none; }
.afp .afp-opt.is-on { border-color: var(--afp-navy); box-shadow: 0 0 0 3px rgba(49, 50, 128, .12); background: #f8f9ff; }
.afp .afp-opt.is-off { opacity: .45; cursor: not-allowed; }
.afp .afp-opt-ico { font-size: 22px; line-height: 1; }
.afp .afp-opt b { display: block; font-size: 15px; color: var(--afp-ink); }
.afp .afp-opt small { display: block; font-size: 12px; color: var(--afp-muted); line-height: 1.4; margin-top: 2px; }
.afp .afp-opt .afp-opt-h { color: var(--afp-navy); font-weight: 700; }
.afp .afp-opt-check { margin-left: auto; flex: 0 0 22px; width: 22px; height: 22px; border-radius: 50%; border: 2px solid var(--afp-line); display: inline-flex; align-items: center; justify-content: center; font-size: 13px; color: transparent; }
.afp .afp-opt.is-on .afp-opt-check { background: var(--afp-navy); border-color: var(--afp-navy); color: #fff; }
.afp .afp-servicios { grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); }
.afp .afp-segmentado { display: inline-flex; background: var(--afp-bg); border-radius: 999px; padding: 4px; gap: 4px; margin: 0 0 14px; }
.afp .afp-segmentado label { margin: 0; cursor: pointer; }
.afp .afp-segmentado input { position: absolute; opacity: 0; }
.afp .afp-segmentado span { display: inline-block; padding: 8px 16px; border-radius: 999px; font-size: 14px; font-weight: 700; color: var(--afp-muted); }
.afp .afp-segmentado input:checked + span { background: var(--afp-navy); color: #fff; }
.afp .afp-segmentado input:focus-visible + span { outline: 2px solid var(--afp-sky); }
.afp .afp-fila { display: flex; flex-wrap: wrap; gap: 14px; align-items: flex-end; }
.afp .afp-res-form input[type="date"], .afp .afp-res-form input[type="time"], .afp .afp-res-form select, .afp .afp-res-form textarea, .afp .afp-res-form input[type="text"] {
    height: 46px; padding: 0 14px; font-family: inherit; font-size: 15px; color: var(--afp-ink); background: #fff; border: 2px solid var(--afp-line); border-radius: 12px; outline: none; margin: 0; max-width: 100%;
}
.afp .afp-res-form textarea { height: auto; min-height: 80px; padding: 10px 14px; width: 100%; max-width: 560px; resize: vertical; }
.afp .afp-res-form input[type="text"] { width: 100%; max-width: 560px; }
.afp .afp-res-form input:focus, .afp .afp-res-form select:focus, .afp .afp-res-form textarea:focus { border-color: var(--afp-sky); box-shadow: 0 0 0 4px rgba(114, 167, 247, .2); }
.afp .afp-dias { display: flex; flex-wrap: wrap; gap: 6px; margin-top: 12px; }
.afp .afp-dias label { display: inline-flex; align-items: center; gap: 6px; border: 2px solid var(--afp-line); border-radius: 999px; padding: 6px 12px; font-size: 13px; font-weight: 600; cursor: pointer; margin: 0; }
.afp .afp-dias label:has(input:checked) { border-color: var(--afp-navy); background: #f8f9ff; color: var(--afp-navy); }
.afp .afp-sino { display: flex; gap: 8px; flex-wrap: wrap; }
.afp .afp-sino label { display: inline-flex; align-items: center; gap: 8px; border: 2px solid var(--afp-line); border-radius: 12px; padding: 10px 16px; font-weight: 700; cursor: pointer; margin: 0; }
.afp .afp-sino label:has(input:checked) { border-color: var(--afp-navy); background: #f8f9ff; color: var(--afp-navy); }
.afp .afp-blk[hidden] { display: none; }
.afp .afp-plazo { font-size: 13px; color: var(--afp-muted); margin: 10px 0 0; }
.afp .afp-plazo b { color: var(--afp-ink); }
.afp .afp-nota-serv { background: #fff8ec; border-left: 4px solid var(--afp-orange); border-radius: 10px; padding: 10px 14px; font-size: 14px; color: #7a4a10; margin: 0 0 16px; }
.afp .afp-resumen { background: var(--afp-bg); border-radius: 12px; padding: 14px 16px; font-size: 14px; color: var(--afp-ink); margin: 0 0 16px; }
.afp .afp-resumen:empty { display: none; }
.afp .afp-mis-res { list-style: none; margin: 0; padding: 0; display: grid; gap: 10px; }
.afp .afp-res-item { display: flex; gap: 14px; align-items: flex-start; flex-wrap: wrap; border: 1px solid var(--afp-line); border-left: 4px solid var(--afp-sky); border-radius: 12px; padding: 14px 16px; }
.afp .afp-res-item.is-anulada { border-left-color: #cbd5e1; opacity: .7; }
.afp .afp-res-item .afp-opt-ico { font-size: 24px; }
.afp .afp-res-info { flex: 1 1 260px; }
.afp .afp-res-info b { color: var(--afp-navy); font-size: 15px; }
.afp .afp-res-info .afp-res-alumno { font-size: 14px; font-weight: 600; }
.afp .afp-res-info .afp-res-fechas { font-size: 14px; color: var(--afp-ink); }
.afp .afp-res-info .afp-res-det { font-size: 13px; color: var(--afp-muted); margin-top: 4px; }
.afp .afp-res-item form { margin: 0; }
.afp button.afp-btn.is-peligro { background: #fff; color: #b42318; box-shadow: inset 0 0 0 2px #f2b8b5; padding: 8px 14px; font-size: 13px; }
.afp button.afp-btn.is-peligro:hover { background: #b42318; color: #fff; }
.afp .afp-res-pasado { font-size: 12px; color: var(--afp-muted); }
.afp details.afp-historial { margin-top: 16px; }
.afp details.afp-historial summary { cursor: pointer; font-weight: 700; color: var(--afp-navy); font-size: 14px; }
.afp details.afp-historial .afp-mis-res { margin-top: 12px; }
.afp .afp-tag { display: inline-block; font-size: 11px; font-weight: 800; border-radius: 999px; padding: 2px 8px; background: #eef2f7; color: #475569; margin-left: 6px; vertical-align: middle; }
</style>

<div class="afp-card afp-res-form">
    <h2 class="afp-h2"><?php esc_html_e('Reservar un servicio', 'area-familias'); ?></h2>
    <?php if (trim((string) $gen['intro']) !== '') : ?>
        <p class="afp-sub"><?php echo esc_html($gen['intro']); ?></p>
    <?php endif; ?>

    <?php if (!$servicios) : ?>
        <span class="afp-pronto"><?php esc_html_e('Muy pronto', 'area-familias'); ?></span>
        <p class="afp-vacio"><?php esc_html_e('Ahora mismo no hay servicios disponibles para reservar desde aquí.', 'area-familias'); ?></p>
    <?php elseif (!$hijos && !empty($hijos_pendientes)) : ?>
        <p class="afp-vacio"><?php esc_html_e('Podréis reservar servicios cuando vuestro hijo o hija empiece en el colegio (la fecha está en «Mis hijos»).', 'area-familias'); ?></p>
    <?php elseif (!$hijos) : ?>
        <p class="afp-vacio"><?php esc_html_e('No encontramos alumnos asociados a tu cuenta.', 'area-familias'); ?></p>
    <?php else : ?>
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" id="afp-reservar-form" novalidate>
            <input type="hidden" name="action" value="af_reservar">
            <?php wp_nonce_field('af_reservar'); ?>

            <fieldset class="afp-paso">
                <legend><span class="afp-paso-n">1</span> <?php echo count($hijos) > 1 ? esc_html__('¿Para quién? (puedes elegir varios)', 'area-familias') : esc_html__('¿Para quién?', 'area-familias'); ?></legend>
                <div class="afp-opciones">
                    <?php foreach ($hijos as $h) : $on = in_array((string) $h['id'], $afr_sel_hijos, true); ?>
                        <label class="afp-opt<?php echo $on ? ' is-on' : ''; ?>">
                            <input type="checkbox" name="alumnos[]" value="<?php echo esc_attr($h['id']); ?>" data-etapa="<?php echo esc_attr(isset($h['etapa_key']) ? $h['etapa_key'] : ''); ?>" data-nombre="<?php echo esc_attr($h['nombre']); ?>" <?php checked($on); ?>>
                            <span class="afp-av" aria-hidden="true" style="flex-basis:38px;width:38px;height:38px;font-size:14px;"><?php echo esc_html(mb_strtoupper(mb_substr($h['nombre'], 0, 1))); ?></span>
                            <span><b><?php echo esc_html($h['nombre']); ?></b><small><?php echo esc_html($h['curso_label']); ?></small></span>
                            <span class="afp-opt-check" aria-hidden="true">✓</span>
                        </label>
                    <?php endforeach; ?>
                </div>
            </fieldset>

            <fieldset class="afp-paso">
                <legend><span class="afp-paso-n">2</span> <?php esc_html_e('¿Qué servicio?', 'area-familias'); ?></legend>
                <div class="afp-opciones afp-servicios">
                    <?php foreach ($servicios as $k => $s) : $on = $afr_serv_sel === $k; ?>
                        <label class="afp-opt<?php echo $on ? ' is-on' : ''; ?>">
                            <input type="radio" name="servicio" value="<?php echo esc_attr($k); ?>" <?php checked($on); ?>
                                   data-etapas="<?php echo esc_attr(implode(',', (array) $s['etapas'])); ?>"
                                   data-varios="<?php echo $s['varios_dias'] === 'yes' ? '1' : '0'; ?>"
                                   data-min="<?php echo esc_attr(AF_Reservas::primera_fecha($s)); ?>"
                                   data-hmin="<?php echo esc_attr($s['hora_inicio']); ?>" data-hmax="<?php echo esc_attr($s['hora_fin']); ?>"
                                   data-desayuno="<?php echo isset($s['desayuno']) && $s['desayuno'] === 'yes' ? '1' : '0'; ?>"
                                   data-dieta="<?php echo isset($s['dieta']) && $s['dieta'] === 'yes' ? '1' : '0'; ?>"
                                   data-recoge="<?php echo isset($s['recoge']) && $s['recoge'] === 'yes' ? '1' : '0'; ?>"
                                   data-nombre="<?php echo esc_attr($s['nombre']); ?>">
                            <span class="afp-opt-ico" aria-hidden="true"><?php echo esc_html($s['icono']); ?></span>
                            <span>
                                <b><?php echo esc_html($s['nombre']); ?></b>
                                <?php if ($h = AF_Reservas::horario($s)) : ?><small class="afp-opt-h"><?php echo esc_html($h); ?></small><?php endif; ?>
                                <?php if ($s['descripcion'] !== '') : ?><small><?php echo esc_html($s['descripcion']); ?></small><?php endif; ?>
                                <?php if ($s['precio'] !== '') : ?><small><?php echo esc_html($s['precio']); ?></small><?php endif; ?>
                                <small class="afp-opt-no" hidden></small>
                            </span>
                            <span class="afp-opt-check" aria-hidden="true">✓</span>
                        </label>
                    <?php endforeach; ?>
                </div>
            </fieldset>

            <fieldset class="afp-paso" id="afp-paso-fecha" <?php echo $afr_serv_sel === '' ? 'hidden' : ''; ?>>
                <legend><span class="afp-paso-n">3</span> <?php esc_html_e('¿Qué día?', 'area-familias'); ?></legend>
                <?php foreach ($servicios as $k => $s) : ?>
                    <?php if ($s['nota'] !== '') : ?><p class="afp-nota-serv afp-blk" data-serv="<?php echo esc_attr($k); ?>" hidden><?php echo esc_html($s['nota']); ?></p><?php endif; ?>
                <?php endforeach; ?>
                <div class="afp-segmentado afp-blk" id="afp-modo" hidden>
                    <label><input type="radio" name="modo" value="dia" <?php checked($afr_old('modo', 'dia'), 'dia'); ?>><span><?php esc_html_e('Un día', 'area-familias'); ?></span></label>
                    <label><input type="radio" name="modo" value="varios" <?php checked($afr_old('modo'), 'varios'); ?>><span><?php esc_html_e('Varios días', 'area-familias'); ?></span></label>
                </div>
                <div class="afp-blk afp-field" id="afp-un-dia">
                    <label for="afp-fecha"><?php esc_html_e('Día', 'area-familias'); ?></label>
                    <input type="date" id="afp-fecha" name="fecha" value="<?php echo esc_attr($afr_old('fecha')); ?>" min="<?php echo esc_attr($hoy); ?>" max="<?php echo esc_attr($max_fecha); ?>">
                </div>
                <div class="afp-blk" id="afp-varios" hidden>
                    <div class="afp-fila">
                        <div class="afp-field" style="margin:0;"><label for="afp-desde"><?php esc_html_e('Desde', 'area-familias'); ?></label>
                            <input type="date" id="afp-desde" name="desde" value="<?php echo esc_attr($afr_old('desde')); ?>" min="<?php echo esc_attr($hoy); ?>" max="<?php echo esc_attr($max_fecha); ?>"></div>
                        <div class="afp-field" style="margin:0;"><label for="afp-hasta"><?php esc_html_e('Hasta', 'area-familias'); ?></label>
                            <input type="date" id="afp-hasta" name="hasta" value="<?php echo esc_attr($afr_old('hasta')); ?>" min="<?php echo esc_attr($hoy); ?>"></div>
                    </div>
                    <div class="afp-dias" role="group" aria-label="<?php esc_attr_e('Días de la semana', 'area-familias'); ?>">
                        <?php foreach ($afr_dias as $n => $d) : ?>
                            <label><input type="checkbox" name="dias[]" value="<?php echo (int) $n; ?>" <?php checked(in_array((string) $n, $afr_dias_sel, true)); ?>> <?php echo esc_html($d); ?></label>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php foreach ($servicios as $k => $s) : ?>
                    <p class="afp-plazo afp-blk" data-serv="<?php echo esc_attr($k); ?>" hidden><b><?php esc_html_e('Plazo para reservar y anular:', 'area-familias'); ?></b> <?php echo esc_html(AF_Reservas::plazo_txt($s)); ?></p>
                <?php endforeach; ?>
            </fieldset>

            <fieldset class="afp-paso" id="afp-paso-datos" <?php echo $afr_serv_sel === '' ? 'hidden' : ''; ?>>
                <legend><span class="afp-paso-n">4</span> <?php esc_html_e('Datos de la reserva', 'area-familias'); ?></legend>

                <div class="afp-blk afp-field" data-blk="desayuno" hidden>
                    <label><?php esc_html_e('¿Quieres desayuno?', 'area-familias'); ?></label>
                    <div class="afp-sino">
                        <label><input type="radio" name="desayuno" value="si" <?php checked($afr_old('desayuno'), 'si'); ?>> <?php esc_html_e('Sí, con desayuno', 'area-familias'); ?></label>
                        <label><input type="radio" name="desayuno" value="no" <?php checked($afr_old('desayuno'), 'no'); ?>> <?php esc_html_e('No, sin desayuno', 'area-familias'); ?></label>
                    </div>
                </div>

                <div class="afp-blk afp-field" data-blk="dieta-pregunta" hidden>
                    <label><?php esc_html_e('¿Necesita dieta?', 'area-familias'); ?></label>
                    <div class="afp-sino">
                        <label><input type="radio" name="dieta" value="no" <?php checked($afr_old('dieta'), 'no'); ?>> <?php esc_html_e('No', 'area-familias'); ?></label>
                        <label><input type="radio" name="dieta" value="si" <?php checked($afr_old('dieta'), 'si'); ?>> <?php esc_html_e('Sí, necesita dieta', 'area-familias'); ?></label>
                    </div>
                </div>

                <div class="afp-blk" data-blk="dieta" hidden>
                    <?php if ($afr_tipos_dieta) : ?>
                        <div class="afp-field">
                            <label for="afp-tipo-dieta"><?php esc_html_e('Tipo de dieta', 'area-familias'); ?></label>
                            <select id="afp-tipo-dieta" name="tipo_dieta">
                                <option value=""><?php esc_html_e('— Elige —', 'area-familias'); ?></option>
                                <?php foreach ($afr_tipos_dieta as $t) : ?>
                                    <option value="<?php echo esc_attr($t); ?>" <?php selected($afr_old('tipo_dieta'), $t); ?>><?php echo esc_html($t); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    <?php endif; ?>
                    <div class="afp-field">
                        <label for="afp-instrucciones"><?php esc_html_e('Instrucciones especiales', 'area-familias'); ?></label>
                        <textarea id="afp-instrucciones" name="instrucciones" rows="3" placeholder="<?php esc_attr_e('Ej.: arroz o pasta blanca, pescado o pollo a la plancha, sin salsas…', 'area-familias'); ?>"><?php echo esc_textarea($afr_old('instrucciones')); ?></textarea>
                    </div>
                </div>

                <div class="afp-blk afp-field" data-blk="hora" hidden>
                    <label for="afp-hora" data-l-salida="<?php esc_attr_e('¿A qué hora va a salir?', 'area-familias'); ?>" data-l-retraso="<?php esc_attr_e('¿A qué hora va a llegar?', 'area-familias'); ?>"><?php esc_html_e('Hora', 'area-familias'); ?></label>
                    <input type="time" id="afp-hora" name="hora" step="300" value="<?php echo esc_attr($afr_old('hora')); ?>">
                    <small class="afp-hora-rango"></small>
                </div>

                <div class="afp-blk afp-field" data-blk="recoge" hidden>
                    <label for="afp-recoge"><?php esc_html_e('¿Quién le recoge?', 'area-familias'); ?> <small style="display:inline;font-weight:500;"><?php esc_html_e('(opcional)', 'area-familias'); ?></small></label>
                    <input type="text" id="afp-recoge" name="recoge" maxlength="150" value="<?php echo esc_attr($afr_old('recoge')); ?>" placeholder="<?php esc_attr_e('Ej.: su abuela, Carmen López', 'area-familias'); ?>">
                </div>

                <div class="afp-blk afp-field" data-blk="motivo" hidden>
                    <label for="afp-motivo"><?php esc_html_e('Motivo', 'area-familias'); ?> <small style="display:inline;font-weight:500;"><?php esc_html_e('(opcional)', 'area-familias'); ?></small></label>
                    <input type="text" id="afp-motivo" name="motivo" maxlength="500" value="<?php echo esc_attr($afr_old('motivo')); ?>">
                </div>

                <div class="afp-field">
                    <label for="afp-obs"><?php esc_html_e('Observaciones', 'area-familias'); ?> <small style="display:inline;font-weight:500;"><?php esc_html_e('(opcional)', 'area-familias'); ?></small></label>
                    <textarea id="afp-obs" name="observaciones" rows="2"><?php echo esc_textarea($afr_old('observaciones')); ?></textarea>
                </div>

                <div class="afp-resumen" id="afp-resumen" aria-live="polite"></div>
                <button type="submit" class="afp-btn"><?php esc_html_e('Reservar', 'area-familias'); ?></button>
            </fieldset>
        </form>
    <?php endif; ?>
</div>

<?php if ($servicios && $hijos) : ?>
    <div class="afp-card" id="afp-mis-reservas">
        <h2 class="afp-h2"><?php esc_html_e('Mis reservas', 'area-familias'); ?></h2>
        <?php if ($error_reservas) : ?>
            <div class="afp-msg error"><?php echo esc_html($error_reservas); ?></div>
        <?php elseif (!$proximas) : ?>
            <p class="afp-vacio"><?php esc_html_e('No tienes ninguna reserva pendiente.', 'area-familias'); ?></p>
        <?php else : ?>
            <p class="afp-sub"><?php esc_html_e('Reservas de hoy en adelante. Puedes anularlas dentro del plazo de cada servicio.', 'area-familias'); ?></p>
            <ul class="afp-mis-res">
                <?php foreach ($proximas as $r) : ?>
                    <li class="afp-res-item">
                        <span class="afp-opt-ico" aria-hidden="true"><?php echo esc_html(isset($afr_iconos[$r['servicio']]) ? $afr_iconos[$r['servicio']] : '📌'); ?></span>
                        <div class="afp-res-info">
                            <b><?php echo esc_html($r['servicio_nombre']); ?></b>
                            <div class="afp-res-alumno"><?php echo esc_html($r['alumno'] . ($r['curso_label'] ? ' · ' . $r['curso_label'] : '')); ?></div>
                            <div class="afp-res-fechas"><?php echo esc_html($r['fechas_texto']); ?></div>
                            <?php
                            $afr_det = array();
                            foreach ((array) $r['detalles'] as $d) {
                                if (!in_array($d['label'], array(__('Horario', 'area-familias'), __('Precio', 'area-familias'), __('Hora de salida', 'area-familias'), __('Hora de llegada', 'area-familias')), true)) {
                                    $afr_det[] = $d['label'] . ': ' . $d['valor'];
                                }
                            }
                            ?>
                            <?php if ($afr_det) : ?><div class="afp-res-det"><?php echo esc_html(implode(' · ', $afr_det)); ?></div><?php endif; ?>
                        </div>
                        <?php if (AF_Reservas::anulable($r)) : ?>
                            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" onsubmit="return confirm('<?php echo esc_js(sprintf(__('¿Anular la reserva de %1$s de %2$s?', 'area-familias'), $r['servicio_nombre'], $r['alumno'])); ?>');">
                                <input type="hidden" name="action" value="af_anular_reserva">
                                <input type="hidden" name="id" value="<?php echo esc_attr($r['id']); ?>">
                                <?php wp_nonce_field('af_anular_reserva_' . $r['id']); ?>
                                <button type="submit" class="afp-btn is-peligro"><?php esc_html_e('Anular', 'area-familias'); ?></button>
                            </form>
                        <?php else : ?>
                            <span class="afp-res-pasado"><?php esc_html_e('Ya no se puede anular desde aquí (plazo pasado).', 'area-familias'); ?></span>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>

        <?php if ($pasadas) : ?>
            <details class="afp-historial">
                <summary><?php printf(esc_html__('Reservas anteriores y anuladas (%d)', 'area-familias'), count($pasadas)); ?></summary>
                <ul class="afp-mis-res">
                    <?php foreach (array_reverse($pasadas) as $r) : ?>
                        <li class="afp-res-item<?php echo $r['estado'] === 'anulada' ? ' is-anulada' : ''; ?>">
                            <span class="afp-opt-ico" aria-hidden="true"><?php echo esc_html(isset($afr_iconos[$r['servicio']]) ? $afr_iconos[$r['servicio']] : '📌'); ?></span>
                            <div class="afp-res-info">
                                <b><?php echo esc_html($r['servicio_nombre']); ?></b>
                                <?php if ($r['estado'] === 'anulada') : ?><span class="afp-tag"><?php esc_html_e('Anulada', 'area-familias'); ?></span><?php endif; ?>
                                <div class="afp-res-alumno"><?php echo esc_html($r['alumno'] . ($r['curso_label'] ? ' · ' . $r['curso_label'] : '')); ?></div>
                                <div class="afp-res-fechas"><?php echo esc_html($r['fechas_texto']); ?></div>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </details>
        <?php endif; ?>
    </div>

<?php endif; ?>
