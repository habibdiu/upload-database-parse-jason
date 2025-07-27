<?php
$columns = [];
$uniqueTypes = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['sqlFile'])) {
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
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Upload SQL File</title>

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
<body class="bg-gray-100 p-10">

  <div class="max-w-5xl mx-auto bg-white shadow-md rounded-lg p-6">
    <h2 class="text-2xl font-semibold text-gray-800 mb-8 text-center">
      Upload Database (.sql)
    </h2>

    <form method="POST" enctype="multipart/form-data" class="mb-6">
      <label for="sqlUpload" class="block mb-2 text-sm font-medium text-gray-700">Choose your .sql file:</label>
      <input name="sqlFile" id="sqlUpload" type="file" accept=".sql"
        class="block w-full text-sm text-gray-500 mb-4 file:mr-4 file:py-2 file:px-4 file:rounded file:border-0
               file:text-sm file:font-semibold file:bg-green-400 file:text-white hover:file:bg-green-700 cursor-pointer" />
      <button type="submit" class="bg-green-500 hover:bg-green-600 text-white font-semibold px-4 py-2 rounded">
        Upload & Parse
      </button>
    </form>

    <?php if (!empty($columns)) : ?>
      <form method="POST" id="finalForm">
        <div class="overflow-x-auto">
          <table class="min-w-full border border-gray-300 divide-y divide-gray-200">
            <thead class="bg-green-500 text-white">
              <tr>
                <th class="px-6 py-3 text-left text-xs font-semibold uppercase">Column Name</th>
                <th class="px-6 py-3 text-left text-xs font-semibold uppercase">Database Type</th>
                <th class="px-6 py-3 text-left text-xs font-semibold uppercase">Input Type</th>
                <th class="px-6 py-3 text-center text-xs font-semibold uppercase">Show</th>
                <th class="px-6 py-3 text-center text-xs font-semibold uppercase">Required</th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
              <?php foreach ($columns as $col): ?>
                <tr>
                  <td class="px-6 py-4 text-gray-700 font-medium"><?= htmlspecialchars($col['name']) ?></td>
                  <td class="px-6 py-4 text-gray-700"><?= htmlspecialchars($col['type']) ?></td>
                  <td class="px-6 py-4 w-48">
                    <select class="input-type w-full select2">
                      <?php foreach ($uniqueTypes as $type): ?>
                        <option value="<?= htmlspecialchars($type) ?>" <?= $type === $col['type'] ? 'selected' : '' ?>>
                          <?= htmlspecialchars($type) ?>
                        </option>
                      <?php endforeach; ?>
                    </select>
                  </td>
                  <td class="px-6 py-4 text-center">
                    <input type="checkbox" checked class="h-5 w-5 text-green-600 rounded" />
                  </td>
                  <td class="px-6 py-4 text-center">
                    <input type="checkbox" checked class="h-5 w-5 text-red-600 rounded" />
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>

        <div class="flex justify-center mt-6">
          <button type="submit"
            class="bg-blue-600 hover:bg-blue-700 text-white font-semibold px-6 py-2 rounded">
            Generate JSON
          </button>
        </div>
      </form>

      <div id="output" class="mt-8 bg-gray-100 p-4 rounded border border-gray-300 text-sm text-gray-800 hidden"></div>
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

        const output = JSON.stringify(data, null, 2);
        $('#output').removeClass('hidden').html(`<pre>${output}</pre>`);
      });
    });
  </script>
</body>
</html>
