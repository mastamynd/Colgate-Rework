<?php

namespace Tests\Feature;

use App\Models\MapData;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class MapDataUploadTest extends TestCase
{
    use RefreshDatabase;

    public function test_upload_requires_valid_file()
    {
        $user = User::factory()->create();
        $mapData = MapData::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->post(route('map-data.upload-rows'), [
            'map_data_id' => $mapData->id
            // Missing excel_file
        ]);

        $response->assertSessionHasErrors(['excel_file']);
    }

    public function test_upload_requires_valid_map_data_id()
    {
        $user = User::factory()->create();
        $file = UploadedFile::fake()->create('test.xlsx');

        $response = $this->actingAs($user)->post(route('map-data.upload-rows'), [
            'excel_file' => $file,
            'map_data_id' => 999 // Non-existent ID
        ]);

        $response->assertSessionHasErrors(['map_data_id']);
    }

    public function test_upload_validates_file_type()
    {
        $user = User::factory()->create();
        $mapData = MapData::factory()->create(['user_id' => $user->id]);
        $file = UploadedFile::fake()->create('test.txt'); // Wrong file type

        $response = $this->actingAs($user)->post(route('map-data.upload-rows'), [
            'excel_file' => $file,
            'map_data_id' => $mapData->id
        ]);

        $response->assertSessionHasErrors(['excel_file']);
    }

    public function test_map_data_can_get_dynamic_row_count()
    {
        $user = User::factory()->create();
        $mapData = MapData::factory()->create(['user_id' => $user->id]);

        // Initially should have no dynamic table
        $this->assertFalse($mapData->hasDynamicTable());
        $this->assertEquals(0, $mapData->getDynamicRowCount());
    }

    public function test_map_data_deletes_dynamic_table_on_delete()
    {
        $user = User::factory()->create();
        $mapData = MapData::factory()->create([
            'user_id' => $user->id,
            'data_table' => 'mp_data_test_table'
        ]);

        // Create a test table
        Schema::create('mp_data_test_table', function ($table) {
            $table->id();
            $table->uuid('map_data_id');
            $table->string('test_column')->nullable();
            $table->timestamps();
        });

        $this->assertTrue(Schema::hasTable('mp_data_test_table'));

        // Delete the MapData
        $mapData->delete();

        // Table should be dropped
        $this->assertFalse(Schema::hasTable('mp_data_test_table'));
    }

    public function test_dynamic_import_logic_works()
    {
        $user = User::factory()->create();
        $mapData = MapData::factory()->create(['user_id' => $user->id]);

        // Test the import logic directly without Excel file
        $import = new \App\Imports\DynamicMapDataImport($mapData);
        
        // Create test data collection
        $testData = collect([
            ['Name' => 'John Doe', 'Age' => 25, 'Latitude' => 40.7128, 'Longitude' => -74.0060, 'Is_Active' => true, 'Created_Date' => '2023-01-15'],
            ['Name' => 'Jane Smith', 'Age' => 30, 'Latitude' => 34.0522, 'Longitude' => -118.2437, 'Is_Active' => false, 'Created_Date' => '2023-02-20'],
            ['Name' => 'Bob Johnson', 'Age' => 35, 'Latitude' => 41.8781, 'Longitude' => -87.6298, 'Is_Active' => true, 'Created_Date' => '2023-03-10'],
        ]);

        // Call the collection method directly
        $import->collection($testData);

        // Refresh the model to get updated data
        $mapData->refresh();

        // Check that the dynamic table was created
        $this->assertTrue($mapData->hasDynamicTable());
        $this->assertNotNull($mapData->data_table);
        $this->assertNotNull($mapData->data_columns);

        // Check that the table exists in the database
        $this->assertTrue(Schema::hasTable($mapData->data_table));

        // Check that data was imported
        $this->assertEquals(3, $mapData->getDynamicRowCount());

        // Verify column types by checking the table structure
        $columns = Schema::getColumnListing($mapData->data_table);
        $this->assertContains('name', $columns);
        $this->assertContains('age', $columns);
        $this->assertContains('latitude', $columns);
        $this->assertContains('longitude', $columns);
        $this->assertContains('is_active', $columns);
        $this->assertContains('created_date', $columns);

        // Check that the rows were created correctly
        $this->assertEquals(3, $import->getRowsCreated());
        $this->assertEmpty($import->getErrors());

        // Verify data was inserted correctly
        $dynamicData = $mapData->getDynamicData();
        $this->assertCount(3, $dynamicData);
        
        $firstRow = $dynamicData->first();
        $this->assertEquals('John Doe', $firstRow->name);
        $this->assertEquals(25, $firstRow->age);
        $this->assertEquals(40.7128, $firstRow->latitude);
        $this->assertEquals(-74.0060, $firstRow->longitude);
    }
}