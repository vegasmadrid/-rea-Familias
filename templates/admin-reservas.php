<?php
/**
 * Admin: Área Familias › Reservas de servicios
 *
 * Variables: $servicios, $gen, $flash, $etapas
 */

if (!defined('ABSPATH')) {
    exit;
}

$afr_prof = trailingslashit((string) AF_Ajustes::get('profesores_url'));
?>
<div class="wrap af-wrap af-reservas-admin">
    <h1 class="af-title"><span class="af-badge"><?php esc_html_e('Área Familias', 'area-familias'); ?></span> <?php esc_html_e('Reservas de servicios', 'area-familias'); ?></h1>
    <p class="af-lead"><?php esc_html_e('Servicios que las familias pueden reservar en la opción "Reservar un servicio" de su panel. Eligen a su hijo o hija (o a varios), el servicio, el día o los días, y contestan sus preguntas. La reserva se guarda en el Área de Profesores, que avisa por correo al tutor/a, a Secretaría y a la familia.', 'area-familias'); ?></p>
    <p class="af-lead">
        <?php esc_html_e('Las reservas se consultan en el Área de Profesores: en "Reservas de familias" (backend, por día, para imprimir o descargar), en la pestaña "Reservas" de cada tutor/a y en el listado de cocina. Los textos de los correos se cambian allí, en "Correos automáticos".', 'area-familias'); ?>
        <?php if ($afr_prof !== '/') : ?>
            <a href="<?php echo esc_url($afr_prof . 'wp-admin/admin.php?page=area-profesores-reservas'); ?>" target="_blank" rel="noopener"><?php esc_html_e('Ver las reservas', 'area-familias'); ?> ↗</a>
        <?php endif; ?>
    </p>

    <?php if ($flash) : ?>
        <div class="notice notice-<?php echo esc_attr($flash['type']); ?> is-dismissible"><p><?php echo esc_html($flash['text']); ?></p></div>
    <?php endif; ?>

    <nav class="af-res-indice">
        <?php foreach ($servicios as $k => $s) : ?>
            <a href="#servicio-<?php echo esc_attr($k); ?>"><?php echo esc_html($s['icono'] . ' ' . $s['nombre']); ?><?php if ($s['activo'] !== 'yes') : ?> <span class="af-pill af-pill-warn"><?php esc_html_e('Desactivado', 'area-familias'); ?></span><?php endif; ?></a>
        <?php endforeach; ?>
        <a href="#af-res-generales"><?php esc_html_e('⚙️ Días sin clase y opciones', 'area-familias'); ?></a>
    </nav>

    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
        <input type="hidden" name="action" value="af_save_reservas">
        <?php wp_nonce_field('af_save_reservas'); ?>

        <?php foreach ($servicios as $k => $s) : $n = 'servicios[' . $k . ']'; ?>
            <div class="af-card af-res-card<?php echo $s['activo'] !== 'yes' ? ' is-off' : ''; ?>" id="servicio-<?php echo esc_attr($k); ?>">
                <h2><span class="af-res-ico"><?php echo esc_html($s['icono']); ?></span> <?php echo esc_html($s['nombre']); ?>
                    <label class="af-res-activo"><input type="checkbox" name="<?php echo esc_attr($n); ?>[activo]" value="1" <?php checked($s['activo'], 'yes'); ?>> <?php esc_html_e('Se puede reservar', 'area-familias'); ?></label></h2>
                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row"><?php esc_html_e('Nombre', 'area-familias'); ?></th>
                        <td><input type="text" class="regular-text" name="<?php echo esc_attr($n); ?>[nombre]" value="<?php echo esc_attr($s['nombre']); ?>"></td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e('Descripción', 'area-familias'); ?></th>
                        <td><textarea class="large-text" rows="2" name="<?php echo esc_attr($n); ?>[descripcion]"><?php echo esc_textarea($s['descripcion']); ?></textarea></td>
                    </tr>
                    <tr>
                        <th scope="row"><?php echo in_array($k, array('salida', 'retraso'), true) ? esc_html__('Horas permitidas', 'area-familias') : esc_html__('Horario', 'area-familias'); ?></th>
                        <td>
                            <?php esc_html_e('De', 'area-familias'); ?> <input type="time" name="<?php echo esc_attr($n); ?>[hora_inicio]" value="<?php echo esc_attr($s['hora_inicio']); ?>">
                            <?php esc_html_e('a', 'area-familias'); ?> <input type="time" name="<?php echo esc_attr($n); ?>[hora_fin]" value="<?php echo esc_attr($s['hora_fin']); ?>">
                            <p class="description"><?php echo in_array($k, array('salida', 'retraso'), true)
                                ? esc_html__('La familia elige una hora dentro de este margen.', 'area-familias')
                                : esc_html__('Se muestra a las familias y en los correos. Déjalo vacío si no tiene horario.', 'area-familias'); ?></p>
                        </td>
                    </tr>
                    <?php if (isset($s['desayuno'])) : ?>
                        <tr><th scope="row"><?php esc_html_e('Desayuno', 'area-familias'); ?></th>
                            <td><label><input type="checkbox" name="<?php echo esc_attr($n); ?>[desayuno]" value="1" <?php checked($s['desayuno'], 'yes'); ?>> <?php esc_html_e('Preguntar si quieren desayuno (con o sin desayuno)', 'area-familias'); ?></label></td></tr>
                    <?php endif; ?>
                    <?php if (isset($s['dieta'])) : ?>
                        <tr><th scope="row"><?php esc_html_e('Dieta', 'area-familias'); ?></th>
                            <td><label><input type="checkbox" name="<?php echo esc_attr($n); ?>[dieta]" value="1" <?php checked($s['dieta'], 'yes'); ?>> <?php esc_html_e('Preguntar si necesita dieta (si dicen que sí: tipo de dieta, instrucciones especiales y motivo opcional)', 'area-familias'); ?></label></td></tr>
                    <?php endif; ?>
                    <?php if (isset($s['tipos_dieta'])) : ?>
                        <tr><th scope="row"><?php esc_html_e('Tipos de dieta', 'area-familias'); ?></th>
                            <td><textarea class="large-text" rows="4" name="<?php echo esc_attr($n); ?>[tipos_dieta]"><?php echo esc_textarea($s['tipos_dieta']); ?></textarea>
                                <p class="description"><?php esc_html_e('Uno por línea. Se ofrecen en un desplegable (también al pedir dieta en el Comedor). Además, siempre pueden escribir instrucciones especiales y, si quieren, el motivo.', 'area-familias'); ?></p></td></tr>
                    <?php endif; ?>
                    <?php if (isset($s['recoge'])) : ?>
                        <tr><th scope="row"><?php esc_html_e('Quién recoge', 'area-familias'); ?></th>
                            <td><label><input type="checkbox" name="<?php echo esc_attr($n); ?>[recoge]" value="1" <?php checked($s['recoge'], 'yes'); ?>> <?php esc_html_e('Preguntar quién le recoge (opcional para la familia)', 'area-familias'); ?></label></td></tr>
                    <?php endif; ?>
                    <tr>
                        <th scope="row"><?php esc_html_e('Plazo', 'area-familias'); ?></th>
                        <td>
                            <?php esc_html_e('Hasta las', 'area-familias'); ?> <input type="time" name="<?php echo esc_attr($n); ?>[plazo_hora]" value="<?php echo esc_attr($s['plazo_hora']); ?>">
                            <select name="<?php echo esc_attr($n); ?>[plazo_dias]">
                                <?php for ($i = 0; $i <= 7; $i++) : ?>
                                    <option value="<?php echo (int) $i; ?>" <?php selected((int) $s['plazo_dias'], $i); ?>><?php echo $i === 0 ? esc_html__('del mismo día', 'area-familias') : ($i === 1 ? esc_html__('del día anterior', 'area-familias') : esc_html(sprintf(__('de %d días antes', 'area-familias'), $i))); ?></option>
                                <?php endfor; ?>
                            </select>
                            <p class="description"><?php esc_html_e('Hasta cuándo se puede reservar (y anular) cada día. Ahora mismo:', 'area-familias'); ?> <strong><?php echo esc_html(AF_Reservas::plazo_txt($s)); ?></strong>
                                <?php if (in_array($k, array('salida', 'retraso'), true)) : ?><?php esc_html_e('Si dejas la hora vacía y "del mismo día", se puede avisar hasta la hora indicada por la familia.', 'area-familias'); ?><?php endif; ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e('Varios días', 'area-familias'); ?></th>
                        <td><label><input type="checkbox" name="<?php echo esc_attr($n); ?>[varios_dias]" value="1" <?php checked($s['varios_dias'], 'yes'); ?>> <?php esc_html_e('Permitir reservar varios días de una vez (desde – hasta, eligiendo los días de la semana)', 'area-familias'); ?></label></td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e('Etapas', 'area-familias'); ?></th>
                        <td>
                            <?php foreach ($etapas as $ek => $el) : ?>
                                <label style="margin-right:14px;"><input type="checkbox" name="<?php echo esc_attr($n); ?>[etapas][]" value="<?php echo esc_attr($ek); ?>" <?php checked(!$s['etapas'] || in_array($ek, (array) $s['etapas'], true)); ?>> <?php echo esc_html($el); ?></label>
                            <?php endforeach; ?>
                            <p class="description"><?php esc_html_e('Solo los alumnos de estas etapas pueden usar el servicio.', 'area-familias'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e('Precio', 'area-familias'); ?></th>
                        <td><input type="text" class="regular-text" name="<?php echo esc_attr($n); ?>[precio]" value="<?php echo esc_attr($s['precio']); ?>" placeholder="<?php esc_attr_e('Opcional. Ej.: 4 € al día (con desayuno, 5 €)', 'area-familias'); ?>"></td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e('Nota para las familias', 'area-familias'); ?></th>
                        <td><textarea class="large-text" rows="2" name="<?php echo esc_attr($n); ?>[nota]" placeholder="<?php esc_attr_e('Opcional. Se muestra al elegir el servicio. Ej.: El cargo se pasa a final de mes.', 'area-familias'); ?>"><?php echo esc_textarea($s['nota']); ?></textarea></td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e('Avisar también a', 'area-familias'); ?></th>
                        <td><input type="text" class="large-text" name="<?php echo esc_attr($n); ?>[avisar]" value="<?php echo esc_attr($s['avisar']); ?>" placeholder="<?php esc_attr_e('Opcional. Ej.: comedor@casadelavirgen.es', 'area-familias'); ?>">
                            <p class="description"><?php esc_html_e('Correos separados por comas que reciben el aviso de cada reserva de este servicio, además del tutor/a y Secretaría.', 'area-familias'); ?></p></td>
                    </tr>
                </table>
                <p class="af-res-acciones">
                    <button type="submit" class="button button-primary"><?php esc_html_e('Guardar', 'area-familias'); ?></button>
                    <button type="submit" name="restablecer" value="<?php echo esc_attr($k); ?>" class="button-link button-link-delete" onclick="return confirm('<?php echo esc_js(__('¿Volver a los valores iniciales de este servicio?', 'area-familias')); ?>');"><?php esc_html_e('Restablecer valores iniciales', 'area-familias'); ?></button>
                </p>
            </div>
        <?php endforeach; ?>

        <div class="af-card" id="af-res-generales">
            <h2><span class="dashicons dashicons-admin-generic"></span> <?php esc_html_e('Días sin clase y opciones', 'area-familias'); ?></h2>
            <table class="form-table" role="presentation">
                <tr>
                    <th scope="row"><?php esc_html_e('Texto de introducción', 'area-familias'); ?></th>
                    <td><textarea class="large-text" rows="2" name="generales[intro]"><?php echo esc_textarea($gen['intro']); ?></textarea></td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e('Días sin clase', 'area-familias'); ?></th>
                    <td><textarea class="large-text code" rows="8" name="generales[festivos]" placeholder="08/12/2026 | Inmaculada&#10;23/12/2026 - 07/01/2027 | Navidad"><?php echo esc_textarea($gen['festivos']); ?></textarea>
                        <p class="description"><?php esc_html_e('Uno por línea: un día (08/12/2026) o un periodo (23/12/2026 - 07/01/2027). Después de "|" puedes poner un comentario. Esos días, y los sábados y domingos, no se pueden reservar.', 'area-familias'); ?>
                            <?php $afr_n = count(AF_Reservas::festivos()); if ($afr_n) : ?><strong><?php printf(esc_html(_n('%d día sin clase configurado.', '%d días sin clase configurados.', $afr_n, 'area-familias')), (int) $afr_n); ?></strong><?php endif; ?></p></td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e('Antelación máxima', 'area-familias'); ?></th>
                    <td><input type="number" min="1" max="366" name="generales[max_antelacion]" value="<?php echo esc_attr($gen['max_antelacion']); ?>" style="width:90px;"> <?php esc_html_e('días', 'area-familias'); ?>
                        <p class="description"><?php esc_html_e('Hasta cuántos días por adelantado se puede reservar el primer día.', 'area-familias'); ?></p></td>
                </tr>
            </table>
            <p><button type="submit" class="button button-primary"><?php esc_html_e('Guardar', 'area-familias'); ?></button></p>
        </div>
    </form>
</div>
