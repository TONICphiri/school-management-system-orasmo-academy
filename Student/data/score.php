<?php

// Get student_score by ID
function getScoreById($student_id, $conn){
   $sql = "SELECT * FROM student_score
           WHERE student_id=? ORDER BY year DESC";
   $stmt = $conn->prepare($sql);
   $stmt->execute([$student_id]);

   if ($stmt->rowCount() > 0) {
     $student_scores = $stmt->fetchAll();
     return $student_scores;
   }else {
    return 0;
   }
}

function gradeCalc($grade){
   $g = "";
   if ($grade >= 85) {
       $g = "A";
   }else if ($grade >= 75) {
       $g = "B";
   }else if ($grade >= 60) {
       $g = "C";
   }else if ($grade >= 50) {
       $g = "D";
   }else if ($grade < 50) {
       $g = "F";
   }
   return $g;
}