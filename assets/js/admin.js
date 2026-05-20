jQuery(document).ready(function($) {
    $(document).on('click', '.spn-pdf-btn-export', function(e) {
        e.preventDefault();
        
        var $btn = $(this);
        if ($btn.hasClass('disabled')) {
            return;
        }
        
        var testUrl = $btn.data('test-url');
        var solUrl = $btn.data('sol-url');
        var fileBase = $btn.data('filename-base') || 'documento';
        
        if (!testUrl || !solUrl) {
            return;
        }
        
        // Determinar si estamos en el paso 2 (descarga manual del solucionario)
        var isSolucionarioOnly = $btn.hasClass('spn-sol-pending');
        var seed = $btn.data('current-seed');
        
        if (isSolucionarioOnly && seed) {
            // Descarga directa del solucionario (acción manual del usuario)
            $btn.addClass('disabled').css('pointer-events', 'none').css('opacity', '0.6');
            $btn.html('<span class="dashicons dashicons-update spin"></span> Descargando...');
            
            var finalSolUrl = solUrl + '&seed=' + seed;
            triggerDirectDownload(finalSolUrl, fileBase + '-solucionario.pdf', function() {
                resetButton($btn);
            });
            return;
        }
        
        // Generar una semilla aleatoria nueva para esta exportación
        seed = Math.floor(Math.random() * 999999) + 1;
        $btn.data('current-seed', seed);
        
        var finalTestUrl = testUrl + '&seed=' + seed;
        var finalSolUrl = solUrl + '&seed=' + seed;
        
        // Paso 1: Descargar Examen
        $btn.addClass('disabled').css('pointer-events', 'none').css('opacity', '0.6');
        $btn.html('<span class="dashicons dashicons-update spin"></span> Generando Examen...');
        
        fetch(finalTestUrl, { credentials: 'same-origin' })
            .then(function(res) {
                if (!res.ok) throw new Error('Error al generar el examen');
                return res.blob();
            })
            .then(function(blob) {
                // Descargar Examen
                saveBlob(blob, fileBase + '-examen.pdf');
                
                // Cambiar estado a solucionario pendiente (por si falla la autodescarga)
                $btn.removeClass('disabled').css('pointer-events', '').css('opacity', '')
                    .addClass('spn-sol-pending')
                    .html('<span class="dashicons dashicons-pdf"></span> Descargar Solucionario');
                
                // Paso 2: Autodescarga secuencial del solucionario
                return fetch(finalSolUrl, { credentials: 'same-origin' });
            })
            .then(function(res) {
                if (!res.ok) throw new Error('Error al generar el solucionario');
                return res.blob();
            })
            .then(function(blob) {
                // Descargar Solucionario automáticamente
                saveBlob(blob, fileBase + '-solucionario.pdf');
                
                // Si la autodescarga funciona, restaurar botón tras 1.5s
                setTimeout(function() {
                    resetButton($btn);
                }, 1500);
            })
            .catch(function(err) {
                console.error(err);
                // Si la autodescarga es bloqueada o falla, el botón se queda en el paso 2 para clic manual
                $btn.removeClass('disabled').css('pointer-events', '').css('opacity', '')
                    .addClass('spn-sol-pending')
                    .html('<span class="dashicons dashicons-pdf"></span> Descargar Solucionario');
            });
    });
    
    function saveBlob(blob, filename) {
        var blobUrl = window.URL.createObjectURL(blob);
        var a = document.createElement('a');
        a.href = blobUrl;
        a.download = filename;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        window.URL.revokeObjectURL(blobUrl);
    }
    
    function triggerDirectDownload(url, filename, callback) {
        fetch(url, { credentials: 'same-origin' })
            .then(function(res) { return res.blob(); })
            .then(function(blob) { 
                saveBlob(blob, filename); 
                if (callback) callback();
            })
            .catch(function(err) { 
                console.error(err);
                if (callback) callback();
            });
    }
    
    function resetButton($btn) {
        $btn.removeClass('disabled spn-sol-pending')
            .css('pointer-events', '')
            .css('opacity', '')
            .removeData('current-seed')
            .removeData('seed') // Por si acaso
            .html('<span class="dashicons dashicons-pdf"></span> Exportar PDF');
    }
});
