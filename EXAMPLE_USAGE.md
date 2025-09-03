# MapData Dynamic Table - Example Usage

## Example Excel File Structure

Create an Excel file with the following structure:

### Sample Data (save as `sample_locations.xlsx`):
```
| latitude  | longitude  | name          | description              | category | population | is_capital | founded_at          |
|-----------|------------|---------------|--------------------------|----------|------------|------------|---------------------|
| 40.7128   | -74.0060   | New York      | The Big Apple            | city     | 8336817    | false      | 1624-01-01 00:00:00 |
| 34.0522   | -118.2437  | Los Angeles   | City of Angels           | city     | 3979576    | false      | 1781-09-04 00:00:00 |
| 41.8781   | -87.6298   | Chicago       | The Windy City           | city     | 2693976    | false      | 1833-08-12 00:00:00 |
| 29.7604   | -95.3698   | Houston       | Space City               | city     | 2320268    | false      | 1836-08-30 00:00:00 |
| 33.4484   | -112.0740  | Phoenix       | Valley of the Sun        | city     | 1680992    | false      | 1868-05-04 00:00:00 |
```

## Step-by-Step Usage

### 1. Create MapData Record
```php
$mapData = MapData::create([
    'name' => 'US Major Cities',
    'description' => 'Population and location data for major US cities',
    'type' => 'point',
    'user_id' => auth()->id(),
    'is_active' => true
]);
```

### 2. Upload Excel File
Using the upload form or API:
```bash
curl -X POST /map-data/upload-rows \
  -F "excel_file=@sample_locations.xlsx" \
  -F "map_data_id={mapData->id}"
```

### 3. Generated Table Structure
The system will create table `mp_data_us_major_cities_{id_suffix}` with:
```sql
CREATE TABLE mp_data_us_major_cities_123e4567 (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    map_data_id UUID NOT NULL,
    latitude DECIMAL(10,8),           -- Auto-detected as coordinate
    longitude DECIMAL(10,8),          -- Auto-detected as coordinate  
    name TEXT,                        -- Default text type
    description TEXT,                 -- Default text type
    category TEXT,                    -- Default text type
    population BIGINT,                -- Auto-detected as number
    is_capital BOOLEAN,               -- Auto-detected as boolean
    founded_at TIMESTAMP,             -- Auto-detected as timestamp
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    FOREIGN KEY (map_data_id) REFERENCES map_data(id) ON DELETE CASCADE,
    INDEX idx_coordinates (latitude, longitude)
);
```

### 4. Access Dynamic Data
```php
// Check if table exists
if ($mapData->hasDynamicTable()) {
    echo "Table: " . $mapData->data_table;
    echo "Columns: " . implode(', ', $mapData->data_columns);
    echo "Row Count: " . $mapData->getDynamicRowCount();
    
    // Get all data
    $data = $mapData->getDynamicData();
    foreach ($data as $row) {
        echo "{$row->name}: {$row->latitude}, {$row->longitude}";
    }
}
```

### 5. Query via API
```javascript
// Get paginated data with search
fetch('/map-data/123e4567-e89b-12d3-a456-426614174000/data?search=New&per_page=10')
    .then(response => response.json())
    .then(result => {
        console.log('Found rows:', result.data.data.length);
        console.log('Total rows:', result.data.total);
        console.log('Columns:', result.columns);
        
        result.data.data.forEach(row => {
            console.log(`${row.name}: Population ${row.population}`);
        });
    });
```

## Column Type Detection Examples

### Coordinate Columns
These headers will be detected as `DECIMAL(10,8)`:
- `latitude`, `lat`
- `longitude`, `lng`, `lon`

### Numeric Columns  
These headers will be detected as `BIGINT`:
- `id`, `count`, `number`, `quantity`, `population`, `total`

### Boolean Columns
These headers will be detected as `BOOLEAN`:
- `is_active`, `is_capital`, `is_enabled`
- `has_data`, `has_location`, `has_children`

### Timestamp Columns
These headers will be detected as `TIMESTAMP`:
- `created_at`, `updated_at`, `deleted_at`
- `start_date`, `end_date`, `birth_date`
- `login_time`, `access_time`, `process_time`

### Text Columns (Default)
All other headers become `TEXT` columns:
- `name`, `description`, `address`, `notes`, `category`

## Advanced Features

### Spatial Point Creation
When both `latitude` and `longitude` columns are present with valid coordinates, the system automatically creates spatial Point objects in the `pointables` table for geographic queries.

### Table Management
```php
// Delete MapData and its dynamic table
$mapData->delete(); // Automatically drops mp_data_{id} table

// Manual table cleanup
$mapData->deleteDynamicTable();
```

### Error Handling
The system provides detailed feedback:
```php
// Upload response with errors
[
    'warning' => 'Excel file imported with 4 rows created, but some errors occurred.',
    'data' => [
        'rows_created' => 4,
        'table_name' => 'mp_data_123e4567_e89b_12d3_a456_426614174000',
        'errors' => [
            'Row 3: Invalid latitude value: not_a_number',
            'Row 7: Failed to create location - longitude out of range'
        ]
    ]
]
```

## Performance Considerations

- **Indexing**: Coordinate columns are automatically indexed
- **Foreign Keys**: All dynamic tables have proper foreign key constraints
- **Cleanup**: Tables are automatically dropped when MapData is deleted
- **Memory**: Large Excel files are processed row-by-row to minimize memory usage

## Best Practices

1. **Column Naming**: Use descriptive headers that match the type detection patterns
2. **Data Validation**: Ensure coordinate values are within valid ranges (-90 to 90 for latitude, -180 to 180 for longitude)
3. **File Size**: Keep Excel files under 10MB for optimal performance
4. **Data Types**: Use consistent data formats within columns
5. **Testing**: Always test with a small sample before uploading large datasets