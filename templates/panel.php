<?php
/**
 * Panel de la familia (página de inicio del Área Familias).
 *
 * Variables: $user, $s, $familia (array|WP_Error), $msg, $nombre, $reservar,
 *            $enlaces, $espacios_html, $logout_url, $sin_telefono, $mantenimiento
 */

if (!defined('ABSPATH')) {
    exit;
}

$af_error = is_wp_error($familia) ? $familia->get_error_message() : '';
$af_ok = !$af_error && !empty($familia['encontrada']);
$af_hijos = $af_ok ? $familia['hijos'] : array();
$af_familias = $af_ok ? $familia['familias'] : array();
$af_hijo_nombre = array();
foreach ($af_hijos as $h) {
    $af_hijo_nombre[$h['id']] = $h['nombre'];
}
$af_iniciales = function ($n) {
    $p = preg_split('/\s+/', trim($n));
    return mb_strtoupper(mb_substr($p[0], 0, 1) . (isset($p[1]) ? mb_substr($p[1], 0, 1) : ''));
};
?>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800&display=swap" rel="stylesheet">

<style>
.afp {
    --afp-navy: #313280;
    --afp-navy-2: #4547a8;
    --afp-sky: #72a7f7;
    --afp-orange: #dc8e2b;
    --afp-ink: #1f2240;
    --afp-muted: #6b7091;
    --afp-line: #e3e6f2;
    --afp-bg: #f5f7fc;
    font-family: "Montserrat", -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
    color: var(--afp-ink);
    max-width: 1000px;
    margin: 0 auto;
    padding: 8px 0 40px;
    line-height: 1.5;
    box-sizing: border-box;
    text-align: left;
}
.afp *, .afp *::before, .afp *::after { box-sizing: border-box; }
.afp .afp-head {
    display: flex; align-items: center; justify-content: space-between; gap: 16px; flex-wrap: wrap;
    background: linear-gradient(120deg, var(--afp-navy) 0%, var(--afp-navy-2) 100%);
    color: #fff; border-radius: 18px; padding: 24px 28px; box-shadow: 0 10px 24px rgba(49, 50, 128, .22);
}
.afp .afp-kicker { margin: 0; font-size: 12px; font-weight: 700; letter-spacing: .14em; text-transform: uppercase; color: #ffd7a3; }
.afp .afp-hola { margin: 2px 0 4px; padding: 0; border: 0; font-family: inherit; font-size: 28px; line-height: 1.2; font-weight: 800; color: #fff; }
.afp .afp-saludo { margin: 0; font-size: 15px; color: #dfe4ff; max-width: 640px; }
.afp a.afp-salir { color: #fff; text-decoration: none; font-size: 14px; font-weight: 600; border: 1px solid rgba(255,255,255,.4); border-radius: 999px; padding: 8px 16px; white-space: nowrap; }
.afp a.afp-salir:hover { background: rgba(255,255,255,.15); color: #fff; }
.afp .afp-tabs { display: flex; flex-wrap: wrap; gap: 8px; margin: 20px 0; padding: 6px; background: #fff; border: 1px solid var(--afp-line); border-radius: 14px; }
.afp .afp-tab {
    display: inline-flex; align-items: center; gap: 8px; border: 0; background: transparent; cursor: pointer;
    font-family: inherit; font-size: 14px; font-weight: 700; color: var(--afp-muted); padding: 10px 16px; border-radius: 10px; text-decoration: none; line-height: 1.2;
}
.afp .afp-tab:hover { background: var(--afp-bg); color: var(--afp-navy); }
.afp .afp-tab.is-active { background: var(--afp-navy); color: #fff; }
.afp .afp-tab .afp-n { font-size: 11px; background: var(--afp-orange); color: #fff; border-radius: 999px; padding: 1px 7px; }
.afp .afp-sec[hidden] { display: none !important; }
.afp .afp-card { background: #fff; border: 1px solid var(--afp-line); border-radius: 16px; padding: 24px; box-shadow: 0 2px 10px rgba(49, 50, 128, .05); }
.afp .afp-card + .afp-card { margin-top: 16px; }
.afp .afp-h2 { margin: 0 0 4px; padding: 0; border: 0; font-family: inherit; font-size: 20px; font-weight: 800; color: var(--afp-navy); }
.afp .afp-sub { margin: 0 0 18px; font-size: 14px; color: var(--afp-muted); }
.afp .afp-hijos { display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: 14px; }
.afp .afp-hijo { border: 1px solid var(--afp-line); border-top: 4px solid var(--afp-navy); border-radius: 14px; padding: 18px; background: #fff; display: flex; flex-direction: column; gap: 10px; }
.afp .afp-hijo-top { display: flex; align-items: center; gap: 12px; }
.afp .afp-av { flex: 0 0 46px; width: 46px; height: 46px; border-radius: 50%; background: var(--afp-sky); color: #fff; font-weight: 800; display: flex; align-items: center; justify-content: center; position: relative; overflow: hidden; }
.afp .afp-av.is-small { flex-basis: 30px; width: 30px; height: 30px; font-size: 11px; background: var(--afp-orange); }
.afp .afp-av img { position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; margin: 0; border: 0; }
.afp .afp-hijo-nombre { font-size: 17px; font-weight: 800; color: var(--afp-ink); line-height: 1.25; }
.afp .afp-hijo-curso { font-size: 13px; font-weight: 600; color: var(--afp-muted); }
.afp .afp-tutor { display: flex; align-items: center; gap: 8px; font-size: 13px; color: var(--afp-muted); }
.afp .afp-tutor b { display: block; color: var(--afp-ink); font-weight: 600; }
.afp a.afp-btn, .afp button.afp-btn {
    display: inline-flex; align-items: center; justify-content: center; gap: 8px; border: 0; cursor: pointer;
    background: var(--afp-navy); color: #fff; text-decoration: none; font-family: inherit; font-size: 14px; font-weight: 700;
    padding: 10px 18px; border-radius: 999px; line-height: 1.2; transition: background .15s;
}
.afp a.afp-btn:hover, .afp button.afp-btn:hover { background: var(--afp-orange); color: #fff; }
.afp a.afp-btn.is-ghost { background: #fff; color: var(--afp-navy); box-shadow: inset 0 0 0 2px var(--afp-navy); }
.afp a.afp-btn.is-ghost:hover { background: var(--afp-navy); color: #fff; }
.afp .afp-msg { padding: 12px 16px; border-radius: 12px; margin: 0 0 16px; font-size: 14px; font-weight: 600; }
.afp .afp-msg.ok { background: #ecfdf5; color: #065f46; border: 1px solid #a7f3d0; }
.afp .afp-msg.error { background: #fdf0ee; color: #9a2c1c; border: 1px solid #f5c2b8; }
.afp .afp-field { margin: 0 0 16px; }
.afp .afp-field label { display: block; font-size: 13px; font-weight: 700; color: var(--afp-ink); margin-bottom: 6px; }
.afp .afp-field input[type="text"], .afp .afp-field input[type="tel"] {
    width: 100%; max-width: 560px; height: 46px; padding: 0 14px; font-family: inherit; font-size: 15px; color: var(--afp-ink);
    background: #fff; border: 2px solid var(--afp-line); border-radius: 12px; outline: none; margin: 0;
}
.afp .afp-field input:focus { border-color: var(--afp-sky); box-shadow: 0 0 0 4px rgba(114, 167, 247, .2); }
.afp .afp-field small { display: block; margin-top: 4px; font-size: 12px; color: var(--afp-muted); }
.afp .afp-nota { background: var(--afp-bg); border-left: 4px solid var(--afp-sky); border-radius: 10px; padding: 12px 14px; font-size: 13px; color: var(--afp-muted); margin: 0 0 18px; }
.afp .afp-chips { display: flex; flex-wrap: wrap; gap: 8px; margin: 12px 0 0; }
.afp .afp-chip { border: 2px solid var(--afp-line); border-radius: 999px; padding: 6px 14px; font-size: 13px; font-weight: 600; color: var(--afp-navy); background: #fff; }
.afp .afp-pronto { display: inline-block; font-size: 11px; font-weight: 800; letter-spacing: .08em; text-transform: uppercase; background: #fff3e2; color: #9a5a12; border-radius: 999px; padding: 3px 10px; margin-bottom: 8px; }
.afp .afp-enlaces { display: grid; grid-template-columns: repeat(auto-fill, minmax(240px, 1fr)); gap: 12px; }
.afp a.afp-enlace { display: block; border: 1px solid var(--afp-line); border-left: 4px solid var(--afp-orange); border-radius: 12px; padding: 14px 16px; text-decoration: none; color: var(--afp-ink); background: #fff; transition: box-shadow .15s, transform .15s; }
.afp a.afp-enlace:hover { box-shadow: 0 8px 18px rgba(49, 50, 128, .12); transform: translateY(-1px); color: var(--afp-ink); }
.afp .afp-enlace b { display: block; color: var(--afp-navy); font-size: 15px; }
.afp .afp-enlace span { font-size: 13px; color: var(--afp-muted); }
.afp .afp-vacio { color: var(--afp-muted); font-size: 15px; }
.afp .afp-aviso { display: flex; align-items: center; gap: 14px; flex-wrap: wrap; background: #fff8ec; border: 1px solid #f3cf98; border-left: 5px solid var(--afp-orange); border-radius: 14px; padding: 14px 18px; margin: 18px 0 0; }
.afp .afp-aviso p { flex: 1 1 280px; margin: 0; font-size: 15px; font-weight: 600; color: #7a4a10; }
.afp .afp-aviso-icon { flex: 0 0 32px; width: 32px; height: 32px; border-radius: 50%; background: var(--afp-orange); color: #fff; font-weight: 800; display: flex; align-items: center; justify-content: center; }
.afp .afp-n-alerta { background: #d64545; }
.afp .afp-field.is-falta input { border-color: #f3cf98; background: #fffdf8; }
.afp .afp-field.is-falta label em { color: #b45309; font-style: normal; font-weight: 600; }
.afp .afp-mant { background: #eef3fe; color: var(--afp-navy); border: 1px solid #c9d8fb; margin-top: 16px; }
.afp .afp-padre { border: 1px solid var(--afp-line); border-radius: 14px; padding: 6px 18px 2px; margin: 0 0 16px; }
.afp .afp-padre legend { padding: 0 8px; font-size: 15px; font-weight: 800; color: var(--afp-navy); }
.afp .afp-sec-espacios .apdf { padding: 0; max-width: none; }
@media (max-width: 600px) {
    .afp .afp-head { padding: 20px; }
    .afp .afp-hola { font-size: 23px; }
    .afp .afp-tab { flex: 1 1 calc(50% - 8px); justify-content: center; padding: 10px 8px; font-size: 13px; }
    .afp .afp-card { padding: 18px; }
}
</style>

<div class="afp">
    <header class="afp-head">
        <div>
            <p class="afp-kicker"><?php esc_html_e('Área de Familias', 'area-familias'); ?></p>
            <h1 class="afp-hola"><?php printf(esc_html__('Hola, %s', 'area-familias'), esc_html($nombre)); ?></h1>
            <?php if (trim((string) $s['panel_saludo']) !== '') : ?>
                <p class="afp-saludo"><?php echo esc_html($s['panel_saludo']); ?></p>
            <?php endif; ?>
        </div>
        <a class="afp-salir" href="<?php echo esc_url($logout_url); ?>"><?php esc_html_e('Salir', 'area-familias'); ?></a>
    </header>

    <?php if ($mantenimiento) : ?>
        <div class="afp-msg afp-mant"><?php esc_html_e('Modo mantenimiento activo: las familias no ven este panel (empiezan en la página de mantenimiento). Tú lo ves porque eres editor o administrador.', 'area-familias'); ?></div>
    <?php endif; ?>

    <?php if ($sin_telefono) : ?>
        <div class="afp-aviso" role="alert">
            <span class="afp-aviso-icon" aria-hidden="true">!</span>
            <p><?php echo esc_html(str_replace('{nombres}', implode(', ', $sin_telefono), (string) $s['panel_aviso_telefono'])); ?></p>
            <button type="button" class="afp-btn" data-ir="datos"><?php esc_html_e('Completar mis datos', 'area-familias'); ?></button>
        </div>
    <?php endif; ?>

    <nav class="afp-tabs" role="tablist">
        <button type="button" class="afp-tab is-active" data-sec="hijos"><?php esc_html_e('Mis hijos', 'area-familias'); ?><?php if ($af_hijos) : ?> <span class="afp-n"><?php echo count($af_hijos); ?></span><?php endif; ?></button>
        <button type="button" class="afp-tab" data-sec="datos"><?php esc_html_e('Mis datos', 'area-familias'); ?><?php if ($sin_telefono) : ?> <span class="afp-n afp-n-alerta">!</span><?php endif; ?></button>
        <button type="button" class="afp-tab" data-sec="reservar"><?php esc_html_e('Reservar un servicio', 'area-familias'); ?><?php if (!empty($reservar['proximas'])) : ?> <span class="afp-n" title="<?php esc_attr_e('Reservas pendientes', 'area-familias'); ?>"><?php echo (int) $reservar['proximas']; ?></span><?php endif; ?></button>
        <?php if ($enlaces) : ?>
            <button type="button" class="afp-tab" data-sec="enlaces"><?php esc_html_e('Enlaces de interés', 'area-familias'); ?></button>
        <?php endif; ?>
        <button type="button" class="afp-tab" data-sec="espacios"><?php esc_html_e('Espacios de aula', 'area-familias'); ?></button>
    </nav>

    <?php if ($msg) : ?>
        <div class="afp-msg <?php echo $msg['type'] === 'ok' ? 'ok' : 'error'; ?>" role="status"><?php echo esc_html($msg['text']); ?></div>
    <?php endif; ?>

    <?php if ($af_error) : ?>
        <div class="afp-msg error"><?php echo esc_html($af_error); ?></div>
    <?php elseif (!$af_ok) : ?>
        <div class="afp-card">
            <p class="afp-vacio"><?php printf(esc_html__('No encontramos ningún alumno asociado a tu cuenta (%s). Si crees que es un error, escribe a secretaría indicando el nombre de tus hijos.', 'area-familias'), esc_html($user->user_email)); ?></p>
        </div>
    <?php endif; ?>

    <!-- Mis hijos -->
    <section class="afp-sec" id="afp-hijos" data-sec="hijos">
        <?php if ($af_hijos) : ?>
            <div class="afp-card">
                <h2 class="afp-h2"><?php esc_html_e('Mis hijos', 'area-familias'); ?></h2>
                <p class="afp-sub"><?php esc_html_e('Curso, tutor o tutora y espacio de aula de cada uno.', 'area-familias'); ?></p>
                <div class="afp-hijos">
                    <?php foreach ($af_hijos as $h) : ?>
                        <article class="afp-hijo">
                            <div class="afp-hijo-top">
                                <span class="afp-av" aria-hidden="true"><?php echo esc_html($af_iniciales($h['nombre'])); ?></span>
                                <div>
                                    <div class="afp-hijo-nombre"><?php echo esc_html($h['nombre']); ?></div>
                                    <div class="afp-hijo-curso"><?php echo esc_html(trim($h['curso_label'] . ($h['etapa'] ? ' · ' . $h['etapa'] : ''))); ?></div>
                                </div>
                            </div>
                            <?php if (!empty($h['tutor']['nombre'])) : ?>
                                <div class="afp-tutor">
                                    <span class="afp-av is-small" aria-hidden="true"><?php echo esc_html($h['tutor']['iniciales']); ?><?php if (!empty($h['tutor']['foto'])) : ?><img src="<?php echo esc_url($h['tutor']['foto']); ?>" alt="" loading="lazy" onerror="this.remove()"><?php endif; ?></span>
                                    <span><?php esc_html_e('Tutor/a', 'area-familias'); ?><b><?php echo esc_html($h['tutor']['nombre']); ?></b></span>
                                </div>
                            <?php endif; ?>
                            <?php if (!empty($h['espacio']['chat_url'])) : ?>
                                <a class="afp-btn" href="<?php echo esc_url($h['espacio']['chat_url']); ?>" target="_blank" rel="noopener"><?php esc_html_e('Abrir su espacio de aula', 'area-familias'); ?></a>
                            <?php endif; ?>
                        </article>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </section>

    <!-- Mis datos -->
    <section class="afp-sec" id="afp-datos" data-sec="datos" hidden>
        <?php foreach ($af_familias as $f) : ?>
            <div class="afp-card">
                <h2 class="afp-h2"><?php esc_html_e('Mis datos', 'area-familias'); ?></h2>
                <p class="afp-sub">
                    <?php
                    $nh = array_filter(array_map(function ($id) use ($af_hijo_nombre) {
                        return isset($af_hijo_nombre[$id]) ? $af_hijo_nombre[$id] : '';
                    }, $f['hijos']));
                    echo esc_html($nh ? sprintf(__('Datos de contacto de la familia de %s.', 'area-familias'), implode(', ', $nh)) : __('Datos de contacto de tu familia.', 'area-familias'));
                    ?>
                </p>
                <div class="afp-nota"><?php esc_html_e('Si cambias algún teléfono o dirección, se actualiza al momento en el colegio y avisamos a la tutora o el tutor y a secretaría.', 'area-familias'); ?></div>
                <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                    <input type="hidden" name="action" value="af_guardar_datos">
                    <input type="hidden" name="familia_id" value="<?php echo esc_attr($f['id']); ?>">
                    <?php wp_nonce_field('af_guardar_datos'); ?>
                    <?php foreach ($f['contactos'] as $c) : ?>
                        <fieldset class="afp-padre">
                            <legend><?php echo esc_html($c['nombre']); ?></legend>
                            <div class="afp-field<?php echo trim((string) (isset($c['telefono']) ? $c['telefono'] : '')) === '' ? ' is-falta' : ''; ?>">
                                <label for="afp-tel-<?php echo esc_attr($c['id']); ?>"><?php esc_html_e('Teléfono de emergencia', 'area-familias'); ?><?php if (trim((string) (isset($c['telefono']) ? $c['telefono'] : '')) === '') : ?> <em><?php esc_html_e('(falta)', 'area-familias'); ?></em><?php endif; ?></label>
                                <input type="tel" id="afp-tel-<?php echo esc_attr($c['id']); ?>" name="telefono[<?php echo esc_attr($c['id']); ?>]" value="<?php echo esc_attr(isset($c['telefono']) ? $c['telefono'] : ''); ?>" placeholder="<?php esc_attr_e('Ej.: 600 123 456', 'area-familias'); ?>">
                            </div>
                            <div class="afp-field">
                                <label for="afp-dir-<?php echo esc_attr($c['id']); ?>"><?php esc_html_e('Dirección', 'area-familias'); ?></label>
                                <input type="text" id="afp-dir-<?php echo esc_attr($c['id']); ?>" name="direccion[<?php echo esc_attr($c['id']); ?>]" value="<?php echo esc_attr($c['direccion']); ?>">
                            </div>
                        </fieldset>
                    <?php endforeach; ?>
                    <?php if (!empty($f['telefono'])) : ?>
                        <p class="afp-sub"><?php printf(esc_html__('Teléfono que teníamos de la familia: %s. Escríbelo en el padre o la madre que corresponda.', 'area-familias'), esc_html($f['telefono'])); ?></p>
                    <?php endif; ?>
                    <button type="submit" class="afp-btn"><?php esc_html_e('Guardar cambios', 'area-familias'); ?></button>
                </form>
            </div>
        <?php endforeach; ?>
    </section>

    <!-- Reservar un servicio -->
    <section class="afp-sec" id="afp-reservar" data-sec="reservar" hidden>
        <?php echo $reservar['html']; // phpcs:ignore WordPress.Security.EscapeOutput -- generated by AF_Reservas ?>
    </section>

    <!-- Espacios de aula -->
    <section class="afp-sec afp-sec-espacios" id="afp-espacios" data-sec="espacios" hidden>
        <div class="afp-card">
            <h2 class="afp-h2"><?php esc_html_e('Espacios de aula', 'area-familias'); ?></h2>
            <?php echo $espacios_html; // phpcs:ignore WordPress.Security.EscapeOutput -- generated by AF_Espacios ?>
        </div>
    </section>

    <?php if ($enlaces) : ?>
        <!-- Enlaces de interés -->
        <section class="afp-sec" id="afp-enlaces" data-sec="enlaces" hidden>
            <div class="afp-card">
                <h2 class="afp-h2"><?php esc_html_e('Enlaces de interés', 'area-familias'); ?></h2>
                <p class="afp-sub"><?php esc_html_e('Accesos directos a información y servicios del colegio.', 'area-familias'); ?></p>
                <div class="afp-enlaces">
                    <?php foreach ($enlaces as $e) : ?>
                        <a class="afp-enlace" href="<?php echo esc_url($e['url']); ?>" target="_blank" rel="noopener">
                            <b><?php echo esc_html($e['titulo']); ?> ↗</b>
                            <?php if ($e['desc'] !== '') : ?><span><?php echo esc_html($e['desc']); ?></span><?php endif; ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
    <?php endif; ?>
</div>

<script>
(function () {
    var root = document.querySelector('.afp');
    if (!root) { return; }
    var show = function (sec) {
        var btn = root.querySelector('.afp-tab[data-sec="' + sec + '"]');
        if (!btn) { return; }
        root.querySelectorAll('.afp-tab[data-sec]').forEach(function (b) { b.classList.toggle('is-active', b === btn); });
        root.querySelectorAll('.afp-sec').forEach(function (s) { s.hidden = s.getAttribute('data-sec') !== sec; });
    };
    root.querySelectorAll('.afp-tab[data-sec]').forEach(function (b) {
        b.addEventListener('click', function () {
            show(b.getAttribute('data-sec'));
            history.replaceState(null, '', '#' + b.getAttribute('data-sec'));
        });
    });
    // Buttons that open an option (e.g. "Completar mis datos").
    root.querySelectorAll('[data-ir]').forEach(function (b) {
        b.addEventListener('click', function () {
            show(b.getAttribute('data-ir'));
            history.replaceState(null, '', '#' + b.getAttribute('data-ir'));
            var t = root.querySelector('.afp-tabs');
            if (t) { t.scrollIntoView({ behavior: 'smooth', block: 'start' }); }
        });
    });
    if (location.hash) { show(location.hash.slice(1)); }
    window.addEventListener('hashchange', function () { if (location.hash) { show(location.hash.slice(1)); } });
})();
</script>
