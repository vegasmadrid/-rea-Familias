<?php
/**
 * Admin: Área Familias › Espacios de aula
 *
 * Variables: $s, $flash, $pages, $ultima, $page_id, $es_publica
 */

if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="wrap af-wrap">
    <h1 class="af-title"><span class="af-badge"><?php esc_html_e('Área Familias', 'area-familias'); ?></span> <?php esc_html_e('Espacios de aula', 'area-familias'); ?></h1>
    <p class="af-lead"><?php esc_html_e('Muestra en una página de esta web los espacios de Google Chat de cada aula. Los datos se leen del Área de Profesores, así que siempre están actualizados (cursos, tutores y promociones).', 'area-familias'); ?></p>

    <?php if ($flash) : ?>
        <div class="notice notice-<?php echo esc_attr($flash['type']); ?> is-dismissible"><p><?php echo esc_html($flash['text']); ?></p></div>
    <?php endif; ?>

    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
        <input type="hidden" name="action" value="af_save_espacios">
        <?php wp_nonce_field('af_save_espacios'); ?>

        <div class="af-columns">
            <div class="af-card">
                <h2><span class="dashicons dashicons-admin-page"></span> <?php esc_html_e('Página', 'area-familias'); ?></h2>
                <p>
                    <label for="espacios_page_id"><strong><?php esc_html_e('Página donde se muestran los espacios de aula', 'area-familias'); ?></strong></label><br>
                    <select name="espacios_page_id" id="espacios_page_id" class="widefat">
                        <option value="0"><?php esc_html_e('— Ninguna —', 'area-familias'); ?></option>
                        <?php foreach ($pages as $p) : ?>
                            <option value="<?php echo esc_attr($p->ID); ?>" <?php selected($page_id, (int) $p->ID); ?>><?php echo esc_html($p->post_title); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <?php if ($page_id) : ?>
                        <a href="<?php echo esc_url(get_permalink($page_id)); ?>" target="_blank" rel="noopener"><?php esc_html_e('Ver página', 'area-familias'); ?></a>
                    <?php endif; ?>
                    <span class="description"><?php esc_html_e('Su contenido se genera solo (no hace falta shortcode).', 'area-familias'); ?></span>
                </p>
                <p>
                    <label><input type="checkbox" name="espacios_publica" value="1" <?php checked($es_publica); ?>> <strong><?php esc_html_e('Página pública', 'area-familias'); ?></strong> — <?php esc_html_e('se puede ver sin iniciar sesión (misma opción que Ajustes › Páginas públicas). Si no, solo la ven las familias con sesión iniciada.', 'area-familias'); ?></label>
                </p>
                <p>
                    <label for="espacios_titulo"><strong><?php esc_html_e('Título', 'area-familias'); ?></strong></label><br>
                    <input type="text" name="espacios_titulo" id="espacios_titulo" class="widefat" value="<?php echo esc_attr($s['espacios_titulo']); ?>">
                </p>
                <p>
                    <label for="espacios_intro"><strong><?php esc_html_e('Texto de introducción', 'area-familias'); ?></strong></label><br>
                    <textarea name="espacios_intro" id="espacios_intro" class="widefat" rows="3"><?php echo esc_textarea($s['espacios_intro']); ?></textarea>
                    <span class="description"><?php esc_html_e('Puedes usar **texto** para la negrita.', 'area-familias'); ?></span>
                </p>
                <fieldset class="af-options">
                    <label><input type="checkbox" name="espacios_tribanda" value="1" <?php checked($s['espacios_tribanda'], 'yes'); ?>> <?php esc_html_e('Mostrar la tribanda corporativa', 'area-familias'); ?></label>
                    <label><input type="checkbox" name="espacios_tutor" value="1" id="af-tutor" <?php checked($s['espacios_tutor'], 'yes'); ?>> <?php esc_html_e('Mostrar el nombre del tutor/a de cada curso', 'area-familias'); ?></label>
                    <label class="af-sub"><input type="checkbox" name="espacios_foto" value="1" id="af-foto" <?php checked($s['espacios_foto'], 'yes'); ?>> <?php esc_html_e('…con su foto (o sus iniciales)', 'area-familias'); ?></label>
                    <label><input type="checkbox" name="espacios_anteriores" value="1" <?php checked($s['espacios_anteriores'], 'yes'); ?>> <?php esc_html_e('Mostrar las promociones anteriores al final', 'area-familias'); ?></label>
                    <label><input type="checkbox" name="espacios_otros" value="1" <?php checked($s['espacios_otros'], 'yes'); ?>> <?php esc_html_e('Mostrar otros espacios (AMPA, coro…)', 'area-familias'); ?></label>
                    <label><input type="checkbox" name="espacios_ayuda" value="1" <?php checked($s['espacios_ayuda'], 'yes'); ?>> <?php esc_html_e('Mostrar el recuadro "¿No puedes entrar en el espacio?"', 'area-familias'); ?></label>
                </fieldset>
            </div>

            <div class="af-card">
                <h2><span class="dashicons dashicons-randomize"></span> <?php esc_html_e('Conexión con el Área de Profesores', 'area-familias'); ?></h2>
                <p class="description" style="margin-top:0;"><?php esc_html_e('En el Área de Profesores: Área Profesores › Espacios de Chat › Conexión con el Área Familias. Copia de allí la dirección y la clave.', 'area-familias'); ?></p>
                <p>
                    <label for="profesores_url"><strong><?php esc_html_e('Dirección del Área de Profesores', 'area-familias'); ?></strong></label><br>
                    <input type="url" name="profesores_url" id="profesores_url" class="widefat" value="<?php echo esc_attr($s['profesores_url']); ?>" placeholder="https://profesores.casadelavirgen.es/">
                </p>
                <p>
                    <label for="api_key"><strong><?php esc_html_e('Clave de conexión', 'area-familias'); ?></strong></label><br>
                    <input type="password" name="api_key" id="api_key" class="widefat" value="" autocomplete="off" placeholder="<?php echo $s['api_key'] !== '' ? esc_attr__('(guardada; déjalo vacío para mantenerla)', 'area-familias') : ''; ?>">
                </p>
                <p>
                    <label for="cache_minutos"><strong><?php esc_html_e('Actualizar los datos cada', 'area-familias'); ?></strong></label>
                    <input type="number" name="cache_minutos" id="cache_minutos" min="5" max="1440" class="small-text" value="<?php echo esc_attr($s['cache_minutos']); ?>"> <?php esc_html_e('minutos', 'area-familias'); ?>
                </p>

                <h3><?php esc_html_e('Estado', 'area-familias'); ?></h3>
                <?php if (is_array($ultima)) : ?>
                    <p>
                        <span class="af-pill af-pill-ok"><?php esc_html_e('Datos recibidos', 'area-familias'); ?></span>
                        <?php printf(
                            esc_html__('%1$d espacios · curso %2$s · hace %3$s', 'area-familias'),
                            (int) AF_Espacios::contar($ultima),
                            esc_html(isset($ultima['curso_escolar']) ? $ultima['curso_escolar'] : '—'),
                            esc_html(human_time_diff((int) $ultima['recibido'], time()))
                        ); ?>
                    </p>
                <?php else : ?>
                    <p><span class="af-pill af-pill-warn"><?php esc_html_e('Sin datos todavía', 'area-familias'); ?></span></p>
                <?php endif; ?>
            </div>
        </div>

        <p><button type="submit" class="button button-primary button-large"><?php esc_html_e('Guardar y comprobar la conexión', 'area-familias'); ?></button></p>
    </form>

    <p>
        <a class="button" href="<?php echo esc_url(wp_nonce_url(admin_url('admin-post.php?action=af_refresh_espacios'), 'af_refresh_espacios')); ?>"><?php esc_html_e('Actualizar ahora desde el Área de Profesores', 'area-familias'); ?></a>
    </p>
</div>
<script>
(function () {
    var t = document.getElementById('af-tutor'), f = document.getElementById('af-foto');
    if (!t || !f) { return; }
    var sync = function () { f.disabled = !t.checked; f.closest('label').style.opacity = t.checked ? 1 : .5; };
    t.addEventListener('change', sync); sync();
})();
</script>
