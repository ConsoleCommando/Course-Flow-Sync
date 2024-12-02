<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Courses and Instructors</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="display_style.css">
</head>
<body>
    <h1>Search Courses by Instructor</h1>
    <form method="GET">
        <label for="instructor">Instructor Name:</label>
        <input type="text" id="instructor" name="instructor" placeholder="Enter instructor name" value="<?php echo htmlspecialchars($instructorFilter ?? '', ENT_QUOTES, 'UTF-8'); ?>">
        <input type="submit" value="Search">
        <button type="button" onclick="window.location.href='index.html'">Go to Home</button>
    </form>

    <h1>Courses and Instructors</h1>
    <div class="grid-container">

    <?php
        ini_set('display_errors', 1);
        ini_set('display_startup_errors', 1);
        error_reporting(E_ALL);

        require 'vendor/autoload.php';
        use MongoDB\Client;

        $manager = new MongoDB\Driver\Manager("mongodb://mongodb:27017/course_flow_sync");
        $instructorFilter = isset($_GET['instructor']) ? $_GET['instructor'] : '';

        try {
            // Aggregation pipeline with instructor filtering
            $pipeline = [
                ['$group' => [
                    '_id' => ['instructorName' => '$instructorName', 'instructorId' => '$instructorBannerId'],
                    'totalStudents' => ['$sum' => '$enrollment'],
                    'courses' => [
                        '$push' => [
                            'title' => '$courseTitle',
                            'enrollment' => '$enrollment',
                            'courseReferenceNumber' => '$courseReferenceNumber'
                        ]
                    ]
                ]],
                ['$project' => [
                    'instructorName' => '$_id.instructorName',
                    'totalStudents' => 1,
                    'courses' => 1,
                    '_id' => 0
                ]]
            ];

            if (!empty($instructorFilter)) {
                array_unshift($pipeline, ['$match' => ['instructorName' => new MongoDB\BSON\Regex($instructorFilter, 'i')]]);
            }

            $command = new MongoDB\Driver\Command([
                'aggregate' => 'courses',
                'pipeline' => $pipeline,
                'cursor' => new stdClass()
            ]);

            $cursor = $manager->executeCommand('course_flow_sync', $command);
            $results = [];

            foreach ($cursor as $result) {
                $results[] = $result;
            }

            usort($results, function($a, $b) {
                return strcmp($a->instructorName, $b->instructorName);
            });

            $resultsFound = false;

            foreach ($results as $result) {
                $resultsFound = true;
                $sanitizedInstructorName = preg_replace('/[^a-zA-Z0-9_]/', '_', $result->instructorName);

                echo "<div class='card'>";
                echo "<h2>" . htmlspecialchars($result->instructorName) . "</h2>";
                echo "<p>Total Students: " . htmlspecialchars($result->totalStudents) . "</p>";

                $courseTitles = [];
                $courseEnrollments = [];

                foreach ($result->courses as $course) {
                    $courseTitleWithReference = htmlspecialchars($course->title) . " (CRN: " . htmlspecialchars($course->courseReferenceNumber) . ")";
                    $courseTitles[] = $courseTitleWithReference;
                    $courseEnrollments[] = htmlspecialchars($course->enrollment);
                }

                echo "<div class='card-content'>";
                echo "<div class='chart-container'><canvas id='chart-" . htmlspecialchars($sanitizedInstructorName) . "'></canvas></div>";

                echo "<table>";
                echo "<tr><th>Course Title</th><th>Enrollment</th><th>Course Reference Number</th></tr>";
                foreach ($result->courses as $course) {
                    echo "<tr>";
                    echo "<td>" . htmlspecialchars($course->title) . "</td>";
                    echo "<td>" . htmlspecialchars($course->enrollment) . "</td>";
                    echo "<td>" . htmlspecialchars($course->courseReferenceNumber) . "</td>";
                    echo "</tr>";
                }
                echo "</table></div></div>";

                echo "<script>
                    document.addEventListener('DOMContentLoaded', function () {
                        var ctx = document.getElementById('chart-" . htmlspecialchars($sanitizedInstructorName) . "').getContext('2d');
                        new Chart(ctx, {
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
                                maintainAspectRatio: false,
                                plugins: {
                                    legend: {
                                        display: true,
                                        position: 'top',
                                        align: 'start', 
                                        labels: {
                                            boxWidth: 12, 
                                            padding: 10 
                                        }
                                    }
                                }
                            }
                        });
                    });
                </script>";
            }

            if (!$resultsFound) {
                echo "<p>No courses found or no instructor matches your query.</p>";
            }

        } catch (MongoDB\Driver\Exception\Exception $e) {
            echo "Error: " . $e->getMessage();
        }
    ?>
    </div>
</body>
</html>
