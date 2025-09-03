<?php

// Debug script to test Excel import without transactions
use App\Models\MapData;
use App\Models\User;
use App\Imports\DynamicMapDataImport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Http\UploadedFile;

echo "Testing Excel import transaction handling...\n";

try {
    // Create test data
    $user = User::first() ?? User::factory()->create();
    $mapData = MapData::create([
        'name' => 'Transaction Test',
        'description' => 'Testing transaction handling',
        'type' => 'point',
        'user_id' => $user->id,
        'is_active' => true
    ]);

    echo "Created MapData: {$mapData->id}\n";

    // Create a simple CSV content
    $csvContent = "latitude,longitude,name\n";
    $csvContent .= "40.7128,-74.0060,New York\n";
    $csvContent .= "34.0522,-118.2437,Los Angeles\n";

    // Create temporary file with .csv extension
    $tempFile = tempnam(sys_get_temp_dir(), 'test_excel_') . '.csv';
    file_put_contents($tempFile, $csvContent);

    echo "Created temp file: {$tempFile}\n";

    // Test the import
    $import = new DynamicMapDataImport($mapData);
    
    echo "Starting Excel import...\n";
    
    // Check current transaction state
    echo "Transaction level before import: " . \Illuminate\Support\Facades\DB::transactionLevel() . "\n";
    
    Excel::import($import, $tempFile);
    
    echo "Excel import completed!\n";
    echo "Rows created: " . $import->getRowsCreated() . "\n";
    echo "Errors: " . count($import->getErrors()) . "\n";
    
    if (!empty($import->getErrors())) {
        foreach ($import->getErrors() as $error) {
            echo "Error: {$error}\n";
        }
    }
    
    // Clean up
    unlink($tempFile);
    
    $tableName = $import->getTableName();
    if (\Illuminate\Support\Facades\Schema::hasTable($tableName)) {
        \Illuminate\Support\Facades\Schema::drop($tableName);
        echo "Cleaned up table: {$tableName}\n";
    }
    
    $mapData->delete();
    echo "Test completed successfully!\n";
    
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
    
    // Clean up on error
    if (isset($tempFile) && file_exists($tempFile)) {
        unlink($tempFile);
    }
    if (isset($mapData)) {
        $tableName = 'mp_data_transaction_test_' . substr(str_replace('-', '', $mapData->id), 0, 8);
        if (\Illuminate\Support\Facades\Schema::hasTable($tableName)) {
            \Illuminate\Support\Facades\Schema::drop($tableName);
        }
        $mapData->delete();
    }
}