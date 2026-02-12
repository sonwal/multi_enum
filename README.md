# Multi Enum Library

A PHP library for efficient handling of multiple enum scenarios using bitmap operations. This library enables conversion between character flags and integer values, perfect for storing multiple enum values in database columns.

## Overview

The Multi Enum library provides two main classes for handling enum flag conversions:

1. **`bitmap`** - Base class for converting between character flags and integer values
2. **`bitsync`** - Extended class for grouped flag operations with support for multiple flag categories

## Key Features

- **Bidirectional Conversion**: Convert character flags to integers and vice versa
- **Batch Processing**: Process multiple conversions simultaneously using array inputs
- **Grouped Flags**: Handle multiple flag categories with the `bitsync` class
- **Database Optimization**: Store multiple enum values in a single integer column
- **Validation**: Built-in input validation with comprehensive error messages
- **Memory Efficient**: Uses bitmap operations for minimal storage requirements

## Installation

Simply include the required class files in your PHP project:

```php
require_once('class.bitmap.php');
require_once('class.bitsync.php'); // Only if you need grouped flag operations
```

## Use Cases

### Database Storage
Store multiple enum values efficiently in a single integer column instead of multiple columns or junction tables:

```
Traditional approach:
- has_feature_a (BOOL)
- has_feature_b (BOOL)
- has_feature_c (BOOL)

With Multi Enum:
- features (INT) - stores all features as a bitmap
```

### API Responses
Convert stored integers to human-readable flag combinations for API responses.

### Configuration Management
Manage complex configuration flags efficiently in your application.

## Usage Examples

### bitmap Class

#### Basic String to Integer Conversion

```php
// Define valid flags (order matters!)
$validFlags = "ABCD";

// Input: string of flags to enable
$inputFlags = "AC";

// Create bitmap object
$bitmap = new bitmap($validFlags, $inputFlags, bitmap::STRING_LITERAL);
$result = $bitmap->convert();

// Result: 10 (binary: 1010)
// A=1, B=0, C=1, D=0
echo $result; // Output: 10
```

#### Integer to String Conversion

```php
// Define valid flags
$validFlags = "ABCD";

// Input: integer value
$inputInt = 10;

// Create bitmap object
$bitmap = new bitmap($validFlags, $inputInt, bitmap::INT_LITERAL);
$result = $bitmap->convert();

// Result: "AC"
echo $result; // Output: AC
```

#### Batch Conversion (Array Input)

```php
// String to Integer batch conversion
$validFlags = "ABCD";
$inputArray = array("AC", "BD", "ABCD", "A");

$bitmap = new bitmap($validFlags, $inputArray, bitmap::STRING_LITERAL);
$result = $bitmap->convert();

// Result format:
// array(
//     "success" => array(
//         0 => 10,  // AC
//         1 => 6,   // BD
//         2 => 15,  // ABCD
//         3 => 8    // A
//     )
// )
```

### bitsync Class

The `bitsync` class extends `bitmap` to handle grouped flags, allowing you to work with multiple categories of flags simultaneously.

#### Grouped Flags: String to Integer

```php
// Define flag groups
$configFlags = array(
    "Price" => "<>=",
    "TP" => "GSE",
    "APPLICABLE" => "DWAMI"
);

// Input flags by group
$inputFlagArr = array(
    "Price" => "<",
    "TP" => "G",
    "APPLICABLE" => "DW"
);

$bitSync = new bitsync($configFlags, $inputFlagArr, bitsync::STRING_LITERAL);
$response = $bitSync->convert();

// Result:
// array(
//     "success" => [integer value]
// )
```

#### Grouped Flags: Integer to String

```php
// Define flag groups
$configFlags = array(
    "Price" => "<>=",
    "TP" => "GSE",
    "APPLICABLE" => "DWAMI"
);

// Input: stored integer value
$inputInt = 1656;

$bitSync = new bitsync($configFlags, $inputInt, bitsync::INT_LITERAL);
$response = $bitSync->convert();

// Result:
// array(
//     "success" => array(
//         "Price" => "<",
//         "TP" => "G",
//         "APPLICABLE" => "DW"
//     )
// )
```

#### Update Existing Values

```php
// Define flag groups
$configFlags = array(
    "Price" => "<>=",
    "TP" => "GSE",
    "APPLICABLE" => "DWAMI"
);

// Existing value in database
$existingValue = 1656;

// New flags to update (only specific groups)
$inputFlagArr = array(
    "TP" => "SE"  // Update only TP group
);

$bitSync = new bitsync($configFlags, $inputFlagArr, bitsync::STRING_LITERAL, $existingValue);
$response = $bitSync->convert();

// Result: New integer with updated TP group, other groups preserved
```

## Database Integration

### MySQL Example

**Create Table:**
```sql
-- Create table with integer column for flags
CREATE TABLE products (
    id INT PRIMARY KEY,
    name VARCHAR(255),
    features INT DEFAULT 0
);
```

**Storing Flags:**
```php
$features = "ACE";
$bitmap = new bitmap("ABCDEFGH", $features, bitmap::STRING_LITERAL);
$intValue = $bitmap->convert();

// Insert into database
$query = "INSERT INTO products (name, features) VALUES ('Product1', $intValue)";
mysqli_query($conn, $query);
```

**Retrieving and Converting Back:**
```php
$query = "SELECT features FROM products WHERE id = 1";
$result = mysqli_query($conn, $query);
$row = mysqli_fetch_assoc($result);

$bitmap = new bitmap("ABCDEFGH", $row['features'], bitmap::INT_LITERAL);
$flags = $bitmap->convert();
echo $flags; // Output: ACE
```

### PostgreSQL Example

```sql
-- Similar approach works with PostgreSQL
CREATE TABLE configurations (
    id SERIAL PRIMARY KEY,
    name VARCHAR(255),
    flags INTEGER DEFAULT 0
);
```

## API Reference

### bitmap Class

#### Constructor
```php
__construct($flag_seq_str, $data, $type = self::STRING_LITERAL)
```

**Parameters:**
- `$flag_seq_str` (string): Valid flag sequence (e.g., "ABCD")
- `$data` (string|int|array): Input data to convert
- `$type` (const): `bitmap::STRING_LITERAL` or `bitmap::INT_LITERAL`

#### Methods

**`convert()`**
- Performs the conversion based on the type specified in constructor
- Returns: Integer (for STRING_LITERAL) or String (for INT_LITERAL)
- Returns: Array with errors if validation fails

#### Constants

- `STRING_LITERAL`: Use for string-to-integer conversion
- `INT_LITERAL`: Use for integer-to-string conversion
- `RESULT_ERROR`: Key for error results in array responses
- `RESULT_SUCCESS`: Key for successful results in array responses

### bitsync Class

#### Constructor
```php
__construct($flagSeqArr, $dataInput, $type = self::STRING_LITERAL, $existingValue = 0)
```

**Parameters:**
- `$flagSeqArr` (array): Associative array of flag groups
- `$dataInput` (array|int): Input data (array for STRING_LITERAL, int for INT_LITERAL)
- `$type` (const): Conversion type
- `$existingValue` (int): Existing value to preserve other groups (default: 0)

#### Methods

**`convert()`**
- Performs grouped flag conversion
- Returns: Array with 'success' or 'errors' keys

## Error Handling

Both classes provide comprehensive error messages:

### bitmap Errors
- Empty input
- Input string too long
- Duplicate characters in input
- Invalid flags not in valid sequence
- Invalid binary format
- Invalid integer format

### bitsync Errors
- Flags not in array format
- Invalid flag groups
- Mismatched flags in group
- Type validation errors

### Example Error Response

```php
array(
    "errors" => array(
        0 => "Error: Input value contains duplicates."
    )
)
```

## Performance Considerations

1. **Storage Efficiency**: A single integer can store up to 64 flags (using 64-bit integers)
2. **Query Performance**: Integer comparisons are faster than string searches
3. **Indexing**: Integer columns can be indexed more efficiently in databases
4. **Batch Processing**: Use array input for better performance when converting multiple values

## Best Practices

1. **Define Flag Order**: Always maintain consistent flag order in your application
2. **Document Flags**: Keep clear documentation of what each flag position represents
3. **Version Control**: If flag meanings change, version your flag definitions
4. **Validation**: Always check the return value for errors before using results
5. **Database Indexing**: Index integer flag columns for better query performance

## Limitations

1. Maximum number of flags depends on integer size (typically 32 or 64 bits)
2. Flag order must remain consistent across the application
3. Input arrays for `bitmap` must be one-dimensional
4. Requires PHP with standard integer and string functions

## Author

Manish Sonwal

## Version History

- **2015-06-18**: Initial release of `bitmap` class
- **2015-09-30**: Added `bitsync` class for grouped flag operations

## License

This library is available for use in your projects. Please retain author attribution.

## Support

For issues, questions, or contributions, please refer to the project repository.
