<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind different classes or traits.
|
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

/**
 * Construye un .xlsx ficticio en memoria con la forma que exporta la Agencia
 * Virtual del SAT (hoja InformacionDTE-FEL), para probar el importador sin
 * commitear jamás un archivo real. Cada fila es un array asociativo con las
 * llaves siendo los encabezados exactos del §2 del brief.
 *
 * @param  array<int, array<string, mixed>>  $filas
 * @param  array{encabezados?: array<int, string>, nombreHoja?: string, nombreArchivo?: string}  $opciones
 */
function construirArchivoSat(array $filas = [], array $opciones = []): UploadedFile
{
    $encabezados = $opciones['encabezados'] ?? [
        'Fecha de emisión',
        'Número de Autorización',
        'Tipo de DTE (nombre)',
        'Serie',
        'Número del DTE',
        'NIT del emisor',
        'Nombre completo del emisor',
        'ID del receptor',
        'Nombre completo del receptor',
        'Estado',
        'Marca de anulado',
        'Moneda',
        'Gran Total (Moneda Original)',
        'IVA (monto de este impuesto)',
        'Petróleo (monto de este impuesto)',
    ];

    $spreadsheet = new Spreadsheet;
    $hoja = $spreadsheet->getActiveSheet();
    $hoja->setTitle($opciones['nombreHoja'] ?? 'InformacionDTE-FEL');

    foreach ($encabezados as $indice => $encabezado) {
        $hoja->setCellValue([$indice + 1, 1], $encabezado);
    }

    foreach (array_values($filas) as $numeroFila => $fila) {
        foreach ($encabezados as $indice => $encabezado) {
            $hoja->setCellValue([$indice + 1, $numeroFila + 2], $fila[$encabezado] ?? '');
        }
    }

    $ruta = sys_get_temp_dir().'/sat_'.uniqid('', true).'.xlsx';
    (new Xlsx($spreadsheet))->save($ruta);

    return new UploadedFile(
        $ruta,
        $opciones['nombreArchivo'] ?? 'facturas.xlsx',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        null,
        true,
    );
}
