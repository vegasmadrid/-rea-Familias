<?php
/**
 * Admin: Área Familias › Nuevas familias (solicitud de matrícula online).
 *
 * Variables: $m (ajustes), $flash, $pages, $niveles, $conectada
 */

if (!defined('ABSPATH')) {
    exit;
}

$af_etapas = array();
foreach ($niveles as $k => $n) {
    $af_etapas[$n['etapa']][$k] = $n['label'];
}
?>
<div class="wrap af-wrap">
    <h1 class="af-title"><span class="af-badge"><?php esc_html_e('Área Familias', 'area-familias'); ?></span> <?php esc_html_e('Nuevas familias', 'area-familias'); ?></h1>

    <?php if ($flash) : ?>
        <div class="notice notice-<?php echo esc_attr($flash['type']); ?> is-dismissible"><p><?php echo esc_html($flash['text']); ?></p></div>
    <?php endif; ?>

    <?php if (!$conectada) : ?>
        <div class="notice notice-error"><p><?php esc_html_e('El Área Familias no está conectada con el Área de Profesores (Área Familias › Espacios de aula): las solicitudes no se podrían entregar.', 'area-familias'); ?></p></div>
    <?php endif; ?>

    <p class="description" style="max-width:820px;"><?php esc_html_e('Las familias nuevas (sin cuenta del colegio) rellenan online los datos de la ficha de matrícula, la reserva de plaza del Primer Ciclo y la orden SEPA. Confirman su correo con un código y la solicitud llega cifrada al Área de Profesores, a la pestaña «Nuevas familias» (Secretaría, Dirección y Administración), desde donde se imprime ya rellena para que la firmen. Aquí no se guarda nada.', 'area-familias'); ?></p>

    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
        <input type="hidden" name="action" value="af_save_matricula">
        <?php wp_nonce_field('af_save_matricula'); ?>

        <div class="af-card">
            <h2><span class="dashicons dashicons-welcome-add-page"></span> <?php esc_html_e('Formulario', 'area-familias'); ?></h2>
            <table class="form-table" role="presentation">
                <tr>
                    <th scope="row"><?php esc_html_e('Plazo abierto', 'area-familias'); ?></th>
                    <td>
                        <label><input type="checkbox" name="abierta" value="1" <?php checked($m['abierta'], 'yes'); ?>> <?php esc_html_e('Las familias pueden enviar solicitudes', 'area-familias'); ?></label>
                        <p class="description"><?php esc_html_e('Mientras está cerrado, la página muestra el texto de «Plazo cerrado» y no sale el botón en el login.', 'area-familias'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="page_id"><?php esc_html_e('Página del formulario', 'area-familias'); ?></label></th>
                    <td>
                        <select name="page_id" id="page_id" class="regular-text">
                            <option value="0"><?php esc_html_e('— Elige una página —', 'area-familias'); ?></option>
                            <?php foreach ($pages as $p) : ?>
                                <option value="<?php echo esc_attr($p->ID); ?>" <?php selected((int) $m['page_id'], (int) $p->ID); ?>><?php echo esc_html($p->post_title); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <?php if ($m['page_id']) : ?> <a href="<?php echo esc_url(get_permalink($m['page_id'])); ?>" target="_blank" rel="noopener"><?php esc_html_e('Ver', 'area-familias'); ?></a><?php endif; ?>
                        <p class="description"><?php esc_html_e('Crea una página vacía (p. ej. «Matrícula») y elígela: su contenido se sustituye por el formulario. Siempre es pública, aunque esté activado «Restringir toda la web».', 'area-familias'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="cursos_escolares"><?php esc_html_e('Curso escolar', 'area-familias'); ?></label></th>
                    <td>
                        <input type="text" class="regular-text" id="cursos_escolares" name="cursos_escolares" value="<?php echo esc_attr($m['cursos_escolares']); ?>">
                        <p class="description"><?php esc_html_e('Por ejemplo 2027/2028. Si pones dos separados por comas (2026/2027, 2027/2028), la familia elige.', 'area-familias'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e('Cursos que se pueden pedir', 'area-familias'); ?></th>
                    <td>
                        <?php foreach ($af_etapas as $etapa => $lista) : ?>
                            <p style="margin:0 0 6px;"><strong><?php echo esc_html($etapa); ?>:</strong>
                                <?php foreach ($lista as $k => $label) : ?>
                                    <label style="margin-right:12px;white-space:nowrap;"><input type="checkbox" name="niveles[]" value="<?php echo esc_attr($k); ?>" <?php checked(in_array($k, (array) $m['niveles'], true)); ?>> <?php echo esc_html($label); ?></label>
                                <?php endforeach; ?>
                            </p>
                        <?php endforeach; ?>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="boton_login"><?php esc_html_e('Botón en el login', 'area-familias'); ?></label></th>
                    <td><input type="text" class="large-text" id="boton_login" name="boton_login" value="<?php echo esc_attr($m['boton_login']); ?>"></td>
                </tr>
                <tr>
                    <th scope="row"><label for="intro"><?php esc_html_e('Texto de presentación', 'area-familias'); ?></label></th>
                    <td><textarea class="large-text" rows="4" id="intro" name="intro"><?php echo esc_textarea($m['intro']); ?></textarea></td>
                </tr>
                <tr>
                    <th scope="row"><label for="cerrado"><?php esc_html_e('Texto con el plazo cerrado', 'area-familias'); ?></label></th>
                    <td><textarea class="large-text" rows="2" id="cerrado" name="cerrado"><?php echo esc_textarea($m['cerrado']); ?></textarea></td>
                </tr>
            </table>
        </div>

        <div class="af-card">
            <h2><span class="dashicons dashicons-shield"></span> <?php esc_html_e('Aceptaciones y protección de datos', 'area-familias'); ?></h2>
            <table class="form-table" role="presentation">
                <tr>
                    <th scope="row"><label for="ideario_url"><?php esc_html_e('Enlace al Ideario y al RRI', 'area-familias'); ?></label></th>
                    <td><input type="url" class="large-text" id="ideario_url" name="ideario_url" value="<?php echo esc_attr($m['ideario_url']); ?>" placeholder="https://">
                        <p class="description"><?php esc_html_e('Opcional: sale un enlace «Leer» junto a la casilla de aceptación.', 'area-familias'); ?></p></td>
                </tr>
                <tr>
                    <th scope="row"><label for="privacidad"><?php esc_html_e('Información sobre protección de datos', 'area-familias'); ?></label></th>
                    <td><textarea class="large-text" rows="7" id="privacidad" name="privacidad"><?php echo esc_textarea($m['privacidad']); ?></textarea>
                        <p class="description"><strong><?php esc_html_e('Es un texto de ejemplo: pedid que lo revise el delegado de protección de datos del colegio antes de abrir el plazo.', 'area-familias'); ?></strong></p></td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e('Uso de la imagen', 'area-familias'); ?></th>
                    <td><label><input type="checkbox" name="preguntar_imagen" value="1" <?php checked($m['preguntar_imagen'], 'yes'); ?>> <?php esc_html_e('Preguntar si autorizan el uso de la imagen de sus hijos', 'area-familias'); ?></label></td>
                </tr>
                <tr>
                    <th scope="row"><label for="email_titulo"><?php esc_html_e('Asunto del correo con el código', 'area-familias'); ?></label></th>
                    <td><input type="text" class="large-text" id="email_titulo" name="email_titulo" value="<?php echo esc_attr($m['email_titulo']); ?>"></td>
                </tr>
            </table>
        </div>

        <?php submit_button(__('Guardar', 'area-familias')); ?>
    </form>
</div>
