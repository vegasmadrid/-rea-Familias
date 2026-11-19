<?php
/**
 * Admin: Área Familias › Ajustes
 *
 * Variables: $s, $flash, $pages, $nextend
 */

if (!defined('ABSPATH')) {
    exit;
}

$af_page_select = function ($name, $selected, $empty) use ($pages) {
    echo '<select name="' . esc_attr($name) . '" id="' . esc_attr($name) . '" class="regular-text">';
    echo '<option value="0">' . esc_html($empty) . '</option>';
    foreach ($pages as $p) {
        echo '<option value="' . esc_attr($p->ID) . '"' . selected((int) $selected, (int) $p->ID, false) . '>' . esc_html($p->post_title) . '</option>';
    }
    echo '</select>';
};
?>
<div class="wrap af-wrap">
    <h1 class="af-title"><span class="af-badge"><?php esc_html_e('Área Familias', 'area-familias'); ?></span> <?php esc_html_e('Ajustes', 'area-familias'); ?></h1>

    <?php if ($flash) : ?>
        <div class="notice notice-<?php echo esc_attr($flash['type']); ?> is-dismissible"><p><?php echo esc_html($flash['text']); ?></p></div>
    <?php endif; ?>

    <?php if (!$nextend) : ?>
        <div class="notice notice-warning"><p><?php esc_html_e('No está activo el plugin Nextend Social Login: el botón de acceso con Google lo necesita (igual que en el Área de Profesores).', 'area-familias'); ?></p></div>
    <?php endif; ?>

    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
        <input type="hidden" name="action" value="af_save_ajustes">
        <?php wp_nonce_field('af_save_ajustes'); ?>

        <div class="af-card">
            <h2><span class="dashicons dashicons-admin-page"></span> <?php esc_html_e('Páginas', 'area-familias'); ?></h2>
            <table class="form-table" role="presentation">
                <tr>
                    <th scope="row"><label for="login_page_id"><?php esc_html_e('Página de login', 'area-familias'); ?></label></th>
                    <td>
                        <?php $af_page_select('login_page_id', $s['login_page_id'], __('— wp-login.php de WordPress —', 'area-familias')); ?>
                        <?php if ($s['login_page_id']) : ?> <a href="<?php echo esc_url(get_permalink($s['login_page_id'])); ?>" target="_blank" rel="noopener"><?php esc_html_e('Ver', 'area-familias'); ?></a><?php endif; ?>
                        <p class="description"><?php esc_html_e('Su contenido se sustituye por la pantalla de acceso con el botón de Google. Siempre es pública.', 'area-familias'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="start_page_id"><?php esc_html_e('Página de inicio', 'area-familias'); ?></label></th>
                    <td>
                        <?php $af_page_select('start_page_id', $s['start_page_id'], __('— Portada de la web —', 'area-familias')); ?>
                        <p class="description"><?php esc_html_e('A dónde van las familias después de iniciar sesión. También es la portada de la web (lo que se ve al abrir la dirección del dominio), en lugar de la elegida en Ajustes › Lectura. En modo mantenimiento, las familias ven como portada la página de mantenimiento.', 'area-familias'); ?></p>
                        <label><input type="checkbox" name="respect_redirect_to" value="1" <?php checked($s['respect_redirect_to'], 'yes'); ?>> <?php esc_html_e('Si intentaban abrir una página concreta, volver a esa página', 'area-familias'); ?></label>
                    </td>
                </tr>
            </table>
        </div>

        <div class="af-card <?php echo $s['mantenimiento'] === 'yes' ? 'af-card-warn' : ''; ?>">
            <h2><span class="dashicons dashicons-hammer"></span> <?php esc_html_e('Modo mantenimiento', 'area-familias'); ?></h2>
            <table class="form-table" role="presentation">
                <tr>
                    <th scope="row"><?php esc_html_e('Activar', 'area-familias'); ?></th>
                    <td>
                        <label><input type="checkbox" name="mantenimiento" value="1" <?php checked($s['mantenimiento'], 'yes'); ?>> <strong><?php esc_html_e('Sitio en mantenimiento', 'area-familias'); ?></strong></label>
                        <p class="description"><?php esc_html_e('Mientras está activo, las familias empiezan en la página de abajo (por ejemplo, la página de inicio antigua) y no ven el panel nuevo. El plugin no inserta nada en esa página. Los editores y administradores siguen viendo la página de inicio nueva para probar los cambios.', 'area-familias'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="mantenimiento_page_id"><?php esc_html_e('Página de inicio durante el mantenimiento', 'area-familias'); ?></label></th>
                    <td>
                        <?php $af_page_select('mantenimiento_page_id', $s['mantenimiento_page_id'], __('— Elige una página —', 'area-familias')); ?>
                        <?php if ($s['mantenimiento_page_id']) : ?> <a href="<?php echo esc_url(get_permalink($s['mantenimiento_page_id'])); ?>" target="_blank" rel="noopener"><?php esc_html_e('Ver', 'area-familias'); ?></a><?php endif; ?>
                        <p class="description"><?php esc_html_e('Tiene que ser distinta de la página de inicio y de la de login. El acceso con la cuenta del colegio sigue funcionando igual.', 'area-familias'); ?></p>
                    </td>
                </tr>
            </table>
        </div>

        <div class="af-card">
            <h2><span class="dashicons dashicons-lock"></span> <?php esc_html_e('Acceso', 'area-familias'); ?></h2>
            <table class="form-table" role="presentation">
                <tr>
                    <th scope="row"><label for="allowed_domain"><?php esc_html_e('Dominio permitido', 'area-familias'); ?></label></th>
                    <td>
                        @<input type="text" name="allowed_domain" id="allowed_domain" class="regular-text" value="<?php echo esc_attr($s['allowed_domain']); ?>">
                        <p class="description"><?php esc_html_e('Solo las cuentas de este dominio pueden registrarse y entrar. Los editores y administradores de la web pueden usar cualquier cuenta.', 'area-familias'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e('Restringir toda la web', 'area-familias'); ?></th>
                    <td>
                        <label><input type="checkbox" name="restrict_site" value="1" <?php checked($s['restrict_site'], 'yes'); ?>> <?php esc_html_e('Solo se puede ver la web con sesión iniciada (excepto la página de login y las páginas públicas)', 'area-familias'); ?></label>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e('Páginas públicas', 'area-familias'); ?></th>
                    <td>
                        <div class="af-checklist">
                            <?php foreach ($pages as $p) : ?>
                                <label><input type="checkbox" name="public_page_ids[]" value="<?php echo esc_attr($p->ID); ?>" <?php checked(in_array((int) $p->ID, array_map('intval', (array) $s['public_page_ids']), true)); ?>> <?php echo esc_html($p->post_title); ?></label>
                            <?php endforeach; ?>
                        </div>
                        <p class="description"><?php esc_html_e('Páginas que se pueden ver sin iniciar sesión.', 'area-familias'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e('Escritorio de WordPress', 'area-familias'); ?></th>
                    <td>
                        <label><input type="checkbox" name="hide_admin_bar" value="1" <?php checked($s['hide_admin_bar'], 'yes'); ?>> <?php esc_html_e('Ocultar la barra de WordPress a las familias', 'area-familias'); ?></label><br>
                        <label><input type="checkbox" name="block_wp_admin" value="1" <?php checked($s['block_wp_admin'], 'yes'); ?>> <?php esc_html_e('No dejar entrar a las familias en /wp-admin', 'area-familias'); ?></label>
                    </td>
                </tr>
            </table>
        </div>

        <div class="af-card">
            <h2><span class="dashicons dashicons-admin-appearance"></span> <?php esc_html_e('Pantalla de acceso e imagen', 'area-familias'); ?></h2>
            <table class="form-table" role="presentation">
                <tr>
                    <th scope="row"><label for="login_titulo"><?php esc_html_e('Título', 'area-familias'); ?></label></th>
                    <td><input type="text" name="login_titulo" id="login_titulo" class="regular-text" value="<?php echo esc_attr($s['login_titulo']); ?>"></td>
                </tr>
                <tr>
                    <th scope="row"><label for="login_texto"><?php esc_html_e('Texto', 'area-familias'); ?></label></th>
                    <td><input type="text" name="login_texto" id="login_texto" class="large-text" value="<?php echo esc_attr($s['login_texto']); ?>"></td>
                </tr>
                <tr>
                    <th scope="row"><label for="login_boton"><?php esc_html_e('Texto del botón', 'area-familias'); ?></label></th>
                    <td><input type="text" name="login_boton" id="login_boton" class="regular-text" value="<?php echo esc_attr($s['login_boton']); ?>"></td>
                </tr>
                <tr>
                    <th scope="row"><label for="logo_url"><?php esc_html_e('Logotipo', 'area-familias'); ?></label></th>
                    <td>
                        <input type="url" name="logo_url" id="logo_url" class="large-text" value="<?php echo esc_attr($s['logo_url']); ?>">
                        <?php if ($s['logo_url']) : ?><p><img src="<?php echo esc_url($s['logo_url']); ?>" alt="" class="af-preview"></p><?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="tribanda_url"><?php esc_html_e('Tribanda corporativa', 'area-familias'); ?></label></th>
                    <td>
                        <input type="url" name="tribanda_url" id="tribanda_url" class="large-text" value="<?php echo esc_attr($s['tribanda_url']); ?>">
                        <p class="description"><?php esc_html_e('Se muestra girada y a todo el ancho, como en la web del colegio.', 'area-familias'); ?></p>
                    </td>
                </tr>
            </table>
        </div>

        <p><button type="submit" class="button button-primary button-large"><?php esc_html_e('Guardar ajustes', 'area-familias'); ?></button></p>
    </form>
</div>
