/**
 * Área Familias · Solicitud de matrícula.
 * Steps, validation (DNI/NIE, IBAN…), repeatable blocks, level suggested by the
 * year of birth, copy of the payer and a draft kept on this device (without ID
 * numbers nor IBAN). Without JavaScript the form still works (one long page).
 */
(function () {
    'use strict';

    var LS = 'afm_borrador_v1';
    var raiz = document.getElementById('afm');
    if (!raiz) {
        return;
    }

    // Sent: forget the draft.
    if (raiz.querySelector('[data-borrar-borrador]')) {
        try { localStorage.removeItem(LS); } catch (e) {}
        return;
    }

    var form = raiz.querySelector('.afm-form');
    if (!form) {
        return;
    }
    raiz.classList.add('is-js');

    var pasos = Array.prototype.slice.call(form.querySelectorAll('.afm-paso'));
    var indicador = raiz.querySelector('.afm-pasos');
    var nav = form.querySelector('.afm-nav');
    var btnAnt = form.querySelector('[data-anterior]');
    var btnSig = form.querySelector('[data-siguiente]');
    var actual = 0;
    var niveles = {};
    try { niveles = JSON.parse(form.getAttribute('data-niveles') || '{}'); } catch (e) {}

    function $$(sel, el) { return Array.prototype.slice.call((el || form).querySelectorAll(sel)); }
    function visible(el) { return !!(el.offsetWidth || el.offsetHeight || el.getClientRects().length); }

    /* ------------------------------------------------------------------
     * Validación
     * ---------------------------------------------------------------- */

    var LETRAS = 'TRWAGMYFPDXBNJZSQVHLCKE';
    function limpiaDoc(v) { return (v || '').toUpperCase().replace(/[\s\-.]/g, ''); }
    function dniOk(tipo, v) {
        v = limpiaDoc(v);
        var m;
        if (tipo === 'DNI') {
            m = v.match(/^(\d{8})([A-Z])$/);
            return !!m && LETRAS[parseInt(m[1], 10) % 23] === m[2];
        }
        if (tipo === 'NIE') {
            m = v.match(/^([XYZ])(\d{7})([A-Z])$/);
            return !!m && LETRAS[parseInt('XYZ'.indexOf(m[1]) + m[2], 10) % 23] === m[3];
        }
        return /^[A-Z0-9]{5,20}$/.test(v);
    }
    function ibanOk(v) {
        v = (v || '').toUpperCase().replace(/[^A-Z0-9]/g, '');
        if (!/^[A-Z]{2}\d{2}[A-Z0-9]{10,30}$/.test(v) || (v.slice(0, 2) === 'ES' && v.length !== 24)) {
            return false;
        }
        var r = v.slice(4) + v.slice(0, 4), num = '', i, mod = 0;
        for (i = 0; i < r.length; i++) {
            var c = r.charCodeAt(i);
            num += (c >= 65 && c <= 90) ? String(c - 55) : r[i];
        }
        for (i = 0; i < num.length; i += 7) {
            mod = parseInt(String(mod) + num.slice(i, i + 7), 10) % 97;
        }
        return mod === 1;
    }
    function telOk(v) {
        var d = (v || '').replace(/\D/g, '');
        return d.length >= 9 && d.length <= 15;
    }

    function ponError(campo, msg) {
        var box = campo.closest('.afm-campo');
        if (!box) {
            return;
        }
        var e = box.querySelector('.afm-error');
        if (e) { e.textContent = msg || ''; }
        box.classList.toggle('has-error', !!msg);
        $$('input,select,textarea', box).forEach(function (i) {
            if (msg) { i.setAttribute('aria-invalid', 'true'); } else { i.removeAttribute('aria-invalid'); }
        });
    }

    /** Error of one field ('' if it is right). */
    function errorDe(el) {
        if (el.disabled || el.type === 'hidden' || !visible(el)) {
            return '';
        }
        var v = (el.value || '').trim();
        if (el.type === 'radio') {
            if (!el.required) { return ''; }
            var grupo = $$('input[type="radio"][name="' + el.name + '"]');
            return grupo.some(function (r) { return r.checked; }) ? '' : 'Elige una opción.';
        }
        if (el.type === 'checkbox') {
            return (el.required && !el.checked) ? 'Hay que marcar esta casilla.' : '';
        }
        if (el.required && v === '') {
            return 'Este dato es obligatorio.';
        }
        if (v === '') {
            return '';
        }
        if (el.type === 'email' && !/^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test(v)) {
            return 'El correo no es válido.';
        }
        if (el.type === 'tel' && !telOk(v)) {
            return 'Escribe un teléfono válido.';
        }
        if (el.type === 'date' && !/^\d{4}-\d{2}-\d{2}$/.test(v)) {
            return 'Escribe una fecha válida.';
        }
        if (/\[cp\]$/.test(el.name) && !/^\d{5}$/.test(v)) {
            return 'El código postal tiene 5 números.';
        }
        if (el.hasAttribute('data-doc')) {
            var tipoSel = el.closest('.afm-tutor').querySelector('[data-doc-tipo]');
            var tipo = tipoSel ? tipoSel.value : 'DNI';
            if (!dniOk(tipo, v)) {
                return tipo === 'PAS' ? 'Escribe el número de pasaporte (solo letras y números).' : 'El ' + tipo + ' no es correcto: revisa los números y la letra.';
            }
        }
        if (el.hasAttribute('data-nif') && !dniOk(/^[XYZ]/i.test(v) ? 'NIE' : 'DNI', v)) {
            return 'Escribe un DNI o NIE válido.';
        }
        if (el.hasAttribute('data-iban') && !ibanOk(v)) {
            return 'El IBAN no es correcto: revisa los 24 caracteres (empieza por ES).';
        }
        if (el.name === 'convivientes' && (parseInt(v, 10) < 2 || parseInt(v, 10) > 30)) {
            return 'Indica cuántas personas viven en la casa (contando al alumno/a).';
        }
        return '';
    }

    function validar(cont) {
        var primero = null, vistos = {};
        $$('input,select,textarea', cont).forEach(function (el) {
            if (el.name === 'web' || el.type === 'hidden') { return; }
            if (el.type === 'radio') {
                if (vistos[el.name]) { return; }
                vistos[el.name] = true;
            }
            var msg = errorDe(el);
            ponError(el, msg);
            if (msg && !primero) { primero = el; }
        });
        if (primero) {
            primero.focus();
            var box = primero.closest('.afm-campo');
            if (box && box.scrollIntoView) { box.scrollIntoView({ block: 'center', behavior: 'smooth' }); }
        }
        return !primero;
    }

    // Errors are shown when pressing "Siguiente" and disappear as soon as the value is right
    // (while typing, not on blur: a message that disappears on blur moves the buttons under the
    // pointer and the click is lost).
    function revisar(ev) {
        var el = ev.target;
        var box = el.closest && el.closest('.afm-campo');
        if (box && box.classList.contains('has-error') && errorDe(el) === '') {
            ponError(el, '');
        }
    }
    form.addEventListener('input', revisar);
    form.addEventListener('change', revisar);
    form.addEventListener('focusout', function (ev) {
        var el = ev.target;
        if (el.hasAttribute && el.hasAttribute('data-iban') && el.value) {
            var v = el.value.toUpperCase().replace(/[^A-Z0-9]/g, '');
            el.value = v.replace(/(.{4})/g, '$1 ').trim();
        }
        if (el.hasAttribute && (el.hasAttribute('data-doc') || el.hasAttribute('data-nif'))) {
            el.value = limpiaDoc(el.value);
        }
    });

    /* ------------------------------------------------------------------
     * Pasos
     * ---------------------------------------------------------------- */

    function ir(n, foco) {
        actual = Math.max(0, Math.min(pasos.length - 1, n));
        pasos.forEach(function (p, i) { p.classList.toggle('is-actual', i === actual); });
        if (indicador) {
            $$('li', indicador).forEach(function (li, i) {
                li.classList.toggle('is-actual', i === actual);
                li.classList.toggle('is-hecho', i < actual);
                if (i === actual) { li.setAttribute('aria-current', 'step'); } else { li.removeAttribute('aria-current'); }
            });
        }
        btnAnt.hidden = actual === 0;
        btnSig.hidden = actual === pasos.length - 1;
        sugerirContacto();
        if (actual === pasos.length - 1) { resumen(); }
        if (foco !== false) {
            var h = pasos[actual].querySelector('h2');
            if (h) { h.setAttribute('tabindex', '-1'); h.focus(); }
            raiz.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    }

    if (indicador) {
        indicador.hidden = false;
        $$('li', indicador).forEach(function (li, i) {
            li.addEventListener('click', function () {
                // Back freely; forward only if the steps in between are right.
                if (i <= actual) { ir(i); return; }
                for (var k = actual; k < i; k++) {
                    ir(k, false);
                    if (!validar(pasos[k])) { return; }
                }
                ir(i);
            });
        });
    }
    nav.hidden = false;
    btnAnt.addEventListener('click', function () { ir(actual - 1); });
    btnSig.addEventListener('click', function () {
        if (validar(pasos[actual])) { ir(actual + 1); }
    });
    form.addEventListener('submit', function (ev) {
        for (var k = 0; k < pasos.length; k++) {
            if (!validar(pasos[k])) {
                ev.preventDefault();
                ir(k, false);
                validar(pasos[k]);
                return;
            }
        }
        // Format the IBAN without spaces is done on the server; keep the draft without sensitive data.
        guardar();
        var b = form.querySelector('button[type="submit"]');
        if (b) { b.disabled = true; b.textContent = 'Enviando…'; }
    });

    /* ------------------------------------------------------------------
     * Bloques repetibles
     * ---------------------------------------------------------------- */

    function renumerar(lista) {
        $$('.afm-repetible', lista).forEach(function (b, i) {
            var n = b.querySelector('.afm-num');
            if (n) { n.textContent = String(i + 1); }
        });
    }
    function anadir(tipo) {
        var lista = form.querySelector('[data-lista="' + tipo + '"]');
        var tpl = form.querySelector('template[data-plantilla="' + tipo + '"]');
        var max = -1;
        $$('.afm-repetible', lista).forEach(function (b) { max = Math.max(max, parseInt(b.getAttribute('data-i'), 10) || 0); });
        var html = tpl.innerHTML.replace(/__i__/g, String(max + 1));
        var tmp = document.createElement('div');
        tmp.innerHTML = html;
        var bloque = tmp.firstElementChild;
        lista.appendChild(bloque);
        renumerar(lista);
        return bloque;
    }
    form.addEventListener('click', function (ev) {
        var t = ev.target;
        if (t.matches('[data-anadir]')) {
            var b = anadir(t.getAttribute('data-anadir'));
            var f = b.querySelector('input,select');
            if (f) { f.focus(); }
        } else if (t.matches('[data-quitar]')) {
            var bloque = t.closest('.afm-repetible');
            var lista = bloque.parentNode;
            if ($$('.afm-repetible', lista).length > 1) {
                bloque.remove();
                renumerar(lista);
            } else {
                $$('input:not([type="radio"]),select,textarea', bloque).forEach(function (i) { i.value = ''; });
            }
            guardarPronto();
        } else if (t.matches('[data-copiar-pagador]')) {
            copiarPagador(t.getAttribute('data-copiar-pagador'));
        }
    });

    /* ------------------------------------------------------------------
     * Ayudas
     * ---------------------------------------------------------------- */

    function campo(nombre) { return form.querySelector('[name="' + nombre + '"]'); }
    function v(nombre) { var c = campo(nombre); return c ? c.value.trim() : ''; }
    function pon(nombre, valor) { var c = campo(nombre); if (c) { c.value = valor; ponError(c, ''); } }

    function copiarPagador(i) {
        var calle = [v('domicilio[calle]'), v('domicilio[numero]'), v('domicilio[piso]'), v('domicilio[puerta]')].filter(Boolean).join(' ');
        pon('pago[pagador][nombre]', (v('tutores[' + i + '][nombre]') + ' ' + v('tutores[' + i + '][apellidos]')).trim());
        if (v('tutores[' + i + '][documento_tipo]') !== 'PAS') {
            pon('pago[pagador][documento]', v('tutores[' + i + '][documento]'));
        }
        pon('pago[pagador][telefono]', v('tutores[' + i + '][movil]'));
        pon('pago[pagador][direccion]', calle);
        pon('pago[pagador][cp]', v('domicilio[cp]'));
        pon('pago[pagador][localidad]', v('domicilio[localidad]'));
        pon('pago[pagador][provincia]', v('domicilio[provincia]'));
    }

    // Only one guardian: hide (and do not send) the second one and the "separated" box.
    var soloUno = form.querySelector('[data-solo-uno]');
    var tutor2 = form.querySelector('[data-tutor2]');
    var separados = form.querySelector('[data-separados]');
    function aplicarSoloUno() {
        var si = soloUno.checked;
        tutor2.hidden = si;
        $$('input,select,textarea', tutor2).forEach(function (i) { i.disabled = si; });
        if (separados) {
            separados.hidden = si;
            separados.querySelector('input').disabled = si;
        }
        $$('[data-copiar-pagador="1"]').forEach(function (b) { b.hidden = si; });
    }
    soloUno.addEventListener('change', aplicarSoloUno);

    // Payment at the counter: no SEPA data.
    var sepa = form.querySelector('[data-sepa]');
    function aplicarPago() {
        var r = form.querySelector('input[name="pago[modo]"]:checked');
        var ventanilla = r && r.value === 'ventanilla';
        sepa.hidden = ventanilla;
        $$('input,select', sepa).forEach(function (i) { i.disabled = ventanilla; });
    }
    $$('input[name="pago[modo]"]').forEach(function (r) { r.addEventListener('change', aplicarPago); });

    // Level suggested by the year of birth (school year starting in September of year Y).
    function inicioCurso() {
        var c = form.querySelector('[data-curso-escolar]');
        var y = c ? parseInt((c.value || '').slice(0, 4), 10) : NaN;
        return isNaN(y) ? new Date().getFullYear() : y;
    }
    function sugerir(bloque) {
        var f = bloque.querySelector('[data-nacimiento]');
        var sel = bloque.querySelector('[data-nivel]');
        if (!f || !sel) { return; }
        var ayuda = sel.closest('.afm-campo').querySelector('.afm-help');
        var anio = parseInt((f.value || '').slice(0, 4), 10);
        if (isNaN(anio)) { if (ayuda) { ayuda.textContent = ''; } return; }
        var edad = inicioCurso() - anio, sug = '';
        Object.keys(niveles).forEach(function (k) { if (niveles[k].edad === edad) { sug = k; } });
        if (!sug) {
            if (ayuda) { ayuda.textContent = ''; }
            return;
        }
        if (ayuda) { ayuda.textContent = 'Por su año de nacimiento le corresponde: ' + niveles[sug].label + '.'; }
        if (!sel.value) { sel.value = sug; ponError(sel, ''); }
    }
    form.addEventListener('change', function (ev) {
        if (ev.target.matches('[data-nacimiento]')) {
            sugerir(ev.target.closest('.afm-alumno'));
        }
        if (ev.target.matches('[data-curso-escolar]')) {
            $$('.afm-alumno').forEach(sugerir);
        }
    });

    // Contact e-mail and mail holder: suggested from the guardians.
    function sugerirContacto() {
        var c = form.querySelector('[data-email-contacto]');
        if (c && !c.value) {
            var e = $$('[data-email-tutor]').map(function (i) { return i.disabled ? '' : i.value.trim(); }).filter(Boolean)[0];
            if (e) { c.value = e; }
        }
        var t = form.querySelector('[data-titular]');
        if (t && !t.value) {
            var n = (v('tutores[0][nombre]') + ' ' + v('tutores[0][apellidos]')).trim();
            if (n) { t.value = n; }
        }
    }

    function esc(s) {
        return String(s).replace(/[&<>"']/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]; });
    }
    function resumen() {
        sugerirContacto();
        var box = form.querySelector('[data-resumen]');
        if (!box) { return; }
        var h = '<h3>Resumen</h3><ul>';
        $$('.afm-alumno', form.querySelector('[data-lista="alumnos"]')).forEach(function (b) {
            var nom = b.querySelector('[name$="[nombre]"]').value + ' ' + b.querySelector('[name$="[apellido1]"]').value + ' ' + b.querySelector('[name$="[apellido2]"]').value;
            var niv = b.querySelector('[data-nivel]');
            h += '<li><strong>' + esc(nom.trim()) + '</strong> · ' + esc(niv.options[niv.selectedIndex] ? niv.options[niv.selectedIndex].text : '') + '</li>';
        });
        [0, 1].forEach(function (i) {
            var n = campo('tutores[' + i + '][nombre]');
            if (n && !n.disabled && n.value) {
                var rol = campo('tutores[' + i + '][rol]');
                h += '<li>' + esc(rol.options[rol.selectedIndex].text) + ': ' + esc(n.value + ' ' + v('tutores[' + i + '][apellidos]')) + ' · ' + esc(v('tutores[' + i + '][movil]')) + '</li>';
            }
        });
        var r = form.querySelector('input[name="pago[modo]"]:checked');
        if (r && r.value === 'ventanilla') {
            h += '<li>Pago por ventanilla</li>';
        } else {
            var iban = v('pago[iban]').replace(/\s/g, '');
            h += '<li>Domiciliación: ' + esc(v('pago[pagador][nombre]')) + ' · IBAN ' + esc(iban.slice(0, 4)) + ' ···· ' + esc(iban.slice(-4)) + '</li>';
        }
        h += '</ul>';
        box.innerHTML = h;
        box.hidden = false;
    }

    /* ------------------------------------------------------------------
     * Borrador en este dispositivo (sin documentos ni IBAN)
     * ---------------------------------------------------------------- */

    var NO_GUARDAR = /^(web|_wpnonce|_wp_http_referer|action)$|\[documento\]$|\[iban\]$/;
    function guardar() {
        var d = {};
        $$('input,select,textarea').forEach(function (i) {
            if (!i.name || NO_GUARDAR.test(i.name) || i.closest('template')) { return; }
            if (i.type === 'radio' || i.type === 'checkbox') {
                if (i.checked) { d[i.name] = i.value; }
            } else if (i.value !== '') {
                d[i.name] = i.value;
            }
        });
        d._n = { alumnos: $$('.afm-repetible', form.querySelector('[data-lista="alumnos"]')).length, hermanos: $$('.afm-repetible', form.querySelector('[data-lista="hermanos"]')).length, paso: actual };
        try { localStorage.setItem(LS, JSON.stringify(d)); } catch (e) {}
    }
    var temp = null;
    function guardarPronto() { clearTimeout(temp); temp = setTimeout(guardar, 600); }
    form.addEventListener('input', guardarPronto);
    form.addEventListener('change', guardarPronto);

    function restaurar() {
        var d = null;
        try { d = JSON.parse(localStorage.getItem(LS) || 'null'); } catch (e) {}
        if (!d || !d._n) { return 0; }
        ['alumnos', 'hermanos'].forEach(function (t) {
            var lista = form.querySelector('[data-lista="' + t + '"]');
            while ($$('.afm-repetible', lista).length < Math.min(6, d._n[t] || 1)) { anadir(t); }
        });
        Object.keys(d).forEach(function (k) {
            if (k === '_n') { return; }
            $$('[name="' + k.replace(/"/g, '\\"') + '"]').forEach(function (i) {
                if (i.type === 'radio' || i.type === 'checkbox') { i.checked = (i.value === d[k]); } else { i.value = d[k]; }
            });
        });
        return d._n.paso || 0;
    }

    /* ------------------------------------------------------------------
     * Inicio
     * ---------------------------------------------------------------- */

    var conErrores = !!raiz.querySelector('#afm-errores');
    var paso = 0;
    if (!conErrores) {
        paso = restaurar();
        if (paso) {
            var aviso = document.createElement('p');
            aviso.className = 'afm-info';
            aviso.innerHTML = 'Hemos recuperado lo que habíais escrito en este dispositivo. <button type="button" class="afm-link-btn">Empezar de cero</button>';
            aviso.querySelector('button').addEventListener('click', function () {
                try { localStorage.removeItem(LS); } catch (e) {}
                window.location.reload();
            });
            form.parentNode.insertBefore(aviso, form);
        }
    }
    aplicarSoloUno();
    aplicarPago();
    $$('.afm-alumno').forEach(sugerir);
    if (conErrores) {
        var err = form.querySelector('.afm-campo.has-error');
        var p = err ? err.closest('.afm-paso') : null;
        paso = p ? pasos.indexOf(p) : 0;
    }
    ir(paso, false);
    if (conErrores) {
        var e1 = form.querySelector('.afm-campo.has-error input, .afm-campo.has-error select, .afm-campo.has-error textarea');
        if (e1) { e1.focus(); }
    }
})();
