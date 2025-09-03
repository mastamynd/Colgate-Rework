# MapData Dynamic Table Upload Documentation

## Overview
The MapData upload functionality dynamically creates database tables based on Excel file structure. Each MapData record can have its own custom table with columns that match the Excel headers.

## Dynamic Table Approach

### Key Features
- **Dynamic Schema**: Tables are created on-the-fly based on Excel column headers
- **Meaningful Table Names**: Tables are named `mp_data_{mapdata_name}_{id_suffix}` for easy identification
- **Intelligent Column Types**: System automatically determines appropriate column types based on header names and data patterns
- **Spatial Support**: Automatic spatial point creation when latitude/longitude columns are present

### Column Type Detection
The system intelligently assigns column types based on header names:

- **Decimal (10,8)**: `latitude`, `lat`, `longitude`, `lng`, `lon`
- **BigInteger**: `id`, `count`, `number`, `quantity`
- **Timestamp**: Headers ending with `_at`, `_date`, `_time`
- **Boolean**: Headers starting with `is_` or `has_`
- **Text**: All other columns (default)

### Example Excel Structure
```
| latitude  | longitude  | name        | description           | category | count | is_active | created_at          |
|-----------|------------|-------------|-----------------------|----------|-------|-----------|---------------------|
| 40.7128   | -74.0060   | New York    | City in New York      | city     | 100   | true      | 2024-01-01 10:00:00 |
| 34.0522   | -118.2437  | Los Angeles | City in California    | city     | 200   | true      | 2024-01-02 11:00:00 |
| 41.8781   | -87.6298   | Chicago     | City in Illinois      | city     | 150   | false     | 2024-01-03 12:00:00 |
```

This would create a table `mp_data_us_major_cities_{id_suffix}` with columns:
- `latitude` (decimal)
- `longitude` (decimal) 
- `name` (text)
- `description` (text)
- `category` (text)
- `count` (bigint)
- `is_active` (boolean)
- `created_at` (timestamp)

## Upload Process

1. **File Validation**: Validates Excel file format and size (max 10MB)
2. **Schema Analysis**: Analyzes first row to determine column structure
3. **Table Creation**: Creates/recreates dynamic table with appropriate column types
4. **Data Import**: Imports all rows with proper type casting
5. **Spatial Processing**: Creates spatial points for valid coordinate pairs
6. **Metadata Storage**: Stores table name and column information in MapData record

## API Endpoints

### Upload Excel File
**POST** `/map-data/upload-rows`

#### Parameters
- `excel_file` (required): Excel file (.xlsx or .xls, max 10MB)
- `map_data_id` (required): ID of the MapData record

#### Response
- Success: Redirects with success message, row count, and table name
- Errors: Redirects with detailed error information

### Get Dynamic Table Data
**GET** `/map-data/{map_data}/data`

#### Parameters
- `search` (optional): Search term to filter across all columns
- `per_page` (optional): Number of records per page (default: 15)

#### Response
```json
{
  "data": {
    "current_page": 1,
    "data": [...],
    "total": 100
  },
  "columns": ["latitude", "longitude", "name", "description"],
  "table_name": "mp_data_123e4567-e89b-12d3-a456-426614174000"
}
```

## Model Methods

### MapData Model
```php
// Check if MapData has a dynamic table
$mapData->hasDynamicTable(); // boolean

// Get all data from dynamic table
$mapData->getDynamicData(); // Collection

// Get row count from dynamic table
$mapData->getDynamicRowCount(); // int

// Delete dynamic table (called automatically on model delete)
$mapData->deleteDynamicTable(); // void
```

## Usage Examples

### Basic Upload Form
```php
<form action="{{ route('map-data.upload-rows') }}" method="POST" enctype="multipart/form-data">
    @csrf
    <input type="hidden" name="map_data_id" value="{{ $mapData->id }}">
    <input type="file" name="excel_file" accept=".xlsx,.xls" required>
    <button type="submit">Upload Excel File</button>
</form>
```

### Displaying Dynamic Data
```php
@if($mapData->hasDynamicTable())
    <p>Table: {{ $mapData->data_table }}</p>
    <p>Columns: {{ implode(', ', $mapData->data_columns) }}</p>
    <p>Row Count: {{ $mapData->getDynamicRowCount() }}</p>
@else
    <p>No data uploaded yet.</p>
@endif
```

### Fetching Data via API
```javascript
fetch(`/map-data/${mapDataId}/data?search=city&per_page=25`)
    .then(response => response.json())
    .then(data => {
        console.log('Table:', data.table_name);
        console.log('Columns:', data.columns);
        console.log('Data:', data.data);
    });
```

## Database Schema

### MapData Table Additions
```sql
ALTER TABLE map_data ADD COLUMN data_table VARCHAR(255) NULL;
ALTER TABLE map_data ADD COLUMN data_columns JSON NULL;
```

### Dynamic Table Structure
Each dynamic table follows this pattern:
```sql
CREATE TABLE mp_data_{sanitized_name}_{id_suffix} (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    map_data_id UUID NOT NULL,
    -- Dynamic columns based on Excel headers --
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    FOREIGN KEY (map_data_id) REFERENCES map_data(id) ON DELETE CASCADE
);
```

## Notes

- **Table Replacement**: Uploading a new Excel file replaces the existing table completely
- **Column Sanitization**: Excel headers are sanitized to valid database column names
- **Empty Row Handling**: Empty rows are automatically skipped during import
- **Spatial Integration**: Coordinates are automatically indexed and can create spatial points
- **Cleanup**: Dynamic tables are automatically deleted when MapData is deleted
- **Performance**: Tables are indexed on common columns (coordinates, foreign keys)

## Error Handling

The system provides detailed error reporting for:
- Invalid file formats
- Schema creation failures
- Data type conversion errors
- Row-level processing errors
- Spatial coordinate validation errors

All errors are logged and returned to the user with specific row numbers and descriptions.