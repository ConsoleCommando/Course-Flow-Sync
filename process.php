<?php
// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Autoload MongoDB library via Composer
require 'vendor/autoload.php';

use MongoDB\Client;

// Check if the file was uploaded
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['jsonFile'])) {
    $file = $_FILES['jsonFile']['tmp_name'];

    // Read and wrap JSON data with square brackets
    $jsonData = '[' . file_get_contents($file) . ']';

    // Decode JSON into associative array
    $data = json_decode($jsonData, true);

    // Check if JSON decoding failed or if 'data' key is missing
    if ($data === null || !isset($data[0]['data'])) {
        die('Error: Invalid JSON structure or missing "data" key.');
    }

    // Extract courses data
    $coursesData = $data[0]['data'];

    // Connect to MongoDB
    $client = new Client("mongodb://mongodb:27017");
    $db = $client->course_flow_sync;
    $coursesCollection = $db->courses;
    $instructorsCollection = $db->instructors;

    try {
        // Clear existing collections (optional)
        $coursesCollection->drop();
        $instructorsCollection->drop();

        // Loop through each course to insert into collections
        foreach ($coursesData as $course) {
            if (isset($course['faculty']) && is_array($course['faculty'])) {
                foreach ($course['faculty'] as $faculty) {
                    // Insert into courses collection
                    $coursesCollection->insertOne([
                        'courseTitle' => $course['courseTitle'] ?? 'Unknown Title',
                        'courseReferenceNumber' => $course['courseReferenceNumber'] ?? 'Unknown CRN',
                        'term' => $course['term'] ?? 'Unknown Term',
                        'enrollment' => $course['enrollment'] ?? 0,
                        'instructorName' => $faculty['displayName'] ?? 'Unknown Instructor'
                    ]);

                    // Insert or update instructor in instructors collection
                    $instructorsCollection->updateOne(
                        ['displayName' => $faculty['displayName']],
                        [
                            '$setOnInsert' => [
                                'displayName' => $faculty['displayName'] ?? 'Unknown Instructor',
                                'emailAddress' => $faculty['emailAddress'] ?? 'Unknown Email'
                            ]
                        ],
                        ['upsert' => true]
                    );
                }
            }
        }

        // Redirect to display.php after successful insertion
        header('Location: display.php');
        exit;

    } catch (Exception $e) {
        die("Error inserting data: " . $e->getMessage());
    }
} else {
    echo "No file uploaded.";
}
?>
