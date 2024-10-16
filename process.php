<?php
    // Enable error reporting for debugging
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);

    // Autoload MongoDB library via Composer
    require 'vendor/autoload.php';

    // Connect to MongoDB
    $manager = new MongoDB\Driver\Manager("mongodb://mongodb:27017/course-flow-sync"); // Use the correct database name

    // Check if an instructor name is provided from the search
    $instructorFilter = isset($_GET['instructor']) ? $_GET['instructor'] : '';

    try {
        // Aggregation query with optional filtering by instructor name
        $pipeline = [
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
        ];

        // If an instructor name is provided, add a match stage to the aggregation
        if (!empty($instructorFilter)) {
            array_unshift($pipeline, [
                '$match' => [
                    'instructorName' => new MongoDB\BSON\Regex($instructorFilter, 'i')
                ]
            ]);
        }

        $command = new MongoDB\Driver\Command([
            'aggregate' => 'courses',
            'pipeline' => $pipeline,
            'cursor' => new stdClass()
        ]);

        $cursor = $manager->executeCommand('course-flow-sync', $command); // Use the correct database name here

        echo "<h1>Courses and Instructors</h1>";

        foreach ($cursor as $result) {
            echo "<h2>Instructor: " . htmlspecialchars($result->instructorName) . "</h2>";
            echo "<p>Total Students: " . htmlspecialchars($result->totalStudents) . "</p>";

            // Prepare data for visualization (course names and enrollments)
            $courseTitles = [];
            $courseEnrollments = [];

            foreach ($result->courses as $course) {
                // Combine course title with course reference number
                $courseTitleWithReference = htmlspecialchars($course->title) . " (CRN: " . htmlspecialchars($course->courseReferenceNumber) . ")";
                $courseTitles[] = $courseTitleWithReference;
                $courseEnrollments[] = htmlspecialchars($course->enrollment);
            }

            // Add chart container with fixed size
            echo "<div class='chart-container'><canvas id='chart-". htmlspecialchars($result->instructorName) ."'></canvas></div>";

            echo "<script>
            var ctx = document.getElementById('chart-". htmlspecialchars($result->instructorName) ."').getContext('2d');
            var chart = new Chart(ctx, {
                type: 'pie',
                data: {
                    labels: " . json_encode($courseTitles) . ",
                    datasets: [{
                        label: 'Enrollment',
                        data: " . json_encode($courseEnrollments) . ",
                        backgroundColor: [
                            'rgba(255, 99, 132, 0.2)',
                            'rgba(54, 162, 235, 0.2)',
                            'rgba(255, 206, 86, 0.2)',
                            'rgba(75, 192, 192, 0.2)',
                            'rgba(153, 102, 255, 0.2)',
                            'rgba(255, 159, 64, 0.2)'
                        ],
                        borderColor: [
                            'rgba(255, 99, 132, 1)',
                            'rgba(54, 162, 235, 1)',
                            'rgba(255, 206, 86, 1)',
                            'rgba(75, 192, 192, 1)',
                            'rgba(153, 102, 255, 1)',
                            'rgba(255, 159, 64, 1)'
                        ],
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false
                }
            });
            </script>";

            // Display raw data in a table
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
    

