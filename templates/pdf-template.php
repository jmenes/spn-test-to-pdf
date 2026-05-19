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
            top: 25%;
            left: 15%;
            width: 70%;
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
        .logo {
            float: left;
        }
        .header-title {
            font-size: 10.5pt;
            font-weight: bold;
            color: #475569;
            margin-left: 12px;
            border-left: 1px solid #e2e8f0;
            padding-left: 12px;
            vertical-align: middle;
            display: inline-block;
            line-height: 38px;
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
        .test-title {
            font-size: 16pt;
            font-weight: bold;
            color: #1a252f;
            margin: 15px 0 5px 0;
            clear: both;
        }
        .test-subtitle {
            font-size: 10pt;
            color: #7f8c8d;
            margin-bottom: 20px;
            border-bottom: 1px dashed #e2e8f0;
            padding-bottom: 8px;
        }
        .questions-container {
            margin-top: 15px;
        }
        .question-block {
            page-break-inside: avoid;
            margin-bottom: 20px;
            padding-bottom: 12px;
            border-bottom: 1px solid #f1f2f6;
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
            margin-top: 8px;
            font-size: 8.5pt;
            color: #5d6d7e;
        }
        .explanation-title {
            font-weight: bold;
            color: #27ae60;
            margin-bottom: 3px;
            font-size: 9pt;
        }
        .footer {
            position: fixed;
            bottom: -1cm;
            left: 0px;
            right: 0px;
            height: 30px;
            border-top: 1px solid #e2e8f0;
            text-align: center;
            font-size: 8pt;
            color: #a0aec0;
            line-height: 30px;
        }
    </style>
</head>
<body>

    <!-- Marca de agua -->
    <?php if (!empty($watermark_path) && file_exists($watermark_path)) : ?>
        <img id="watermark" src="<?php echo $watermark_path; ?>" />
    <?php endif; ?>

    <!-- Encabezado -->
    <div class="header">
        <div class="header-meta">
            <span style="vertical-align: middle;">
                <span class="icon">&#9201;</span>
                <span style="vertical-align: middle;"><?php echo esc_html($duration); ?> min</span>
            </span>
            <span style="margin-left: 15px; vertical-align: middle;">
                <span class="icon">&#9776;</span>
                <span style="vertical-align: middle;"><?php echo count($questions); ?></span>
            </span>
        </div>
        <div class="logo">
            <?php if (!empty($logo_path) && file_exists($logo_path)) : ?>
                <img src="<?php echo $logo_path; ?>" style="height: 38px; width: auto; vertical-align: middle;" />
            <?php else : ?>
                Seguimiento PN
            <?php endif; ?>
            <span class="header-title"><?php echo esc_html($title); ?></span>
        </div>
    </div>

    <!-- Título del Examen -->
    <div class="test-title"><?php echo esc_html($title); ?></div>
    <?php if ($with_answers) : ?>
        <div class="test-subtitle">
            <strong style="color: #27ae60;">SOLUCIONARIO Y EXPLICACIONES</strong>
        </div>
    <?php endif; ?>

    <!-- Contenedor de Preguntas -->
    <div class="questions-container">
        <?php if (empty($questions)) : ?>
            <p>No hay preguntas asignadas a este examen.</p>
        <?php else : ?>
            <?php foreach ($questions as $index => $q) : ?>
                <div class="question-block">
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

                    <?php if ($with_answers && !empty($q['explanation'])) : ?>
                        <div class="explanation-block">
                            <div class="explanation-title">Explicación:</div>
                            <div>
                                <?php 
                                // Renderizar el contenido HTML directo de la explicación
                                echo $q['explanation']; 
                                ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <div class="footer">
        &copy; <?php echo date('Y'); ?> Seguimiento PN - Todos los derechos reservados.
    </div>

</body>
</html>
