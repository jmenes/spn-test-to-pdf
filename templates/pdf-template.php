<?php
/**
 * Plantilla HTML para la generación del PDF.
 * Variables disponibles:
 * - $title: Título del test/simulacro
 * - $type: Tipo (Test o Simulacro)
 * - $duration: Duración en minutos
 * - $questions: Array de preguntas procesadas
 * - $with_answers: Booleano indicando si se incluye solucionario
 * - $watermark_path: Ruta absoluta al archivo SVG de marca de agua
 */

if (!function_exists('spn_get_clock_icon_base64')) {
    function spn_get_clock_icon_base64() {
        $im = imagecreatetruecolor(16, 16);
        imagealphablending($im, false);
        imagesavealpha($im, true);
        
        $trans = imagecolorallocatealpha($im, 0, 0, 0, 127);
        imagefill($im, 0, 0, $trans);
        
        $color = imagecolorallocate($im, 127, 140, 141);
        
        // Círculo del reloj
        imageellipse($im, 8, 8, 14, 14, $color);
        imageellipse($im, 8, 8, 13, 13, $color);
        imagesetpixel($im, 8, 8, $color);
        // Agujas
        imageline($im, 8, 8, 8, 4, $color);
        imageline($im, 8, 8, 11, 8, $color);
        
        ob_start();
        imagepng($im);
        $data = ob_get_clean();
        imagedestroy($im);
        
        return 'data:image/png;base64,' . base64_encode($data);
    }
}

if (!function_exists('spn_get_doc_icon_base64')) {
    function spn_get_doc_icon_base64() {
        $im = imagecreatetruecolor(16, 16);
        imagealphablending($im, false);
        imagesavealpha($im, true);
        
        $trans = imagecolorallocatealpha($im, 0, 0, 0, 127);
        imagefill($im, 0, 0, $trans);
        
        $color = imagecolorallocate($im, 127, 140, 141);
        
        // Bordes del documento
        imagerectangle($im, 3, 2, 12, 13, $color);
        // Líneas de texto del documento
        imageline($im, 5, 5, 10, 5, $color);
        imageline($im, 5, 8, 10, 8, $color);
        imageline($im, 5, 11, 8, 11, $color);
        
        ob_start();
        imagepng($im);
        $data = ob_get_clean();
        imagedestroy($im);
        
        return 'data:image/png;base64,' . base64_encode($data);
    }
}

if (!function_exists('spn_get_correct_answer_letter')) {
    function spn_get_correct_answer_letter($question) {
        $letters = ['A', 'B', 'C', 'D', 'E'];
        if (isset($question['options']) && is_array($question['options'])) {
            foreach ($question['options'] as $idx => $opt) {
                if (!empty($opt['correct'])) {
                    return isset($letters[$idx]) ? $letters[$idx] : '';
                }
            }
        }
        return '';
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title><?php echo esc_html($title); ?></title>
    <style>
        @page {
            margin: 2.8cm 2cm 2.2cm 2cm;
        }
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            color: #2c3e50;
            line-height: 1.5;
            font-size: 9.5pt;
        }
        /* Marca de agua centrada en cada página */
        #watermark {
            position: fixed;
            top: 29.5%;
            left: 8%;
            width: 84%;
            height: auto;
            opacity: 0.12;
            z-index: -1000;
        }
        .header {
            position: fixed;
            top: -1.8cm;
            left: 0px;
            right: 0px;
            height: 48px;
            border-bottom: 1px solid #e2e8f0;
            padding-bottom: 8px;
        }
        .header-logo {
            float: left;
            height: 38px;
            width: auto;
        }
        .header-logo-text {
            float: left;
            font-weight: bold;
            color: #2c3e50;
            line-height: 38px;
            font-size: 14pt;
        }
        .header-title-container {
            float: left;
            margin-left: 12px;
            border-left: 1px solid #e2e8f0;
            padding-left: 12px;
            height: 38px;
        }
        .header-title {
            font-size: 10pt;
            font-weight: bold;
            color: #475569;
        }
        .header-solucionario-label {
            font-size: 7.5pt;
            font-weight: bold;
            color: #27ae60;
            line-height: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .header-meta {
            float: right;
            font-size: 9pt;
            color: #7f8c8d;
            margin-top: 12px;
        }
        .icon {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 10pt;
            color: #7f8c8d;
            vertical-align: middle;
            margin-right: 3px;
        }
        .questions-container {
            margin-top: 15px;
        }
        .question-block {
            margin-bottom: 12px;
            padding-bottom: 8px;
            border-bottom: 1px solid #f1f2f6;
        }
        .question-block:last-child {
            border-bottom: none;
            padding-bottom: 0;
            margin-bottom: 0;
        }
        .question-main {
            page-break-inside: avoid;
        }
        .question-title {
            font-weight: bold;
            font-size: 10pt;
            margin-bottom: 8px;
            color: #2c3e50;
        }
        .options-list {
            margin-left: 15px;
            margin-bottom: 8px;
        }
        .option-item {
            margin-bottom: 5px;
            font-size: 9pt;
        }
        /* Estilos para el solucionario */
        .option-correct {
            font-weight: bold;
            color: #27ae60;
        }
        .explanation-block {
            border-left: 3px solid #27ae60;
            padding: 0 0 0 10px;
            margin-top: 15px;
            font-size: 8.5pt;
            color: #5d6d7e;
            page-break-inside: avoid;
        }
        .explanation-block p {
            margin-top: 0;
            margin-bottom: 4px;
        }
        .explanation-block p:last-child {
            margin-bottom: 0;
        }
        .explanation-title {
            font-weight: bold;
            color: #27ae60;
            margin-bottom: 4px;
            font-size: 9pt;
        }
        .footer {
            position: fixed;
            bottom: -1.2cm;
            left: 0px;
            right: 0px;
            height: 30px;
            font-size: 8pt;
            color: #a0aec0;
            line-height: 30px;
        }
        .footer-content {
            margin: 0;
            border-top: 1px solid #e2e8f0;
        }
        
        /* Estilos de la Portada */
        @page :first {
            margin: 0cm;
        }
        .cover-page {
            box-sizing: border-box;
            padding: 2.2cm 2cm 2cm 2cm;
        }
        .cover-header {
            width: 100%;
            font-size: 8.5pt;
            font-weight: bold;
            color: #1e3a8a;
            text-transform: uppercase;
        }
        .cover-header-left {
            float: left;
        }
        .cover-header-right {
            float: right;
            letter-spacing: 0.5px;
        }
        .cover-badge-a {
            border: 3.5px solid #000;
            width: 50px;
            height: 55px;
            line-height: 55px;
            font-size: 38pt;
            font-weight: bold;
            text-align: center;
            margin: 0 auto;
            font-family: Arial, sans-serif;
        }
        .cover-subtitle-escala {
            text-align: center;
            font-size: 13.5pt;
            font-weight: bold;
            margin-top: 15px;
            color: #000;
            letter-spacing: -0.2px;
        }
        .cover-subtitle-promo {
            text-align: center;
            font-size: 11pt;
            font-weight: bold;
            margin-top: 3px;
            color: #000;
        }
        .cover-logo-container {
            text-align: center;
        }
        .cover-logo {
            height: 55px;
            width: auto;
        }
        .cover-title-box {
            border: 4px solid #000;
            padding: 12px;
            text-align: center;
            font-size: 15pt;
            font-weight: bold;
            margin-top: 15px;
            margin-bottom: 25px;
            font-family: Arial, sans-serif;
            letter-spacing: 0.5px;
        }
        .cover-fields {
            margin-bottom: 30px;
        }
        .cover-instructions {
            width: 100%;
            margin-bottom: 20px;
        }
        .instructions-heading {
            font-size: 9.5pt;
            font-weight: bold;
            text-decoration: underline;
            margin-bottom: 8px;
            color: #000;
        }
        .instructions-list {
            margin: 0;
            padding-left: 15px;
            list-style-type: square;
        }
        .instructions-list li {
            margin-bottom: 6px;
            font-size: 8.5pt;
            line-height: 1.35;
        }
        .cover-qr-box {
            width: 100%;
            text-align: center;
            margin-top: 15px;
            margin-bottom: 15px;
        }
        .cover-qr-img {
            width: 120px;
            height: 120px;
            display: block;
            margin: 0 auto;
        }
        .cover-qr-text {
            font-size: 9.5pt;
            font-weight: bold;
            margin-top: 6px;
            color: #000;
        }
        .cover-btn-download {
            display: block;
            width: 160px;
            margin: 8px auto 0 auto;
            background-color: #1e3a8a;
            color: #ffffff !important;
            text-decoration: none;
            font-weight: bold;
            font-size: 9pt;
            padding: 7px 12px;
            border-radius: 4px;
            text-align: center;
        }
        .cover-footer-notice {
            clear: both;
            text-align: center;
            font-size: 13pt;
            font-weight: bold;
            margin-top: 40px;
            letter-spacing: 0.2px;
            font-family: Arial, sans-serif;
        }
        
        /* Estilos de la Portada del Solucionario */
        .sol-cover-title {
            text-align: center;
            font-size: 16pt;
            font-weight: bold;
            margin-top: 10px;
            margin-bottom: 5px;
            color: #2c3e50;
        }
        .sol-cover-subtitle {
            text-align: center;
            font-size: 13pt;
            font-weight: bold;
            color: #27ae60;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 30px;
        }
        .sol-table-title {
            text-align: center;
            font-size: 12pt;
            font-weight: bold;
            margin-bottom: 12px;
            color: #2c3e50;
            text-transform: uppercase;
        }
        .sol-table {
            width: 85%;
            margin: 0 auto;
            border-collapse: collapse;
            font-size: 9.5pt;
        }
        .sol-table td {
            border: 1px solid #cbd5e1;
            padding: 5px 8px;
            text-align: center;
            width: 25%;
        }
        .sol-table td.empty-cell {
            background-color: #f8fafc;
            color: #cbd5e1;
        }
    </style>
</head>
<body>

    <!-- Marca de agua -->
    <?php if (!empty($watermark_path) && file_exists($watermark_path)) : ?>
        <img id="watermark" src="<?php echo $watermark_path; ?>" />
    <?php endif; ?>

    <!-- Portada (Cover Page) -->
    <?php if (!$with_answers) : ?>
        <div class="cover-page">
            <div style="height: 10px;"></div>
            
            <div class="cover-badge-a">A</div>
            <div class="cover-subtitle-escala">ESCALA BÁSICA DE LA POLICÍA NACIONAL</div>
            
            <div style="height: 35px;"></div>
            
            <div class="cover-logo-container">
                <?php if (!empty($logo_path) && file_exists($logo_path)) : ?>
                    <img src="<?php echo $logo_path; ?>" class="cover-logo" />
                <?php endif; ?>
            </div>
            
            <div style="height: 35px;"></div>
            
            <div class="cover-title-box">
                <?php echo esc_html(strtoupper($title)); ?>
            </div>
            
            <div class="cover-fields">
                <table style="width: 100%; border-collapse: collapse;">
                    <tr>
                        <td style="font-weight: bold; font-style: italic; font-size: 10pt; vertical-align: bottom; white-space: nowrap;">
                            APELLIDOS Y NOMBRE: <span style="display: inline-block; width: 330px; border-bottom: 1.5px solid #000; margin-bottom: 2px;"></span>
                        </td>
                        <td style="font-weight: bold; font-style: italic; font-size: 10pt; text-align: right; vertical-align: bottom; white-space: nowrap; padding-left: 20px;">
                            D.N.I.: <span style="display: inline-block; width: 110px; border-bottom: 1.5px solid #000; margin-bottom: 2px;"></span>
                        </td>
                    </tr>
                </table>
            </div>
            
            <div class="cover-instructions">
                <div class="instructions-heading">INSTRUCCIONES</div>
                <ul class="instructions-list">
                    <li>La siguiente prueba consta de <strong><?php echo count($questions); ?></strong> preguntas. Si en el transcurso de la prueba observa que le falta alguna, comuníquelo a algún miembro del Tribunal o colaborador.</li>
                    <li>Cada pregunta solo tiene una respuesta correcta.</li>
                    <li>Los errores penalizan.</li>
                    <li>Solo está permitido bolígrafo azul o negro, tipo Bic.</li>
                    <li>Las contestaciones a las preguntas debe marcarlas en la hoja de respuestas <strong>A9</strong>, zona 1.</li>
                    <?php if ($duration > 0) : ?>
                        <li>Dispone de <strong><?php echo strtoupper(spn_number_to_words_es($duration)); ?> MINUTOS</strong>.</li>
                    <?php endif; ?>
                </ul>
            </div>
            
            <div class="cover-qr-box">
                <?php if (!empty($qr_path) && file_exists($qr_path)) : ?>
                    <img src="<?php echo $qr_path; ?>" class="cover-qr-img" />
                    <div class="cover-qr-text">Hoja A9</div>
                    <a href="https://drive.google.com/file/d/15xV1omkjQIpYmGFXbF2a7-bcvyy4nxzJ/" class="cover-btn-download">Descargar Hoja A9</a>
                <?php endif; ?>
            </div>
            
            <div class="cover-footer-notice">
                NO PASE LA PÁGINA HASTA QUE SE INDIQUE
            </div>
        </div>
    <?php else : ?>
        <!-- Portada del Solucionario -->
        <div class="cover-page">
            <div class="cover-logo-container" style="margin-bottom: 25px;">
                <?php if (!empty($logo_path) && file_exists($logo_path)) : ?>
                    <img src="<?php echo $logo_path; ?>" class="cover-logo" />
                <?php endif; ?>
            </div>
            
            <div class="sol-cover-title"><?php echo esc_html(strtoupper($title)); ?></div>
            <div class="sol-cover-subtitle">Retroalimentación</div>
            
            <div class="sol-table-title">Soluciones</div>
            
            <?php
            $rows = max(25, ceil(count($questions) / 4));
            ?>
            <table class="sol-table">
                <?php for ($r = 0; $r < $rows; $r++) : ?>
                    <tr>
                        <?php for ($c = 0; $c < 4; $c++) : ?>
                            <?php
                            $q_idx = $r + ($c * $rows);
                            if (isset($questions[$q_idx])) {
                                $q_num = $q_idx + 1;
                                $correct_letter = spn_get_correct_answer_letter($questions[$q_idx]);
                                echo "<td><strong>{$q_num}.</strong> {$correct_letter}</td>";
                            } else {
                                echo "<td class=\"empty-cell\">-</td>";
                            }
                            ?>
                        <?php endfor; ?>
                    </tr>
                <?php endfor; ?>
            </table>
        </div>
    <?php endif; ?>

    <!-- Salto de página para comenzar las preguntas en la página 2 -->
    <div style="page-break-after: always;"></div>

    <!-- Encabezado -->
    <div class="header">
        <div class="header-meta">
            <span style="vertical-align: middle;">
                <img src="<?php echo spn_get_clock_icon_base64(); ?>" style="width: 12px; height: 12px; vertical-align: middle; margin-right: 2px;" />
                <span style="vertical-align: middle;"><?php echo esc_html($duration); ?> min</span>
            </span>
            <span style="margin-left: 15px; vertical-align: middle;">
                <img src="<?php echo spn_get_doc_icon_base64(); ?>" style="width: 12px; height: 12px; vertical-align: middle; margin-right: 2px;" />
                <span style="vertical-align: middle;"><?php echo count($questions); ?></span>
            </span>
        </div>
        <?php if (!empty($logo_path) && file_exists($logo_path)) : ?>
            <img class="header-logo" src="<?php echo $logo_path; ?>" />
        <?php else : ?>
            <span class="header-logo-text">Seguimiento PN</span>
        <?php endif; ?>
        <div class="header-title-container">
            <div class="header-title" style="<?php echo $with_answers ? 'line-height: 18px; margin-top: 2px;' : 'line-height: 38px;'; ?>">
                <?php echo esc_html($title); ?>
            </div>
            <?php if ($with_answers) : ?>
                <div class="header-solucionario-label">RETROALIMENTACIÓN</div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Contenedor de Preguntas -->
    <div class="questions-container">
        <?php if (empty($questions)) : ?>
            <p>No hay preguntas asignadas a este examen.</p>
        <?php else : ?>
            <?php foreach ($questions as $index => $q) : ?>
                <div class="question-block">
                    <div class="question-main">
                        <div class="question-title">
                            <?php echo ($index + 1) . '. ' . esc_html($q['title']); ?>
                        </div>
                        
                        <div class="options-list">
                            <?php 
                            $letters = ['a', 'b', 'c', 'd', 'e', 'f', 'g'];
                            foreach ($q['options'] as $o_idx => $opt) : 
                                $letter = isset($letters[$o_idx]) ? $letters[$o_idx] : chr(97 + $o_idx);
                                $is_correct_opt = $with_answers && !empty($opt['correct']);
                                $option_class = $is_correct_opt ? 'option-item option-correct' : 'option-item';
                                ?>
                                <div class="<?php echo $option_class; ?>">
                                    <strong><?php echo esc_html(strtoupper($letter)); ?>)</strong> <?php echo esc_html($opt['option']); ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <?php 
                    $cleaned_explanation = '';
                    if ($with_answers && !empty($q['explanation'])) {
                        $html = $q['explanation'];
                        // 1. Eliminar párrafos que estén completamente vacíos o contengan solo espacios/saltos
                        $html = preg_replace('/<p[^>]*>(\s|&nbsp;|<br\s*\/?>)*<\/p>/i', '', $html);
                        // 2. Eliminar saltos y espacios al inicio del contenido de cualquier párrafo <p...>
                        $html = preg_replace('/(<p[^>]*>)(?:\s|&nbsp;|<br\s*\/?>)+/i', '$1', $html);
                        // 3. Eliminar saltos y espacios al final del contenido de cualquier párrafo ...</p>
                        $html = preg_replace('/(?:\s|&nbsp;|<br\s*\/?>)+(<\/p>)/i', '$1', $html);
                        // 4. Eliminar saltos y espacios al inicio y final del HTML completo
                        $html = preg_replace('/^(?:\s|&nbsp;|<br\s*\/?>)+/i', '', $html);
                        $html = preg_replace('/(?:\s|&nbsp;|<br\s*\/?>)+$/i', '', $html);
                        
                        $cleaned_explanation = trim($html);
                    }
                    if (!empty($cleaned_explanation)) : 
                    ?>
                        <div class="explanation-block">
                            <div class="explanation-title">Explicación:</div>
                            <div>
                                <?php echo $cleaned_explanation; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <div class="footer">
        <div class="footer-content">
            <span style="float: left;">&copy; <?php echo date('Y'); ?> Seguimiento PN - Todos los derechos reservados.</span>
            <div style="clear: both;"></div>
        </div>
    </div>

</body>
</html>
