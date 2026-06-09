<?php
$data = json_decode(file_get_contents('C:\Kuliah\Semester 4\Kecerdasan Mesin\DHC\Models\DidactionModel_01.ipynb'), true);
foreach ($data['cells'] as $i => $cell) {
    $source = is_array($cell['source']) ? implode("", $cell['source']) : $cell['source'];
    echo "Cell $i [{$cell['cell_type']}]: " . substr(str_replace("\n", " ", $source), 0, 150) . "\n";
}
