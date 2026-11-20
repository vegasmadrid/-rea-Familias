<?php
/**
 * Script of the "Reservar un servicio" form. Printed in the footer (not inside
 * the page content, where WordPress filters would alter it).
 *
 * Variables: $festivos
 */

if (!defined('ABSPATH')) {
    exit;
}
?>
    <script>
    (function () {
        var form = document.getElementById('afp-reservar-form');
        if (!form) { return; }
        var festivos = <?php echo wp_json_encode(array_values($festivos)); ?>;
        var etapaNombres = <?php echo wp_json_encode(AF_Reservas::etapas()); ?>;
        var $ = function (s, ctx) { return (ctx || form).querySelector(s); };
        var $$ = function (s, ctx) { return Array.prototype.slice.call((ctx || form).querySelectorAll(s)); };
        var val = function (name) { var el = $('input[name="' + name + '"]:checked'); return el ? el.value : ''; };
        var show = function (el, on) {
            if (!el) { return; }
            el.hidden = !on;
            $$('input, select, textarea', el).forEach(function (i) { i.disabled = !on; });
        };
        var fmt = function (d) {
            if (!d) { return ''; }
            try { return new Date(d + 'T12:00:00').toLocaleDateString('es-ES', { weekday: 'long', day: 'numeric', month: 'long' }); } catch (e) { return d; }
        };
        var esLectivo = function (d) {
            var w = new Date(d + 'T12:00:00').getDay();
            return w !== 0 && w !== 6 && festivos.indexOf(d) === -1;
        };

        function update() {
            // Selected children and services available for all of them.
            var hijos = $$('input[name="alumnos[]"]:checked');
            $$('.afp-opt').forEach(function (l) { var i = $('input', l); l.classList.toggle('is-on', i.checked); });
            $$('input[name="servicio"]').forEach(function (r) {
                var etapas = r.getAttribute('data-etapas') ? r.getAttribute('data-etapas').split(',') : [];
                var fuera = etapas.length ? hijos.filter(function (h) { return etapas.indexOf(h.getAttribute('data-etapa')) === -1; }) : [];
                var label = r.closest('.afp-opt');
                var note = $('.afp-opt-no', label);
                label.classList.toggle('is-off', fuera.length > 0);
                r.disabled = fuera.length > 0;
                if (fuera.length) {
                    note.hidden = false;
                    note.textContent = 'No disponible para ' + fuera.map(function (h) { return h.getAttribute('data-nombre').split(' ')[0]; }).join(', ') + ' (solo ' + etapas.map(function (e) { return etapaNombres[e] || e; }).join(', ') + ')';
                    if (r.checked) { r.checked = false; label.classList.remove('is-on'); }
                } else {
                    note.hidden = true;
                }
            });

            var s = $('input[name="servicio"]:checked');
            var k = s ? s.value : '';
            $('#afp-paso-fecha').hidden = !k;
            $('#afp-paso-datos').hidden = !k;
            if (!k) { return; }

            // Dates
            var varios = s.getAttribute('data-varios') === '1';
            show($('#afp-modo'), varios);
            if (!varios) { $('input[name="modo"][value="dia"]').checked = true; }
            var modo = varios ? val('modo') || 'dia' : 'dia';
            show($('#afp-un-dia'), modo === 'dia');
            show($('#afp-varios'), modo === 'varios');
            var min = s.getAttribute('data-min');
            ['#afp-fecha', '#afp-desde', '#afp-hasta'].forEach(function (id) { $(id).min = min; });
            $$('[data-serv]').forEach(function (el) { el.hidden = el.getAttribute('data-serv') !== k; });

            // Questions of each service
            var dieta = k === 'dieta' || (k === 'comedor' && s.getAttribute('data-dieta') === '1' && val('dieta') === 'si');
            show($('[data-blk="desayuno"]'), k === 'ampliacion' && s.getAttribute('data-desayuno') === '1');
            show($('[data-blk="dieta-pregunta"]'), k === 'comedor' && s.getAttribute('data-dieta') === '1');
            show($('[data-blk="dieta"]'), dieta);
            show($('[data-blk="hora"]'), k === 'salida' || k === 'retraso');
            show($('[data-blk="recoge"]'), k === 'salida' && s.getAttribute('data-recoge') === '1');
            show($('[data-blk="motivo"]'), dieta || k === 'salida' || k === 'retraso');
            if (k === 'salida' || k === 'retraso') {
                var lab = $('[data-blk="hora"] label');
                lab.textContent = lab.getAttribute('data-l-' + k);
                var h = $('#afp-hora');
                h.min = s.getAttribute('data-hmin') || '';
                h.max = s.getAttribute('data-hmax') || '';
                $('.afp-hora-rango').textContent = (h.min && h.max) ? 'Entre las ' + h.min + ' y las ' + h.max + '.' : '';
            }

            // Summary
            var nombres = hijos.map(function (h) { return h.getAttribute('data-nombre').split(' ')[0]; });
            var txt = '';
            if (nombres.length) {
                txt = s.getAttribute('data-nombre') + ' para ' + nombres.join(' y ');
                if (modo === 'dia' && $('#afp-fecha').value) {
                    txt += ', el ' + fmt($('#afp-fecha').value);
                    if (!esLectivo($('#afp-fecha').value)) { txt += ' — ⚠ ese día no hay clase'; }
                } else if (modo === 'varios' && $('#afp-desde').value && $('#afp-hasta').value) {
                    var dias = $$('input[name="dias[]"]:checked').map(function (c) { return c.parentNode.textContent.trim().toLowerCase(); });
                    txt += ', del ' + fmt($('#afp-desde').value) + ' al ' + fmt($('#afp-hasta').value) + (dias.length && dias.length < 5 ? ' (solo ' + dias.join(', ') + ')' : '');
                }
                if ((k === 'salida' || k === 'retraso') && $('#afp-hora').value) { txt += (k === 'salida' ? ', sale a las ' : ', llega a las ') + $('#afp-hora').value; }
                txt += '.';
            }
            $('#afp-resumen').textContent = txt ? 'Vas a reservar: ' + txt : '';
        }

        form.addEventListener('change', update);
        form.addEventListener('input', update);
        $('#afp-desde').addEventListener('change', function () { if (!$('#afp-hasta').value || $('#afp-hasta').value < this.value) { $('#afp-hasta').value = this.value; } $('#afp-hasta').min = this.value; });
        form.addEventListener('submit', function (e) {
            var err = '';
            var s = $('input[name="servicio"]:checked');
            if (!$$('input[name="alumnos[]"]:checked').length) { err = 'Elige para qué hijo o hija es la reserva.'; }
            else if (!s) { err = 'Elige el servicio.'; }
            else if (!$('#afp-un-dia').hidden && !$('#afp-fecha').value) { err = 'Indica el día.'; }
            else if (!$('#afp-un-dia').hidden && !esLectivo($('#afp-fecha').value)) { err = 'Ese día no hay clase. Elige otro día.'; }
            else if (!$('#afp-varios').hidden && (!$('#afp-desde').value || !$('#afp-hasta').value)) { err = 'Indica el primer y el último día.'; }
            else if (!$('[data-blk="desayuno"]').hidden && !val('desayuno')) { err = 'Indica si quieres desayuno.'; }
            else if (!$('[data-blk="dieta-pregunta"]').hidden && !val('dieta')) { err = 'Indica si necesita dieta.'; }
            else if (!$('[data-blk="hora"]').hidden && !$('#afp-hora').value) { err = 'Indica la hora.'; }
            if (err) { e.preventDefault(); alert(err); return; }
            var b = $('button[type="submit"]');
            setTimeout(function () { b.disabled = true; b.textContent = 'Reservando…'; }, 0);
        });
        update();
    })();
    </script>
