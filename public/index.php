<?php

require '../vendor/autoload.php';
session_start();

function public_path($path = '')
{
    return __DIR__ . ($path ? DIRECTORY_SEPARATOR . $path : $path);
}

function processPdf($filePath)
{
    $parser = new \Smalot\PdfParser\Parser();
    
    $pdf = $parser->parseFile($filePath);

    $text = $pdf->getText();

    $pattern = '/([A-F]\)) CORRETA/';

    preg_match_all($pattern, $text, $matches);

    return $matches[0];
}

// Processa o upload do PDF
if (isset($_POST['upload_pdf']) && isset($_FILES['pdf_file'])) {
    $uploadedFile = $_FILES['pdf_file'];

    if ($uploadedFile['error'] == UPLOAD_ERR_OK) {
        $tempFilePath = $uploadedFile['tmp_name'];
        $_SESSION['matches'] = processPdf($tempFilePath);
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    } else {
        echo "Erro ao fazer upload do arquivo.";
    }
}

// Geração do CSV e download
if (isset($_POST['download_csv']) && isset($_SESSION['matches'])) {
    $matches = $_SESSION['matches'];
    $filename = "gabarito.csv";

    // Abre o arquivo em modo de escrita
    $file = fopen($filename, 'w');

    // Cabeçalho do CSV (opcional)
    fputcsv($file, ['Gabarito']);

    // Escreve cada resposta no CSV
    foreach ($matches as $match) {
        fputcsv($file, [$match[0]]);
    }

    // Fecha o arquivo
    fclose($file);

    // Força o download do arquivo CSV
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    readfile($filename);

    // Exclui o arquivo CSV temporário
    unlink($filename);
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gabarito</title>
    <style>
        * {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            box-sizing: border-box;
        }

        body {
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 2em;
            background-color: #f9f9f9;
        }

        h1 {
            color: #333;
        }

        .gabarito {
            border: 1px solid black;
            padding: 0.7em;
            border-radius: 0.2em;
            gap: 0.7em;
            width: 65em;
            height: 25em;
            display: flex;
            flex-wrap: wrap;
            background-color: #f1f1f1;
        }

        .gabarito p {
            display: flex;
            align-items: center;
            justify-content: center;
            margin-block: 0em;
            padding: 0.7em 0.9em;
            border: 1px dashed rgb(85, 85, 85);
            border-radius: 0.4em;
            width: 2.5em;
            height: fit-content;
            text-align: center;
            margin-bottom: 0.7em;
            background-color: #f9f9f9;
        }

        button {
            padding: 0.7em 1.4em;
            margin-bottom: 1.5em;
            background-color: #4CAF50;
            color: white;
            border: none;
            border-radius: 0.3em;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        button:hover {
            background-color: #45a049;
        }

        .btn {
            background-color: #265aa7;

            &:hover {
                background-color: #164995;
            }
        }
    </style>
</head>
<body>
    
    <!-- Formulário para upload de PDF -->
    <form method="post" enctype="multipart/form-data">
        <input type="file" name="pdf_file" accept=".pdf" required>
        <button type="submit" name="upload_pdf" class="btn">Upload PDF</button>
    </form>

    <h1>Gabarito de prova</h1>

    <!-- 
        Se o formulário de upload foi enviado e há matches, 
        exibe o botão de download 
    -->
    <?php if (isset($_SESSION['matches']) && !empty($_SESSION['matches'])): ?>
        <form method="post">
            <button type="submit" name="download_csv">Baixar Gabarito CSV</button>
        </form>
    <?php endif; ?>

    <!-- 
        Exibe o gabarito se houver matches 
    -->
    <?php if (isset($_SESSION['matches']) && !empty($_SESSION['matches'])): ?>
        <div class="gabarito">
            <?php foreach ($_SESSION['matches'] as $match): ?>
                <p><?php echo htmlspecialchars($match[0], ENT_QUOTES, 'UTF-8'); ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</body>
</html>
