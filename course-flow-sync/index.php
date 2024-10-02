<?php
// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Autoload MongoDB library via Composer
require 'vendor/autoload.php';

// Connect to MongoDB
$manager = new MongoDB\Driver\Manager("mongodb://mongodb:27017/course-flow-sync"); // Use the correct database name

try {
    // Aggregation query to get total students, courses, and instructor info
    $command = new MongoDB\Driver\Command([
        'aggregate' => 'courses',
        'pipeline' => [
            [
                '$group' => [
                    '_id' => [
                        'instructorName' => '$instructorName',
                        'instructorId' => '$instructorBannerId'
                    ],
                    'totalStudents' => ['$sum' => '$enrollment'],
                    'courses' => [
                        '$push' => [
                            'title' => '$courseTitle',
                            'enrollment' => '$enrollment',
                            'courseReferenceNumber' => '$courseReferenceNumber'
                        ]
                    ]
                ]
            ],
            [
                '$project' => [
                    'instructorName' => '$_id.instructorName',
                    'totalStudents' => 1,
                    'courses' => 1,
                    '_id' => 0
                ]
            ]
        ],
        'cursor' => new stdClass()
    ]);

    $cursor = $manager->executeCommand('course-flow-sync', $command); // Use the correct database name here

    echo "<h1>Courses and Instructors</h1>";

    foreach ($cursor as $result) {
        echo "<h2>Instructor: " . htmlspecialchars($result->instructorName) . "</h2>";
        echo "<p>Total Students: " . htmlspecialchars($result->totalStudents) . "</p>";
        echo "<table border='1'>";
        echo "<tr><th>Course Title</th><th>Enrollment</th><th>Course Reference Number</th></tr>";

        foreach ($result->courses as $course) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($course->title) . "</td>";
            echo "<td>" . htmlspecialchars($course->enrollment) . "</td>";
            echo "<td>" . htmlspecialchars($course->courseReferenceNumber) . "</td>";
            echo "</tr>";
        }
        echo "</table><br>";
    }

} catch (MongoDB\Driver\Exception\Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>

