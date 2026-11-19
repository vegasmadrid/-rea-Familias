<?php
/**
 * Admin: Área Familias › Panel de familias
 *
 * Variables: $s, $flash, $start_id
 */

if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="wrap af-wrap">
    <h1 class="af-title"><span class="af-badge"><?php esc_html_e('Área Familias', 'area-familias'); ?></span> <?php esc_html_e('Panel de familias', 'area-familias'); ?></h1>
    <p class="af-lead"><?php esc_html_e('Es la página de inicio de las familias: un saludo y sus opciones (Mis hijos, Mis datos, Reservar un servicio, Enlaces de interés y Espacios de aula). Los datos de los alumnos y las familias se leen del Área de Profesores.', 'area-familias'); ?></p>

    <?php if ($flash) : ?>
        <div class="notice notice-<?php echo esc_attr($flash['type']); ?> is-dismissible"><p><?php echo esc_html($flash['text']); ?></p></div>
    <?php endif; ?>

    <div class="af-card">
        <h2><span class="dashicons dashicons-admin-home"></span> <?php esc_html_e('Página', 'area-familias'); ?></h2>
        <?php if ($start_id && get_post_status($start_id)) : ?>
            <p><?php printf(esc_html__('El panel se muestra en la "Página de inicio": %s.', 'area-familias'), '<a href="' . esc_url(get_permalink($start_id)) . '" target="_blank" rel="noopener">' . esc_html(get_the_title($start_id)) . '</a>'); ?>
                <a class="button button-small" href="<?php echo esc_url(admin_url('admin.php?page=area-familias')); ?>"><?php esc_html_e('Cambiarla en Ajustes', 'area-familias'); ?></a></p>
        <?php else : ?>
            <div class="notice notice-warning inline"><p><?php esc_html_e('Elige primero la "Página de inicio" en Área Familias › Ajustes: es donde se mostrará el panel.', 'area-familias'); ?> <a href="<?php echo esc_url(admin_url('admin.php?page=area-familias')); ?>"><?php esc_html_e('Ir a Ajustes', 'area-familias'); ?></a></p></div>
        <?php endif; ?>
    </div>

    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
        <input type="hidden" name="action" value="af_save_panel">
        <?php wp_nonce_field('af_save_panel'); ?>

        <div class="af-card">
            <h2><span class="dashicons dashicons-format-chat"></span> <?php esc_html_e('Saludo', 'area-familias'); ?></h2>
            <p class="description"><?php esc_html_e('Texto que aparece bajo el "Hola, …" de la familia.', 'area-familias'); ?></p>
            <textarea name="panel_saludo" class="large-text" rows="2"><?php echo esc_textarea($s['panel_saludo']); ?></textarea>
        </div>

        <div class="af-card">
            <h2><span class="dashicons dashicons-warning"></span> <?php esc_html_e('Aviso de teléfono de emergencia', 'area-familias'); ?></h2>
            <p class="description"><?php esc_html_e('Se muestra arriba del panel a las familias en las que falta el teléfono de emergencia de algún padre o madre. {nombres} se sustituye por sus nombres.', 'area-familias'); ?></p>
            <textarea name="panel_aviso_telefono" class="large-text" rows="2"><?php echo esc_textarea($s['panel_aviso_telefono']); ?></textarea>
        </div>

        <div class="af-card">
            <h2><span class="dashicons dashicons-admin-links"></span> <?php esc_html_e('Enlaces de interés', 'area-familias'); ?></h2>
            <p class="description"><?php esc_html_e('Uno por línea: Título | dirección | descripción (opcional). Si no hay ninguno, la pestaña no aparece.', 'area-familias'); ?></p>
            <textarea name="panel_enlaces" class="large-text code" rows="6" placeholder="Calendario escolar | https://madrid.casadelavirgen.es/calendario | Festivos y evaluaciones&#10;Menú del comedor | https://..."><?php echo esc_textarea($s['panel_enlaces']); ?></textarea>
        </div>

        <div class="af-card">
            <h2><span class="dashicons dashicons-calendar-alt"></span> <?php esc_html_e('Servicios que se podrán reservar', 'area-familias'); ?></h2>
            <p class="description"><?php esc_html_e('Uno por línea. Por ahora se muestran como "Muy pronto"; los usaremos al activar las reservas.', 'area-familias'); ?></p>
            <textarea name="panel_servicios" class="large-text" rows="7"><?php echo esc_textarea($s['panel_servicios']); ?></textarea>
        </div>

        <p><button type="submit" class="button button-primary button-large"><?php esc_html_e('Guardar', 'area-familias'); ?></button></p>
    </form>
</div>
