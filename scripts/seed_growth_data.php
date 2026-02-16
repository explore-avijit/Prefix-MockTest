<?php
require_once __DIR__ . '/../includes/db.php';

echo "Starting data seeding...\n";

try {
    $pdo->beginTransaction();

    // Clear existing mock users if needed, or just add more. 
    // To be safe, let's keep existing data and just add more registrations across 12 months.
    
    $roles = ['expert', 'aspirant'];
    $months_back = 12;

    for ($i = 0; $i <= $months_back; $i++) {
        // Calculate the month
        $date = new DateTime();
        $date->modify("-$i month");
        $month_str = $date->format('Y-m');
        
        // Experts for this month (random between 5 and 15)
        $experts_count = rand(5, 15);
        for ($j = 0; $j < $experts_count; $j++) {
            $email = "expert_" . $month_str . "_" . $j . "_" . uniqid() . "@example.com";
            $name = "Expert " . $month_str . " " . $j;
            // Generate a random day in that month
            $day = rand(1, 28);
            $timestamp = $date->format('Y-m') . "-" . str_pad($day, 2, '0', STR_PAD_LEFT) . " " . rand(10, 20) . ":" . rand(10, 59) . ":" . rand(10, 59);
            
            $stmt = $pdo->prepare("INSERT INTO users (full_name, email, password, role, status, created_at) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$name, $email, password_hash('password123', PASSWORD_DEFAULT), 'expert', 'approved', $timestamp]);
        }
        
        // Aspirants for this month (random between 20 and 50)
        $aspirants_count = rand(20, 50);
        for ($j = 0; $j < $aspirants_count; $j++) {
            $email = "aspirant_" . $month_str . "_" . $j . "_" . uniqid() . "@example.com";
            $name = "Aspirant " . $month_str . " " . $j;
            $day = rand(1, 28);
            $timestamp = $date->format('Y-m') . "-" . str_pad($day, 2, '0', STR_PAD_LEFT) . " " . rand(10, 20) . ":" . rand(10, 59) . ":" . rand(10, 59);

            $stmt = $pdo->prepare("INSERT INTO users (full_name, email, password, role, status, created_at) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$name, $email, password_hash('password123', PASSWORD_DEFAULT), 'aspirant', 'approved', $timestamp]);
        }
        
        echo "Inserted data for $month_str: $experts_count experts, $aspirants_count aspirants\n";
    }

    $pdo->commit();
    echo "Seeding completed successfully!\n";

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo "Error: " . $e->getMessage() . "\n";
}
?>
