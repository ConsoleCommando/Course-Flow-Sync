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

    // Read and decode JSON file
    $jsonData = file_get_contents($file);
    $data = json_decode($jsonData, true);  // Decode into associative array

    // Check if JSON decoding failed or if 'data' key is missing
    if ($data === null || !is_array($data)) {
        die('Error: Invalid JSON structure or missing "data" key.');
    }

    // Now handle the case where data is inside an array
    foreach ($data as $item) {
        if (!isset($item['data'])) {
            die('Error: Missing "data" key in one of the array elements.');
        }

        $tempData = $item['data'];  // Extract the 'data' key containing courses

        // Connect to MongoDB
        $client = new Client("mongodb://mongodb:27017");  // Docker MongoDB connection string
        $db = $client->course_flow_sync;  // Your database name
        $coursesCollection = $db->courses;
        $instructorsCollection = $db->instructors;

        try {
            // Clear existing collections (optional)
            $coursesCollection->drop();
            $instructorsCollection->drop();

            // Loop through each course to insert into collections
            foreach ($tempData as $course) {
                // Check if the course has 'faculty' and process it
                if (isset($course['faculty']) && is_array($course['faculty'])) {
                    foreach ($course['faculty'] as $faculty) {
                        // Insert the course into the courses collection
                        $coursesCollection->insertOne([
                            'courseTitle' => $course['courseTitle'] ?? 'Unknown Title',
                            'courseReferenceNumber' => $course['courseReferenceNumber'] ?? 'Unknown CRN',
                            'term' => $course['term'] ?? 'Unknown Term',
                            'enrollment' => $course['enrollment'] ?? 0,
                            'instructorName' => $faculty['displayName'] ?? 'Unknown Instructor'  // Instructor display name
                        ]);

                        // Insert or update the instructor in the instructors collection
                        $instructorsCollection->updateOne(
                            ['displayName' => $faculty['displayName']],  // Match by instructor's display name
                            [
                                '$setOnInsert' => [
                                    'displayName' => $faculty['displayName'] ?? 'Unknown Instructor',
                                    'emailAddress' => $faculty['emailAddress'] ?? 'Unknown Email'
                                ]
                            ],
                            ['upsert' => true]  // Only insert if it doesn't exist
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
    }
} else {
    echo "No file uploaded.";
}
?>

