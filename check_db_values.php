<?php
require_once 'includes/db.php';
echo "--- DISTINCT VALUES IN QUESTIONS TABLE ---\n";

echo "\nAcademic Levels (Classes):\n";
$stmt = $pdo->query("SELECT DISTINCT academic_level FROM questions");
while($r = $stmt->fetch()) echo "- " . ($r[0] ?? "NULL") . "\n";

echo "\nCategories:\n";
$stmt = $pdo->query("SELECT DISTINCT category FROM questions");
while($r = $stmt->fetch()) echo "- " . ($r[0] ?? "NULL") . "\n";

echo "\nSubjects:\n";
$stmt = $pdo->query("SELECT DISTINCT subject FROM questions");
while($r = $stmt->fetch()) echo "- " . ($r[0] ?? "NULL") . "\n";

echo "\nLanguages:\n";
$stmt = $pdo->query("SELECT DISTINCT language FROM questions");
while($r = $stmt->fetch()) echo "- " . ($r[0] ?? "NULL") . "\n";

echo "\nRoles:\n";
$stmt = $pdo->query("SELECT DISTINCT role FROM questions");
while($r = $stmt->fetch()) echo "- " . ($r[0] ?? "NULL") . "\n";
?>
