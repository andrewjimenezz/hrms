<?php
$test_dir = __DIR__ . '/uploads/profiles/';

echo "Directory path: " . $test_dir . "<br>";
echo "Directory exists: " . (file_exists($test_dir) ? 'YES' : 'NO') . "<br>";
echo "Is writable: " . (is_writable($test_dir) ? 'YES' : 'NO') . "<br>";

// Try to create a test file
$test_file = $test_dir . 'test.txt';
if (file_put_contents($test_file, 'test')) {
    echo "Write test: SUCCESS<br>";
    unlink($test_file); // Delete test file
} else {
    echo "Write test: FAILED<br>";
}
?>