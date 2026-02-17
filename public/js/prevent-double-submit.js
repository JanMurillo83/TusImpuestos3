/**
 * Prevención de doble envío de formularios
 * Este script previene que los usuarios hagan doble clic en botones de envío
 * y dupliquen registros en la base de datos.
 */

(function($) {
    'use strict';

    // Control para formularios estándar
    $(document).on('submit', 'form', function(e) {
        const $form = $(this);
        const $submitButtons = $form.find('button[type="submit"], input[type="submit"]');

        // Verificar si el formulario ya está siendo procesado
        if ($form.data('submitting') === true) {
            e.preventDefault();
            return false;
        }

        // Marcar el formulario como en proceso
        $form.data('submitting', true);

        // Deshabilitar todos los botones de envío
        $submitButtons.each(function() {
            const $btn = $(this);

            // Guardar el contenido original
            $btn.data('original-html', $btn.html());
            $btn.data('original-text', $btn.val());

            // Deshabilitar el botón
            $btn.prop('disabled', true);

            // Cambiar el texto/contenido del botón
            if ($btn.is('button')) {
                $btn.html('<i class="fa fa-spinner fa-spin"></i> Procesando...');
            } else {
                $btn.val('Procesando...');
            }

            // Agregar clase visual
            $btn.addClass('btn-processing');
        });

        // Timeout de seguridad: re-habilitar después de 30 segundos
        setTimeout(function() {
            if ($form.data('submitting') === true) {
                $form.data('submitting', false);
                $submitButtons.each(function() {
                    const $btn = $(this);
                    $btn.prop('disabled', false);

                    if ($btn.is('button')) {
                        $btn.html($btn.data('original-html'));
                    } else {
                        $btn.val($btn.data('original-text'));
                    }

                    $btn.removeClass('btn-processing');
                });
            }
        }, 30000);
    });

    // Control para botones individuales que no estén en formularios
    $(document).on('click', 'button[data-prevent-double], a[data-prevent-double]', function(e) {
        const $btn = $(this);

        // Si ya está procesando, prevenir la acción
        if ($btn.data('processing') === true) {
            e.preventDefault();
            return false;
        }

        // Marcar como en proceso
        $btn.data('processing', true);
        $btn.data('original-html', $btn.html());

        // Deshabilitar y cambiar apariencia
        $btn.prop('disabled', true);
        $btn.html('<i class="fa fa-spinner fa-spin"></i> Procesando...');
        $btn.addClass('btn-processing');

        // Re-habilitar después de 5 segundos (para AJAX)
        setTimeout(function() {
            $btn.data('processing', false);
            $btn.prop('disabled', false);
            $btn.html($btn.data('original-html'));
            $btn.removeClass('btn-processing');
        }, 5000);
    });

    // Control para AJAX requests usando jQuery
    if (window.jQuery) {
        $(document).ajaxSend(function(event, jqxhr, settings) {
            // Encontrar el botón que disparó el AJAX si existe
            const $activeBtn = $('button:focus, input[type="submit"]:focus');
            if ($activeBtn.length && !$activeBtn.prop('disabled')) {
                $activeBtn.data('ajax-processing', true);
                $activeBtn.data('original-content', $activeBtn.is('button') ? $activeBtn.html() : $activeBtn.val());
                $activeBtn.prop('disabled', true);

                if ($activeBtn.is('button')) {
                    $activeBtn.html('<i class="fa fa-spinner fa-spin"></i> Procesando...');
                } else {
                    $activeBtn.val('Procesando...');
                }
            }
        });

        $(document).ajaxComplete(function(event, jqxhr, settings) {
            // Re-habilitar botones después de completar AJAX
            const $activeBtn = $('button[data-ajax-processing="true"], input[data-ajax-processing="true"]');
            if ($activeBtn.length) {
                setTimeout(function() {
                    $activeBtn.prop('disabled', false);
                    if ($activeBtn.is('button')) {
                        $activeBtn.html($activeBtn.data('original-content'));
                    } else {
                        $activeBtn.val($activeBtn.data('original-content'));
                    }
                    $activeBtn.removeData('ajax-processing');
                }, 500);
            }
        });
    }

    // Estilos CSS adicionales
    const style = document.createElement('style');
    style.textContent = `
        .btn-processing {
            opacity: 0.7;
            cursor: not-allowed !important;
        }
    `;
    document.head.appendChild(style);

})(jQuery);
