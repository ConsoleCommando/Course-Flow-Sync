# Course-Flow-Sync
CPSC 4910 Group 5 Project

Command to input JSON file into database:
mongoimport --db class_schedule --collection temp_courses --file /home/searchResults.json --jsonArray

Command to sort databse into proper collections:
// Clear existing collections if needed
db.courses.drop();
db.instructors.drop();

// Get the data from the temporary collection
const tempData = db.temp_courses.findOne().data; // Adjust this if necessary

// Loop through each course to insert into collections
tempData.forEach(course => {
  // Insert into courses collection
  course.faculty.forEach(faculty => {
    db.courses.insert({
      courseTitle: course.courseTitle,
      courseReferenceNumber: course.courseReferenceNumber,
      term: course.term,
      enrollment: course.enrollment,
      instructorName: faculty.displayName  // Use display name as a reference
    });

    // Insert into instructors collection (only if not already present)
    db.instructors.update(
      { displayName: faculty.displayName },
      {
        $setOnInsert: {
          emailAddress: faculty.emailAddress // Assuming you still want to keep the email
        }
      },
      { upsert: true } // Insert if it doesn't exist
    );
  });
});

// Drop the temporary collection after organizing
db.temp_courses.drop();
