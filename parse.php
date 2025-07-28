<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['jsonData'])) {
    $json = $_POST['jsonData'];
    $data = json_decode($json, true);

    
    $originalFilename = $_POST['originalFilename'] ?? 'uploaded_file.sql';
    $originalFilename = basename($originalFilename);
    $originalFilename = preg_replace('/[^a-zA-Z0-9_\-\.]/', '_', $originalFilename);

    
    $baseName = pathinfo($originalFilename, PATHINFO_FILENAME);

    
    date_default_timezone_set('Asia/Dhaka');
    $date = date('d-m-Y_H_i');
    $fileName = "{$baseName}_{$date}.txt";



    
    $text = "Parsed Column Details:\n\n";
    foreach ($data as $col) {
        $text .= "- Column Name: {$col['name']}\n";
        $text .= "  DB Type: {$col['db_type']}\n";
        $text .= "  Input Type: {$col['input_type']}\n";
        $text .= "  Show: " . ($col['show'] ? 'Yes' : 'No') . "\n";
        $text .= "  Required: " . ($col['required'] ? 'Yes' : 'No') . "\n\n";
    }

    
    file_put_contents($fileName, $text);

} else {
    die("Invalid request.");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>SQL IN JSON</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 text-gray-800 p-6">
  <div class="max-w-3xl mx-auto bg-white p-8 rounded-xl shadow">
    <h1 class="text-2xl font-semibold mb-4">Submit</h1>
    <pre style="white-space: pre-wrap; word-break: break-word;" class="bg-gray-100 p-4 rounded border border-gray-300 text-sm overflow-y-auto max-h-96"><?= htmlspecialchars($json) ?></pre>

    <div class="mt-6 text-center">
      <a href="<?= htmlspecialchars($fileName) ?>" download="<?= htmlspecialchars($fileName) ?>" class="inline-block bg-green-600 hover:bg-green-700 text-white px-6 py-2 rounded-md">
        Download
      </a>
    </div>

    <div class="mt-4 text-center">
      <a href="index.php" class="text-blue-600 hover:underline">← Back</a>
    </div>
  </div>
</body>
</html>
