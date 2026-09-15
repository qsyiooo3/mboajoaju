/* ============================================================
   MBOA' JOAJU — app.js (v2.0)
   JavaScript común para todo el sistema
   Sin dependencias · Vanilla JS · Ligero · Compatible
   ============================================================ */

(function () {
    'use strict';

    /* ══════════════════════════════════════════════════════
       1. SIDEBAR MÓVIL (Toggle)
       ══════════════════════════════════════════════════════ */
    function initSidebarToggle() {
        const toggleBtn = document.querySelector('.btn-sidebar-toggle');
        const sidebar = document.querySelector('.sidebar');
        let overlay = document.querySelector('.sidebar-overlay');

        if (!toggleBtn || !sidebar) return;

        if (!overlay) {
            overlay = document.createElement('div');
            overlay.className = 'sidebar-overlay';
            document.body.appendChild(overlay);
        }

        toggleBtn.addEventListener('click', function () {
            sidebar.classList.toggle('mobile-abierto');
            overlay.classList.toggle('activo');
        });

        overlay.addEventListener('click', function () {
            sidebar.classList.remove('mobile-abierto');
            overlay.classList.remove('activo');
        });

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                sidebar.classList.remove('mobile-abierto');
                overlay.classList.remove('activo');
            }
        });
    }

    /* ══════════════════════════════════════════════════════
       2. MODALES (Abrir / Cerrar)
       ══════════════════════════════════════════════════════ */
    function initModales() {
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                document.querySelectorAll('.modal-overlay.activo').forEach(function (modal) {
                    modal.classList.remove('activo');
                });
            }
        });

        document.querySelectorAll('.modal-overlay').forEach(function (modal) {
            modal.addEventListener('click', function (e) {
                if (e.target === modal) {
                    modal.classList.remove('activo');
                }
            });
        });

        document.querySelectorAll('[data-modal]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                const modalId = this.getAttribute('data-modal');
                const modal = document.getElementById(modalId);
                if (modal) modal.classList.add('activo');
            });
        });

        document.querySelectorAll('[data-close-modal]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                const modalId = this.getAttribute('data-close-modal');
                const modal = document.getElementById(modalId);
                if (modal) modal.classList.remove('activo');
            });
        });
    }

    /* ══════════════════════════════════════════════════════
       3. FILTRO DE TABLAS EN TIEMPO REAL
       ══════════════════════════════════════════════════════ */
    function initFiltroTablas() {
        document.querySelectorAll('[data-filtro-tabla]').forEach(function (input) {
            input.addEventListener('input', function () {
                const tablaId = this.getAttribute('data-filtro-tabla');
                const tabla = document.getElementById(tablaId);
                if (!tabla) return;

                const filtro = this.value.toUpperCase().trim();
                const filas = tabla.querySelectorAll('tbody tr');

                filas.forEach(function (fila) {
                    const texto = fila.textContent || fila.innerText;
                    fila.style.display =
                        filtro === '' || texto.toUpperCase().indexOf(filtro) > -1 ? '' : 'none';
                });
            });
        });
    }

    /* ══════════════════════════════════════════════════════
       4. CONFIRMACIÓN DE ELIMINACIÓN
       ══════════════════════════════════════════════════════ */
    function initConfirmaciones() {
        document.querySelectorAll('[data-confirm]').forEach(function (el) {
            el.addEventListener('click', function (e) {
                const mensaje = this.getAttribute('data-confirm');
                if (!confirm(mensaje)) {
                    e.preventDefault();
                    e.stopPropagation();
                    return false;
                }
            });
        });
    }

    /* ══════════════════════════════════════════════════════
       5. AUTO-CERRAR ALERTAS
       ══════════════════════════════════════════════════════ */
    function initAutoCerrarAlertas() {
        document.querySelectorAll('.alerta').forEach(function (alerta) {
            setTimeout(function () {
                alerta.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
                alerta.style.opacity = '0';
                alerta.style.transform = 'translateY(-10px)';
                setTimeout(function () {
                    alerta.style.display = 'none';
                }, 500);
            }, 5000);
        });
    }

    /* ══════════════════════════════════════════════════════
       6. VALIDACIÓN DE FORMULARIOS
       ══════════════════════════════════════════════════════ */
    function initValidacionFormularios() {
        document.querySelectorAll('form[data-validar]').forEach(function (form) {
            form.addEventListener('submit', function (e) {
                let valido = true;
                const campos = form.querySelectorAll('[required]');

                campos.forEach(function (campo) {
                    campo.classList.remove('input-error');
                    const errorPrevio = campo.parentNode.querySelector('.error-msg');
                    if (errorPrevio) errorPrevio.remove();

                    if (!campo.value.trim()) {
                        valido = false;
                        campo.classList.add('input-error');
                        mostrarErrorCampo(campo, 'Este campo es obligatorio');
                    } else if (campo.type === 'email' && !validarEmail(campo.value)) {
                        valido = false;
                        campo.classList.add('input-error');
                        mostrarErrorCampo(campo, 'Correo electrónico inválido');
                    } else if (campo.type === 'number') {
                        const min = parseFloat(campo.getAttribute('min'));
                        const max = parseFloat(campo.getAttribute('max'));
                        const val = parseFloat(campo.value);
                        if (!isNaN(min) && val < min) {
                            valido = false;
                            campo.classList.add('input-error');
                            mostrarErrorCampo(campo, 'El valor mínimo es ' + min);
                        }
                        if (!isNaN(max) && val > max) {
                            valido = false;
                            campo.classList.add('input-error');
                            mostrarErrorCampo(campo, 'El valor máximo es ' + max);
                        }
                    }
                });

                if (!valido) {
                    e.preventDefault();
                    const primerError = form.querySelector('.input-error');
                    if (primerError) {
                        primerError.focus();
                        primerError.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    }
                }
            });
        });
    }

    function mostrarErrorCampo(campo, mensaje) {
        const error = document.createElement('small');
        error.className = 'error-msg';
        error.style.color = 'var(--color-error)';
        error.style.fontSize = 'var(--font-size-xs)';
        error.style.marginTop = '3px';
        error.style.display = 'block';
        error.textContent = mensaje;
        campo.parentNode.appendChild(error);
    }

    function validarEmail(email) {
        return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
    }

    /* ══════════════════════════════════════════════════════
       7. VALIDACIÓN DE ARCHIVOS
       ══════════════════════════════════════════════════════ */
    function initValidacionArchivos() {
        const MAX_SIZE = 10 * 1024 * 1024;
        const EXTENSIONES = [
            'pdf', 'doc', 'docx', 'xls', 'xlsx',
            'ppt', 'pptx', 'jpg', 'jpeg', 'png',
            'gif', 'txt', 'zip', 'rar'
        ];

        document.querySelectorAll('input[type="file"]').forEach(function (input) {
            input.addEventListener('change', function () {
                const errorPrevio = this.parentNode.querySelector('.file-error');
                if (errorPrevio) errorPrevio.remove();

                if (!this.files || !this.files[0]) return;

                const file = this.files[0];
                const extension = file.name.split('.').pop().toLowerCase();

                if (!EXTENSIONES.includes(extension)) {
                    mostrarErrorArchivo(
                        this,
                        'Extensión no permitida. Solo: ' + EXTENSIONES.join(', ')
                    );
                    this.value = '';
                    return;
                }

                if (file.size > MAX_SIZE) {
                    mostrarErrorArchivo(
                        this,
                        'El archivo supera el límite de 10 MB. Tamaño actual: ' +
                            formatearBytes(file.size)
                    );
                    this.value = '';
                    return;
                }
            });
        });
    }

    function mostrarErrorArchivo(input, mensaje) {
        const error = document.createElement('small');
        error.className = 'file-error';
        error.style.color = 'var(--color-error)';
        error.style.fontSize = 'var(--font-size-xs)';
        error.style.marginTop = '3px';
        error.style.display = 'block';
        error.textContent = mensaje;
        input.parentNode.appendChild(error);
    }

    function formatearBytes(bytes) {
        const unidades = ['B', 'KB', 'MB', 'GB'];
        let i = 0;
        while (bytes >= 1024 && i < unidades.length - 1) {
            bytes /= 1024;
            i++;
        }
        return bytes.toFixed(2) + ' ' + unidades[i];
    }

    /* ══════════════════════════════════════════════════════
       8. CONTADOR DE CARACTERES
       ══════════════════════════════════════════════════════ */
    function initContadorCaracteres() {
        document.querySelectorAll('textarea[maxlength]').forEach(function (textarea) {
            const max = parseInt(textarea.getAttribute('maxlength'));
            let contador = textarea.parentNode.querySelector('.contador-caracteres');

            if (!contador) {
                contador = document.createElement('small');
                contador.className = 'contador-caracteres';
                contador.style.display = 'block';
                contador.style.textAlign = 'right';
                contador.style.fontSize = 'var(--font-size-xs)';
                contador.style.color = 'var(--color-text-light)';
                contador.style.marginTop = '2px';
                textarea.parentNode.appendChild(contador);
            }

            function actualizar() {
                const actual = textarea.value.length;
                contador.textContent = actual + ' / ' + max + ' caracteres';
                contador.style.color =
                    actual > max * 0.9
                        ? 'var(--color-warning)'
                        : 'var(--color-text-light)';
            }

            textarea.addEventListener('input', actualizar);
            actualizar();
        });
    }

    /* ══════════════════════════════════════════════════════
       9. TOAST (Notificaciones flotantes)
       ══════════════════════════════════════════════════════ */
    function mostrarToast(mensaje, tipo) {
        tipo = tipo || 'info';
        const colores = {
            success: 'var(--color-success)',
            error: 'var(--color-error)',
            warning: 'var(--color-warning)',
            info: 'var(--color-info)'
        };

        const toast = document.createElement('div');
        toast.className = 'toast toast-' + tipo;
        toast.textContent = mensaje;
        toast.style.cssText = `
            position: fixed;
            bottom: 24px;
            right: 24px;
            padding: 14px 22px;
            background: ${colores[tipo] || colores.info};
            color: white;
            border-radius: var(--radius-md);
            box-shadow: 0 6px 20px rgba(0,0,0,0.2);
            font-size: var(--font-size-sm);
            font-weight: 600;
            z-index: 9999;
            animation: slideToast 0.3s ease;
            max-width: 90vw;
        `;

        document.body.appendChild(toast);

        setTimeout(function () {
            toast.style.opacity = '0';
            toast.style.transform = 'translateY(20px)';
            toast.style.transition = 'all 0.3s ease';
            setTimeout(function () { toast.remove(); }, 300);
        }, 3000);
    }

    /* ══════════════════════════════════════════════════════
       10. COPIAR AL PORTAPAPELES
       ══════════════════════════════════════════════════════ */
    function initCopiarPortapapeles() {
        document.querySelectorAll('[data-copiar]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                const texto = this.getAttribute('data-copiar');
                if (!texto) return;

                if (navigator.clipboard && navigator.clipboard.writeText) {
                    navigator.clipboard.writeText(texto).then(function () {
                        mostrarToast('Copiado al portapapeles', 'success');
                    }).catch(function () {
                        mostrarToast('No se pudo copiar', 'error');
                    });
                } else {
                    const textarea = document.createElement('textarea');
                    textarea.value = texto;
                    document.body.appendChild(textarea);
                    textarea.select();
                    try {
                        document.execCommand('copy');
                        mostrarToast('Copiado al portapapeles', 'success');
                    } catch (e) {
                        mostrarToast('No se pudo copiar', 'error');
                    }
                    textarea.remove();
                }
            });
        });
    }

    /* ══════════════════════════════════════════════════════
       11. SCROLL SUAVE A ANCLAS
       ══════════════════════════════════════════════════════ */
    function initScrollSuave() {
        document.querySelectorAll('a[href^="#"]').forEach(function (link) {
            link.addEventListener('click', function (e) {
                const href = this.getAttribute('href');
                if (href === '#' || href === '') return;

                const target = document.querySelector(href);
                if (target) {
                    e.preventDefault();
                    target.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            });
        });
    }

    /* ══════════════════════════════════════════════════════
       12. PREVIEW DE ARCHIVO ANTES DE SUBIR
       ══════════════════════════════════════════════════════ */
    function initPreviewArchivos() {
        document.querySelectorAll('input[type="file"][data-preview]').forEach(function (input) {
            const previewId = input.getAttribute('data-preview');
            const preview = document.getElementById(previewId);

            if (!preview) return;

            input.addEventListener('change', function () {
                preview.innerHTML = '';

                if (!this.files || !this.files[0]) return;

                const file = this.files[0];
                const extension = file.name.split('.').pop().toLowerCase();

                const info = document.createElement('div');
                info.style.cssText = `
                    display: flex;
                    align-items: center;
                    gap: 8px;
                    padding: 8px 12px;
                    background: var(--color-background);
                    border-radius: var(--radius-sm);
                    margin-top: 8px;
                    font-size: var(--font-size-xs);
                `;

                const iconos = {
                    pdf: '📄', doc: '📝', docx: '📝',
                    xls: '📊', xlsx: '📊',
                    ppt: '📑', pptx: '📑',
                    jpg: '🖼️', jpeg: '🖼️', png: '🖼️', gif: '🖼️',
                    zip: '📦', rar: '📦', txt: '📃'
                };

                info.innerHTML = `
                    <span style="font-size: 20px;">${iconos[extension] || '📎'}</span>
                    <span style="flex-grow: 1;">${file.name}</span>
                    <span style="color: var(--color-text-light);">${formatearBytes(file.size)}</span>
                `;

                preview.appendChild(info);
            });
        });
    }

    /* ══════════════════════════════════════════════════════
       13. CONTADOR DE NOTIFICACIONES (Polling ligero)
       ══════════════════════════════════════════════════════ */
    function initContadorNotificaciones() {
        const badge = document.querySelector('[data-notif-badge]');
        if (!badge) return;

        const url = badge.getAttribute('data-notif-url');
        if (!url) return;

        function actualizar() {
            fetch(url, { credentials: 'same-origin' })
                .then(function (res) { return res.json(); })
                .then(function (data) {
                    if (data.total > 0) {
                        badge.textContent = data.total;
                        badge.style.display = 'inline-block';
                    } else {
                        badge.style.display = 'none';
                    }
                })
                .catch(function () { /* Silencioso */ });
        }

        actualizar();
        setInterval(actualizar, 60000);
    }

    /* ══════════════════════════════════════════════════════
       14. TOGGLE DE CAMPOS SEGÚN ROL
       ══════════════════════════════════════════════════════ */
    function initToggleCamposRol() {
        const selectRol = document.querySelector('select[data-toggle-rol]');
        if (!selectRol) return;

        const grupos = {
            alumno: ['grupo_cedula', 'grupo_curso', 'grupo_seccion',
                     'grupo_telefono', 'grupo_telefono_padre', 'grupo_nombre_padre'],
            profesor: ['grupo_cedula', 'grupo_especialidad', 'grupo_telefono']
        };

        function actualizar() {
            const rol = selectRol.value;

            document.querySelectorAll('[id^="grupo_"]').forEach(function (el) {
                el.style.display = 'none';
            });

            if (rol && grupos[rol]) {
                grupos[rol].forEach(function (id) {
                    const el = document.getElementById(id);
                    if (el) el.style.display = 'block';
                });
            }
        }

        selectRol.addEventListener('change', actualizar);
        actualizar();
    }

    /* ══════════════════════════════════════════════════════
       15. LOADER / SPINNER
       ══════════════════════════════════════════════════════ */
    function mostrarLoader(texto) {
        let overlay = document.getElementById('loaderOverlay');

        if (!overlay) {
            overlay = document.createElement('div');
            overlay.id = 'loaderOverlay';
            overlay.className = 'loader-overlay';
            overlay.innerHTML = `
                <div class="loader-content">
                    <div class="spinner"></div>
                    <div class="loader-text">${texto || 'Cargando...'}</div>
                </div>
            `;
            document.body.appendChild(overlay);
        } else {
            const textEl = overlay.querySelector('.loader-text');
            if (textEl) textEl.textContent = texto || 'Cargando...';
        }

        overlay.classList.add('activo');
    }

    function ocultarLoader() {
        const overlay = document.getElementById('loaderOverlay');
        if (overlay) overlay.classList.remove('activo');
    }

    function botonCargando(boton, textoCarga) {
        if (!boton) return;
        const textoOriginal = boton.innerHTML;
        boton.dataset.textoOriginal = textoOriginal;
        boton.classList.add('btn-loading');
        boton.disabled = true;
        boton.innerHTML = `
            <span class="spinner spinner-sm"></span>
            ${textoCarga || 'Procesando...'}
        `;
    }

    function botonRestaurar(boton) {
        if (!boton) return;
        boton.classList.remove('btn-loading');
        boton.disabled = false;
        if (boton.dataset.textoOriginal) {
            boton.innerHTML = boton.dataset.textoOriginal;
            delete boton.dataset.textoOriginal;
        }
    }

    /* ══════════════════════════════════════════════════════
       16. AUTO-LOADER EN FORMULARIOS Y ENLACES
       ══════════════════════════════════════════════════════ */
    function initAutoLoader() {
        document.querySelectorAll('form[data-loader]').forEach(function (form) {
            form.addEventListener('submit', function () {
                const textoLoader = form.getAttribute('data-loader') || 'Procesando...';
                mostrarLoader(textoLoader);

                const botonEnviar = form.querySelector('button[type="submit"]');
                if (botonEnviar) botonCargando(botonEnviar);
            });
        });

        document.querySelectorAll('a[data-loader]').forEach(function (link) {
            link.addEventListener('click', function (e) {
                if (this.target === '_blank') return;
                const texto = this.getAttribute('data-loader') || 'Cargando...';
                mostrarLoader(texto);
            });
        });
    }

    /* ══════════════════════════════════════════════════════
       17. INICIALIZACIÓN
       ══════════════════════════════════════════════════════ */
    function init() {
        initSidebarToggle();
        initModales();
        initFiltroTablas();
        initConfirmaciones();
        initAutoCerrarAlertas();
        initValidacionFormularios();
        initValidacionArchivos();
        initContadorCaracteres();
        initCopiarPortapapeles();
        initScrollSuave();
        initPreviewArchivos();
        initContadorNotificaciones();
        initToggleCamposRol();
        initAutoLoader();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    /* ══════════════════════════════════════════════════════
       18. EXPONER FUNCIONES GLOBALES
       ══════════════════════════════════════════════════════ */
    window.mostrarToast = mostrarToast;
    window.mostrarLoader = mostrarLoader;
    window.ocultarLoader = ocultarLoader;
    window.botonCargando = botonCargando;
    window.botonRestaurar = botonRestaurar;
    window.formatearBytes = formatearBytes;

    /* ══════════════════════════════════════════════════════
       19. ANIMACIONES GLOBALES (para toast)
       ══════════════════════════════════════════════════════ */
    const style = document.createElement('style');
    style.textContent = `
        @keyframes slideToast {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .input-error {
            border-color: var(--color-error) !important;
            box-shadow: 0 0 0 3px rgba(192, 57, 43, 0.12) !important;
        }
    `;
    document.head.appendChild(style);

})();