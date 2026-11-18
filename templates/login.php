<?php
/**
 * Pantalla de acceso del Área Familias.
 *
 * Variables: $s (ajustes), $google_url, $domain, $error, $user, $start_url, $logout_url
 */

if (!defined('ABSPATH')) {
    exit;
}
?>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800&display=swap" rel="stylesheet">

<style>
.aflg {
    --aflg-navy: #313280;
    --aflg-sky: #72a7f7;
    --aflg-orange: #dc8e2b;
    --aflg-ink: #1f2240;
    --aflg-muted: #6b7091;
    --aflg-line: #e3e6f2;
    font-family: "Montserrat", -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
    color: var(--aflg-ink);
    line-height: 1.5;
    box-sizing: border-box;
    padding-bottom: 40px;
}
.aflg *, .aflg *::before, .aflg *::after { box-sizing: border-box; }
html:has(.aflg), body:has(.aflg) { overflow-x: clip; }
.aflg .aflg-bleed { position: relative; width: 100vw; max-width: 100vw; left: 50%; margin-left: -50vw; }
.aflg .aflg-header { background: #fff; padding: 10px 12px; }
.aflg .aflg-logo { display: block; width: auto; height: auto; max-width: 170px; max-height: 100px; margin: 0; border: 0; }
.aflg .aflg-tribanda { line-height: 0; overflow: hidden; margin-bottom: 40px; }
.aflg .aflg-tribanda img { display: block; width: 100%; max-width: none; height: 36px; object-fit: fill; transform: rotate(180deg); margin: 0; border: 0; }
.aflg .aflg-card {
    max-width: 460px;
    margin: 0 auto;
    padding: 34px 30px 30px;
    background: #fff;
    border: 1px solid var(--aflg-line);
    border-top: 5px solid var(--aflg-navy);
    border-radius: 18px;
    box-shadow: 0 12px 30px rgba(49, 50, 128, .10);
    text-align: center;
}
.aflg .aflg-kicker { margin: 0 0 4px; font-size: 12px; font-weight: 700; letter-spacing: .14em; text-transform: uppercase; color: var(--aflg-orange); }
.aflg .aflg-title { margin: 0 0 10px; padding: 0; border: 0; font-family: inherit; font-size: 30px; line-height: 1.15; font-weight: 800; text-transform: uppercase; color: var(--aflg-sky); }
.aflg .aflg-text { margin: 0 0 24px; font-size: 15px; color: var(--aflg-muted); }
.aflg a.aflg-btn {
    display: flex; align-items: center; justify-content: center; gap: 10px;
    width: 100%; padding: 15px 18px; border-radius: 999px;
    background: var(--aflg-navy); color: #fff; text-decoration: none;
    font-family: inherit; font-size: 16px; font-weight: 700; line-height: 1.2;
    box-shadow: 0 6px 16px rgba(49, 50, 128, .25);
    transition: background .15s, transform .15s;
}
.aflg a.aflg-btn:hover, .aflg a.aflg-btn:focus { background: var(--aflg-orange); color: #fff; transform: translateY(-1px); }
.aflg .aflg-domain { margin: 16px 0 0; font-size: 13px; color: var(--aflg-muted); }
.aflg .aflg-domain span { display: inline-block; padding: 2px 10px; border-radius: 999px; background: #eef3fe; color: var(--aflg-navy); font-weight: 700; }
.aflg .aflg-error { margin: 0 0 20px; padding: 12px 14px; border-radius: 12px; background: #fdf0ee; color: #9a2c1c; font-size: 14px; text-align: left; }
.aflg .aflg-ok { margin: 0 0 18px; font-size: 15px; }
.aflg a.aflg-link { display: inline-block; margin-top: 16px; font-size: 13px; color: var(--aflg-muted); text-decoration: underline; }
@media (max-width: 600px) {
    .aflg .aflg-logo { max-width: 110px; max-height: 70px; }
    .aflg .aflg-tribanda { margin-bottom: 24px; }
    .aflg .aflg-tribanda img { height: 24px; }
    .aflg .aflg-card { padding: 26px 20px 22px; margin: 0 4px; }
    .aflg .aflg-title { font-size: 24px; }
}
</style>

<div class="aflg">
    <div class="aflg-bleed aflg-header">
        <?php if (!empty($s['logo_url'])) : ?>
            <img class="aflg-logo" src="<?php echo esc_url($s['logo_url']); ?>" alt="<?php esc_attr_e('Colegio Casa de la Virgen', 'area-familias'); ?>">
        <?php endif; ?>
    </div>
    <?php if (!empty($s['tribanda_url'])) : ?>
        <div class="aflg-bleed aflg-tribanda" aria-hidden="true"><img src="<?php echo esc_url($s['tribanda_url']); ?>" alt=""></div>
    <?php endif; ?>

    <div class="aflg-card">
        <p class="aflg-kicker"><?php esc_html_e('Colegio Casa de la Virgen', 'area-familias'); ?></p>
        <h1 class="aflg-title"><?php echo esc_html($s['login_titulo']); ?></h1>

        <?php if ($user) : ?>
            <p class="aflg-ok"><?php printf(esc_html__('Has iniciado sesión como %s.', 'area-familias'), '<strong>' . esc_html($user->display_name) . '</strong>'); ?></p>
            <a class="aflg-btn" href="<?php echo esc_url($start_url); ?>"><?php esc_html_e('Entrar en el Área de Familias', 'area-familias'); ?> &rarr;</a>
            <a class="aflg-link" href="<?php echo esc_url($logout_url); ?>"><?php esc_html_e('Cerrar sesión / cambiar de cuenta', 'area-familias'); ?></a>
        <?php else : ?>
            <?php if ($s['login_texto'] !== '') : ?>
                <p class="aflg-text"><?php echo esc_html($s['login_texto']); ?></p>
            <?php endif; ?>
            <?php if ($error === 'dominio') : ?>
                <div class="aflg-error" role="alert">
                    <?php printf(esc_html__('Esa cuenta no puede entrar. Usa la cuenta del colegio que termina en @%s.', 'area-familias'), esc_html($domain)); ?>
                </div>
            <?php endif; ?>
            <a class="aflg-btn" href="<?php echo esc_url($google_url); ?>">
                <svg viewBox="0 0 24 24" width="20" height="20" aria-hidden="true" focusable="false"><path fill="currentColor" d="M12 2a5 5 0 0 1 5 5v2h1a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2v-9a2 2 0 0 1 2-2h1V7a5 5 0 0 1 5-5Zm0 2a3 3 0 0 0-3 3v2h6V7a3 3 0 0 0-3-3Z"/></svg>
                <?php echo esc_html($s['login_boton']); ?>
            </a>
            <?php if ($domain !== '') : ?>
                <p class="aflg-domain"><?php esc_html_e('Solo con cuentas', 'area-familias'); ?> <span>@<?php echo esc_html($domain); ?></span></p>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>
