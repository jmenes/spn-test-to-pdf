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
            margin: 2.2cm 2cm 2.2cm 2cm;
        }
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            color: #2c3e50;
            line-height: 1.5;
            font-size: 10.5pt;
        }
        /* Marca de agua centrada en cada página */
        #watermark {
            position: fixed;
            top: 50%;
            left: 50%;
            width: 70%;
            height: auto;
            margin-left: -35%; /* Centrado horizontal */
            margin-top: -35%;  /* Centrado vertical aproximado */
            opacity: 0.05;
            z-index: -1000;
        }
        .header {
            border-bottom: 2px solid #34495e;
            padding-bottom: 10px;
            margin-bottom: 25px;
        }
        .logo {
            font-size: 16pt;
            font-weight: bold;
            color: #2c3e50;
            letter-spacing: 1px;
            text-transform: uppercase;
        }
        .header-meta {
            float: right;
            font-size: 9pt;
            color: #7f8c8d;
            text-align: right;
            margin-top: 5px;
        }
        .test-title {
            font-size: 20pt;
            font-weight: bold;
            color: #1a252f;
            margin: 15px 0 5px 0;
            clear: both;
        }
        .test-subtitle {
            font-size: 10.5pt;
            color: #7f8c8d;
            margin-bottom: 25px;
            border-bottom: 1px dashed #e2e8f0;
            padding-bottom: 8px;
        }
        .questions-container {
            margin-top: 15px;
        }
        .question-block {
            page-break-inside: avoid;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 1px solid #f1f2f6;
        }
        .question-title {
            font-weight: bold;
            font-size: 11pt;
            margin-bottom: 10px;
            color: #2c3e50;
        }
        .options-list {
            margin-left: 15px;
            margin-bottom: 10px;
        }
        .option-item {
            margin-bottom: 6px;
            font-size: 10pt;
        }
        .checkbox {
            display: inline-block;
            width: 10px;
            height: 10px;
            border: 1px solid #7f8c8d;
            border-radius: 2px;
            margin-right: 8px;
            vertical-align: middle;
            background-color: #ffffff;
        }
        /* Estilos para el solucionario */
        .option-correct {
            font-weight: bold;
            color: #27ae60;
        }
        .checkbox-correct {
            border-color: #27ae60;
            background-color: #27ae60;
        }
        .explanation-block {
            background-color: #fafbfc;
            border-left: 4px solid #27ae60;
            padding: 12px 15px;
            margin-top: 12px;
            border-radius: 0 4px 4px 0;
            font-size: 9.5pt;
            color: #5d6d7e;
        }
        .explanation-title {
            font-weight: bold;
            color: #27ae60;
            margin-bottom: 5px;
            font-size: 10pt;
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
            <strong>Tipo:</strong> <?php echo esc_html($type); ?><br>
            <strong>Duración:</strong> <?php echo esc_html($duration); ?> min<br>
            <strong>Preguntas:</strong> <?php echo count($questions); ?>
        </div>
        <div class="logo">Seguimiento PN</div>
    </div>

    <!-- Título del Examen -->
    <div class="test-title"><?php echo esc_html($title); ?></div>
    <div class="test-subtitle">
        <?php if ($with_answers) : ?>
            <strong style="color: #27ae60;">SOLUCIONARIO Y EXPLICACIONES</strong> - Documento de revisión pedagógica.
        <?php else : ?>
            Documento de examen para realización en papel. Marque la opción correcta para cada una de las siguientes preguntas.
        <?php endif; ?>
    </div>

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
                        <?php foreach ($q['options'] as $opt) : ?>
                            <?php 
                            $is_correct_opt = $with_answers && !empty($opt['correct']);
                            $option_class = $is_correct_opt ? 'option-item option-correct' : 'option-item';
                            $checkbox_class = $is_correct_opt ? 'checkbox checkbox-correct' : 'checkbox';
                            ?>
                            <div class="<?php echo $option_class; ?>">
                                <span class="<?php echo $checkbox_class; ?>"></span>
                                <strong><?php echo esc_html(strtoupper($opt['id'])); ?>)</strong> <?php echo esc_html($opt['option']); ?>
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
        &copy; <?php echo date('Y'); ?> menes.studio - Seguimiento PN - Todos los derechos reservados.
    </div>

</body>
</html>
