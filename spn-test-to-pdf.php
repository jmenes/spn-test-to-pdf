<?php
/**
 * Plugin Name: SPN Test to PDF
 * Description: Convertir a PDF los post types "simulacros" y "test" con opción de examen en papel y solucionario explicativo.
 * Version: 1.0.0
 * Author: menes.studio
 * Author URI: https://menes.studio
 * License: GPL2
 */

if (!defined('ABSPATH')) {
    exit; // Salir si se accede directamente
}

// Definir constantes del plugin
define('SPN_TTP_DIR', plugin_dir_path(__FILE__));
define('SPN_TTP_URL', plugin_dir_url(__FILE__));

/**
 * Añadir columna "Exportar PDF" en el listado de Tests y Simulacros
 */
add_filter('manage_edit-test_columns', 'spn_ttp_add_pdf_columns', 9999);
add_filter('manage_edit-simulacro_columns', 'spn_ttp_add_pdf_columns', 9999);
function spn_ttp_add_pdf_columns($columns) {
    $columns['spn_pdf_actions'] = 'Exportar PDF';
    return $columns;
}

/**
 * Renderizar los botones de descarga de PDF en la nueva columna
 */
add_action('manage_test_posts_custom_column', 'spn_ttp_render_pdf_columns', 10, 2);
add_action('manage_simulacro_posts_custom_column', 'spn_ttp_render_pdf_columns', 10, 2);
function spn_ttp_render_pdf_columns($column, $post_id) {
    if ($column === 'spn_pdf_actions') {
        $nonce = wp_create_nonce('spn_generate_pdf_' . $post_id);
        $test_url = admin_url('admin-post.php?action=spn_generate_test_pdf&post_id=' . $post_id . '&with_answers=0&_wpnonce=' . $nonce);
        $solucion_url = admin_url('admin-post.php?action=spn_generate_test_pdf&post_id=' . $post_id . '&with_answers=1&_wpnonce=' . $nonce);
        
        echo '<div class="spn-pdf-actions-wrapper">';
        echo '<a href="' . esc_url($test_url) . '" class="spn-pdf-btn spn-pdf-btn-test" title="Descargar Test sin soluciones">';
        echo '<span class="dashicons dashicons-pdf"></span> Test';
        echo '</a>';
        echo '<a href="' . esc_url($solucion_url) . '" class="spn-pdf-btn spn-pdf-btn-answers" title="Descargar Solucionario con respuestas y explicaciones">';
        echo '<span class="dashicons dashicons-welcome-learn-more"></span> Solucionario';
        echo '</a>';
        echo '</div>';
    }
}

/**
 * Encolar los estilos del panel de administración
 */
add_action('admin_enqueue_scripts', 'spn_ttp_enqueue_admin_assets');
function spn_ttp_enqueue_admin_assets($hook) {
    global $post_type;
    if ($hook === 'edit.php' && in_array($post_type, ['test', 'simulacro'])) {
        wp_enqueue_style('spn-test-to-pdf-admin', SPN_TTP_URL . 'assets/css/admin.css', [], '1.0.0');
    }
}

/**
 * Procesar la solicitud de generación de PDF
 */
add_action('admin_post_spn_generate_test_pdf', 'spn_ttp_handle_pdf_generation');
function spn_ttp_handle_pdf_generation() {
    // 1. Verificar parámetros mínimos de la petición
    if (!isset($_GET['post_id']) || !isset($_GET['_wpnonce'])) {
        wp_die('Acceso denegado. Parámetros insuficientes.');
    }
    
    $post_id = intval($_GET['post_id']);
    
    // 2. Verificar la validez del nonce
    if (!wp_verify_nonce($_GET['_wpnonce'], 'spn_generate_pdf_' . $post_id)) {
        wp_die('Acceso denegado. Firma de seguridad no válida o caducada.');
    }
    
    // 3. Verificar permisos de edición sobre el test/simulacro
    if (!current_user_can('edit_post', $post_id)) {
        wp_die('No tienes permisos suficientes para realizar esta acción.');
    }
    
    // 4. Obtener y validar el tipo de post
    $post = get_post($post_id);
    if (!$post || !in_array($post->post_type, ['test', 'simulacro'])) {
        wp_die('El post especificado no es un examen válido.');
    }
    
    $with_answers = isset($_GET['with_answers']) && $_GET['with_answers'] === '1';
    
    // 5. Cargar Dompdf
    if (!class_exists('Dompdf\\Dompdf')) {
        // En Bedrock el autoloader carga todo en wp-config.php. Si por algún motivo no se cargó, intentamos incluirlo:
        $bedrock_root = dirname(ABSPATH); // /srv/bedrock/web
        if (file_exists($bedrock_root . '/../vendor/autoload.php')) {
            require_once $bedrock_root . '/../vendor/autoload.php';
        } elseif (file_exists($bedrock_root . '/vendor/autoload.php')) {
            require_once $bedrock_root . '/vendor/autoload.php';
        }
    }
    
    if (!class_exists('Dompdf\\Dompdf')) {
        wp_die('Error: La librería Dompdf no está instalada o cargada en el entorno.');
    }
    
    // 6. Obtener preguntas del test mediante ACF
    $questions_field = get_field('preguntas', $post_id);
    $questions = [];
    
    if (is_array($questions_field)) {
        foreach ($questions_field as $question_id) {
            $question_post = get_post($question_id);
            if (!$question_post || $question_post->post_type !== 'pregunta' || $question_post->post_status !== 'publish') {
                continue;
            }
            
            // Obtener opciones
            $options = [];
            $options_field = get_field('opciones', $question_post->ID);
            if (is_array($options_field)) {
                foreach ($options_field as $opt) {
                    $options[] = [
                        'id'      => isset($opt['id']) ? $opt['id'] : '',
                        'option'  => isset($opt['opcion']) ? $opt['opcion'] : '',
                        'correct' => isset($opt['respuesta_correcta']) ? (bool)$opt['respuesta_correcta'] : false,
                    ];
                }
            }
            
            $questions[] = [
                'id'          => $question_post->ID,
                'title'       => get_the_title($question_post),
                'options'     => $options,
                'explanation' => get_field('explicacion', $question_post->ID),
            ];
        }
    }
    
    // 7. Preparar variables para la plantilla PDF
    $title = get_the_title($post);
    $type = ($post->post_type === 'simulacro') ? 'Simulacro' : 'Test';
    $duration_raw = get_field('field_6912550e1539e', $post_id); // Campo ACF Duración
    $duration = is_numeric($duration_raw) ? intval($duration_raw) : 0;
    
    // Ruta absoluta al icono PNG para marca de agua en el PDF
    $watermark_path = SPN_TTP_DIR . 'SeguimientoPN-icon.png';
    
    // Ruta absoluta al logo color para la cabecera en el PDF
    $logo_path = SPN_TTP_DIR . 'SeguimientoPN-Logo-Color.png';
    
    // 8. Renderizar y capturar el HTML de la plantilla
    ob_start();
    if (file_exists(SPN_TTP_DIR . 'templates/pdf-template.php')) {
        include SPN_TTP_DIR . 'templates/pdf-template.php';
    } else {
        wp_die('Error: No se encuentra la plantilla HTML para el PDF.');
    }
    $html = ob_get_clean();
    
    // 9. Generar y transmitir el PDF con Dompdf
    try {
        $dompdf = new \Dompdf\Dompdf([
            'isHtml5ParserEnabled' => true,
            'isPhpEnabled'          => false,
            'isRemoteEnabled'       => true,
            'defaultFont'           => 'sans-serif',
            'chroot'                => dirname(ABSPATH, 2)
        ]);
        
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        
        // Agregar numeración de página "X / Y" en el pie de página
        $canvas = $dompdf->getCanvas();
        if ($canvas) {
            $fontMetrics = $dompdf->getFontMetrics();
            $font = $fontMetrics->get_font('helvetica', 'normal');
            // X=515 y Y=802 colocan el texto "PÁGINA / TOTAL" alineado con el pie de página
            $canvas->page_text(515, 802, '{PAGE_NUM} / {PAGE_COUNT}', $font, 8, [160/255, 174/255, 192/255]);
        }
        
        $suffix = $with_answers ? '-solucionario' : '-examen';
        $filename = sanitize_title($title) . $suffix . '.pdf';
        
        // Transmitir descarga directa
        $dompdf->stream($filename, ['Attachment' => 1]);
        exit;
    } catch (\Exception $e) {
        wp_die('Error al generar el PDF: ' . esc_html($e->getMessage()));
    }
}
