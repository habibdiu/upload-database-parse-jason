<?php
$columns = [];
$uniqueTypes = [];
$uploadedFileName = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['sqlFile'])) {
    $uploadedFileName = $_FILES['sqlFile']['name'] ?? 'uploaded_file.sql';
    $sqlContent = file_get_contents($_FILES['sqlFile']['tmp_name']);

    if (preg_match('/CREATE TABLE.*?\((.*?)\)[^)]*;/is', $sqlContent, $matches)) {
        $columnDefs = preg_split("/,\n|\n/", trim($matches[1]));

        foreach ($columnDefs as $def) {
            if (preg_match('/`(\w+)`\s+([a-zA-Z0-9\(\)]+)/', trim($def), $colMatch)) {
                $colName = $colMatch[1];
                $colType = $colMatch[2];

                $columns[] = [
                    'name' => $colName,
                    'type' => $colType,
                ];

                $uniqueTypes[] = $colType;
            }
        }
        $uniqueTypes = array_unique($uniqueTypes);
        sort($uniqueTypes);
    }
}


$inputTypeMap = [
    'int' => 'Number',
    'tinyint' => 'Number',
    'smallint' => 'Number',
    'mediumint' => 'Number',
    'bigint' => 'Number',
    'decimal' => 'Number',
    'float' => 'Number',
    'double' => 'Number',
    'varchar' => 'Text',
    'char' => 'Text',
    'text' => 'Text',
    'mediumtext' => 'Text',
    'longtext' => 'Text',
    'datetime' => 'Date',
    'timestamp' => 'Date',
    'date' => 'Date',
    'time' => 'Time',
    'year' => 'Number',
    'boolean' => 'Checkbox',
    'bit' => 'Checkbox',
    'enum' => 'Select',
    'set' => 'Select',
];

foreach ($columns as &$col) {
    if (preg_match('/^([a-z]+)\b/i', $col['type'], $match)) {
        $baseType = strtolower($match[1]);
    } else {
        $baseType = strtolower($col['type']);
    }

    $col['friendly_input_type'] = $inputTypeMap[$baseType] ?? 'Text';
}
unset($col);


$uniqueFriendlyInputTypes = [];
foreach ($columns as $col) {
    $uniqueFriendlyInputTypes[$col['friendly_input_type']] = true;
}
$uniqueFriendlyInputTypes = array_keys($uniqueFriendlyInputTypes);
sort($uniqueFriendlyInputTypes);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>SQL Column Extractor</title>

  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

  <style>
    .select2-container--default .select2-selection--single {
      height: 2.5rem;
      padding: 0.5rem;
      border-radius: 0.375rem;
      border: 1px solid #d1d5db;
    }
    .select2-container--default .select2-selection--single .select2-selection__rendered {
      line-height: 1.5rem;
      color: #374151;
    }
    .select2-container--default .select2-selection--single .select2-selection__arrow {
      height: 2.5rem;
    }
  </style>
</head>
<body class="bg-gray-50 text-gray-800 p-6">

  <div class="max-w-4xl mx-auto bg-white shadow-sm rounded-xl p-8 border border-gray-200">
    <h1 class="text-2xl font-semibold text-center mb-6">Upload .SQL File</h1>

    <form method="POST" enctype="multipart/form-data" class="space-y-4 mb-8" id="uploadForm">
      <div>
        <label for="sqlUpload" class="block text-sm font-medium mb-1">Choose a .sql file</label>
        <input name="sqlFile" id="sqlUpload" type="file" accept=".sql"
          class="w-full file:bg-blue-500 file:text-white file:px-4 file:py-2 file:rounded-md
                 file:border-0 file:text-sm hover:file:bg-blue-600 transition cursor-pointer" />
      </div>
      <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 rounded-md">
        Upload & Preview
      </button>
    </form>

    <?php if (!empty($columns)) : ?>
      <form method="POST" id="finalForm" class="space-y-6">
        <input type="hidden" id="originalFilename" value="<?= htmlspecialchars($uploadedFileName) ?>">

        <div class="overflow-x-auto rounded-lg border border-gray-200">
          <table class="min-w-full text-sm text-left">
            <thead class="bg-gray-100 border-b text-xs font-semibold">
              <tr>
                <th class="px-4 py-2">Column Name</th>
                <th class="px-4 py-2">DB Type</th>
                <th class="px-4 py-2">Input Type</th>
                <th class="px-4 py-2 text-center">Show</th>
                <th class="px-4 py-2 text-center">Required</th>
              </tr>
            </thead>
            <tbody class="divide-y">
              <?php foreach ($columns as $col): ?>
                <tr class="hover:bg-gray-50">
                  <td class="px-4 py-2"><?= htmlspecialchars($col['name']) ?></td>
                  <td class="px-4 py-2"><?= htmlspecialchars($col['type']) ?></td>
                  <td class="px-4 py-2 w-52">
                    <select class="input-type w-full select2">
                      <?php foreach ($uniqueFriendlyInputTypes as $friendlyType): ?>
                        <option value="<?= htmlspecialchars($friendlyType) ?>" <?= $friendlyType === $col['friendly_input_type'] ? 'selected' : '' ?>>
                          <?= htmlspecialchars($friendlyType) ?>
                        </option>
                      <?php endforeach; ?>
                    </select>
                  </td>
                  <td class="px-4 py-2 text-center">
                    <input type="checkbox" checked class="h-5 w-5 text-blue-600 rounded" />
                  </td>
                  <td class="px-4 py-2 text-center">
                    <input type="checkbox" checked class="h-5 w-5 text-red-600 rounded" />
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>

        <div class="text-center">
          <button type="submit" class="bg-green-600 hover:bg-green-700 text-white font-medium py-2 px-6 rounded-md">
            Generate JSON
          </button>
        </div>
      </form>
    <?php endif; ?>
  </div>

  <script>
    $(document).ready(function () {
      $('.select2').select2({
        placeholder: "Select input type",
        allowClear: false,
        width: 'resolve'
      });

      $('#finalForm').on('submit', function (e) {
        e.preventDefault();

        const data = [];

        $('tbody tr').each(function () {
          const row = $(this);
          const name = row.find('td').eq(0).text().trim();
          const dbType = row.find('td').eq(1).text().trim();
          const inputType = row.find('select.input-type').val();
          const show = row.find('input[type="checkbox"]').eq(0).is(':checked');
          const required = row.find('input[type="checkbox"]').eq(1).is(':checked');

          data.push({
            name,
            db_type: dbType,
            input_type: inputType,
            show,
            required
          });
        });

        const originalFile = $('#originalFilename').val() || 'uploaded_file.sql';

        const form = $('<form>', {
          method: 'POST',
          action: 'parse.php'
        });

        form.append($('<input>', {
          type: 'hidden',
          name: 'jsonData',
          value: JSON.stringify(data)
        }));

        form.append($('<input>', {
          type: 'hidden',
          name: 'originalFilename',
          value: originalFile
        }));

        $('body').append(form);
        form.submit();
      });
    });
  </script>
</body>
</html>
