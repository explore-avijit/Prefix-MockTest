<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/config.php';

function sendOTPMail($toEmail, $otp) {
    $mail = new PHPMailer(true);

    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_USER;
        $mail->Password   = SMTP_PASS;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = SMTP_PORT;

        // Recipients
        $mail->setFrom(SMTP_FROM, SMTP_FROM_NAME);
        $mail->addAddress($toEmail);

        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Your Prefix OTP Code';
        $mail->Body    = "
            <table width='100%' border='0' cellspacing='0' cellpadding='0' style='background-color: #f8fafc; font-family: Arial, sans-serif;'>
                <tr>
                    <td align='center' style='padding: 40px 10px;'>
                        <table width='600' border='0' cellspacing='0' cellpadding='0' style='background-color: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);'>
                            <!-- Header -->
                            <tr>
                                <td style='background-color: #6366f1; padding: 30px; text-align: center;'>
                                    <h1 style='color: #ffffff; margin: 0; font-size: 28px; font-weight: 700; letter-spacing: -0.025em;'>Prefix</h1>
                                    <p style='color: rgba(255, 255, 255, 0.9); margin: 5px 0 0 0; font-size: 14px;'>Smart Practice for Competitive Exams</p>
                                </td>
                            </tr>
                            <!-- Body -->
                            <tr>
                                <td style='padding: 40px 30px;'>
                                    <h2 style='color: #1e293b; margin: 0 0 20px 0; font-size: 20px; font-weight: 600;'>Verification Code</h2>
                                    <p style='color: #475569; line-height: 1.6; margin: 0 0 30px 0;'>Hello,</p>
                                    <p style='color: #475569; line-height: 1.6; margin: 0 0 30px 0;'>To complete your login to your Prefix account, please use the following One-Time Password (OTP). This code will expire in <strong style='color: #1e293b;'>10 minutes</strong>.</p>
                                    
                                    <!-- OTP Box -->
                                    <div style='background-color: #f1f5f9; border: 1px dashed #cbd5e1; border-radius: 8px; padding: 25px; text-align: center; margin-bottom: 30px;'>
                                        <span style='font-size: 36px; font-family: \"Courier New\", Courier, monospace; font-weight: 700; letter-spacing: 8px; color: #6366f1;'>$otp</span>
                                    </div>
                                    
                                    <p style='color: #475569; line-height: 1.6; margin: 0 0 20px 0;'>If you did not request this verification, please contact our support team or ignore this email. Your account security is our priority.</p>
                                    
                                    <div style='border-top: 1px solid #e2e8f0; margin-top: 30px; padding-top: 20px;'>
                                        <p style='color: #94a3b8; font-size: 12px; line-height: 1.5; margin: 0;'>
                                            <strong style='color: #64748b;'>Security Note:</strong> Prefix will never ask for your password or OTP over phone or chat. Do not share this code with anyone.
                                        </p>
                                    </div>
                                </td>
                            </tr>
                            <!-- Footer -->
                            <tr>
                                <td style='padding: 30px; text-align: center; background-color: #f8fafc; border-top: 1px solid #e2e8f0;'>
                                    <p style='color: #64748b; font-size: 13px; margin: 0 0 10px 0;'>&copy; 2026 Prefix EdTech System. All rights reserved.</p>
                                    <div style='color: #94a3b8; font-size: 12px;'>
                                        Sent via Prefix Automated Verification System
                                    </div>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
        ";

        $mail->send();
        return true;
    } catch (Exception $e) {
        return false;
    }
}

function sendWelcomeMail($toEmail, $userName, $role, $uniqueId) {
    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_USER;
        $mail->Password   = SMTP_PASS;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = SMTP_PORT;

        $mail->setFrom(SMTP_FROM, SMTP_FROM_NAME);
        $mail->addAddress($toEmail, $userName);

        $mail->isHTML(true);
        $mail->Subject = "Welcome to Prefix, $userName!";

        // Role-based content personalization
        $roleTitle = ucfirst($role);
        $welcomeMessage = "";
        $benefitsList = "";

        if ($role === 'expert') {
            $welcomeMessage = "We are honored to have you join our distinguished panel of Experts. At Prefix, we bridge the gap between knowledge and achievement, and your expertise is the most vital component of that mission.";
            $benefitsList = "
                <li>Create and manage high-quality mock tests.</li>
                <li>Monitor student progress with detailed analytics.</li>
                <li>Build your professional reputation within our academic community.</li>";
        } elseif ($role === 'student') {
            $welcomeMessage = "Exciting times ahead! You've just taken a major step towards academic excellence. Prefix is designed to help you master your subjects with smart practice and real-time feedback.";
            $benefitsList = "
                <li>Access school-specific mock tests and quizzes.</li>
                <li>Track your performance across different subjects.</li>
                <li>Learn from detailed solutions provided by experts.</li>";
        } else { // Aspirant
            $welcomeMessage = "Welcome to the elite circle of competitive exam aspirants! Your journey towards your dream career just got a powerful upgrade. We are here to ensure your preparation is smart, data-driven, and focused.";
            $benefitsList = "
                <li>Participate in live competitive mocks with thousands of peers.</li>
                <li>Get AI-driven insights into your strengths and weaknesses.</li>
                <li>Access specialized content for WBCS, Police, and Board exams.</li>";
        }

        $mail->Body = "
            <table width='100%' border='0' cellspacing='0' cellpadding='0' style='background-color: #f8fafc; font-family: -apple-system, BlinkMacSystemFont, \"Segoe UI\", Roboto, Helvetica, Arial, sans-serif;'>
                <tr>
                    <td align='center' style='padding: 40px 10px;'>
                        <table width='600' border='0' cellspacing='0' cellpadding='0' style='background-color: #ffffff; border: 1px solid #e2e8f0; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);'>
                            <!-- Header Decor -->
                            <tr><td height='6' style='background-color: #6366f1;'></td></tr>
                            <!-- Header -->
                            <tr>
                                <td style='padding: 40px 40px 20px 40px;'>
                                    <h1 style='color: #1e293b; margin: 0; font-size: 24px; font-weight: 800; letter-spacing: -0.025em; font-family: \"Outfit\", sans-serif;'>Welcome to Prefix!</h1>
                                </td>
                            </tr>
                            <!-- Body -->
                            <tr>
                                <td style='padding: 0 40px 40px 40px;'>
                                    <p style='color: #475569; font-size: 16px; line-height: 1.6; margin: 0 0 20px 0;'>Dear <strong>$userName</strong>,</p>
                                    <p style='color: #475569; font-size: 16px; line-height: 1.6; margin: 0 0 25px 0;'>$welcomeMessage</p>
                                    
                                    <!-- ID Card Style -->
                                    <div style='background-color: #6366f1; border-radius: 12px; padding: 25px; margin-bottom: 30px; color: #ffffff;'>
                                        <p style='margin: 0; font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.1em; opacity: 0.8;'>Your Portal Credentials</p>
                                        <div style='margin-top: 15px;'>
                                            <p style='margin: 0; font-size: 20px; font-weight: 800;'>ID: $uniqueId</p>
                                            <p style='margin: 5px 0 0 0; font-size: 14px; opacity: 0.9;'>Role: $roleTitle</p>
                                        </div>
                                    </div>
                                    
                                    <h3 style='color: #1e293b; font-size: 16px; font-weight: 700; margin: 0 0 15px 0;'>What's next?</h3>
                                    <ul style='color: #475569; font-size: 14px; line-height: 1.8; margin: 0 0 30px 0; padding-left: 20px;'>
                                        $benefitsList
                                    </ul>
                                    
                                    <a href='http://" . $_SERVER['HTTP_HOST'] . "/Prefix-MockTest/index.html' style='display: inline-block; background-color: #1e293b; color: #ffffff; padding: 14px 30px; border-radius: 10px; text-decoration: none; font-weight: 700; font-size: 14px;'>Launch Dashboard</a>
                                </td>
                            </tr>
                            <!-- Footer -->
                            <tr>
                                <td style='padding: 30px 40px; background-color: #f8fafc; border-top: 1px solid #e2e8f0; text-align: center;'>
                                    <p style='color: #94a3b8; font-size: 12px; margin: 0;'>&copy; 2026 Prefix EdTech. This is an automated greeting. Please do not reply to this email.</p>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
        ";

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Welcome Mail Error: " . $e->getMessage());
        return false;
    }
}

function sendAccountStatusMail($toEmail, $userName, $status, $uniqueId, $remarks = '') {
    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_USER;
        $mail->Password   = SMTP_PASS;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = SMTP_PORT;

        $mail->setFrom(SMTP_FROM, SMTP_FROM_NAME);
        $mail->addAddress($toEmail, $userName);

        $mail->isHTML(true);
        
        $subject = "";
        $title = "";
        $message = "";
        $accentColor = "#6366f1"; // Default indigo

        switch (strtolower($status)) {
            case 'approved':
            case 'active':
                $subject = "Success! Your Prefix Account is Approved";
                $title = "Account Approved";
                $message = "Congratulations! Your application for a Prefix account has been reviewed and successfully approved. You can now access your full dashboard and all premium features.";
                $accentColor = "#10b981"; // Green
                break;
            case 'suspended':
                $subject = "Notice regarding your Prefix Account";
                $title = "Account Suspended";
                $message = "Your Prefix account has been temporarily suspended by the administration. This could be due to a routine security audit or a violation of our community guidelines.";
                $accentColor = "#f59e0b"; // Amber
                break;
            case 'declined':
            case 'rejected':
            case 'blocked':
                $subject = "Update on your Prefix Registration";
                $title = "Registration Declined";
                $message = "We regret to inform you that your registration application for Prefix has been declined at this time after our internal review.";
                $accentColor = "#ef4444"; // Red
                break;
        }

        $mail->Subject = $subject;

        $remarkSection = $remarks ? "
            <div style='background-color: #f1f5f9; border-left: 4px solid $accentColor; padding: 15px; margin: 20px 0;'>
                <p style='margin: 0; font-size: 13px; font-weight: 700; color: #475569;'>ADMIN REMARKS:</p>
                <p style='margin: 5px 0 0 0; font-size: 14px; color: #1e293b;'>$remarks</p>
            </div>" : "";

        $mail->Body = "
            <table width='100%' border='0' cellspacing='0' cellpadding='0' style='background-color: #f8fafc; font-family: sans-serif;'>
                <tr>
                    <td align='center' style='padding: 40px 10px;'>
                        <table width='600' border='0' cellspacing='0' cellpadding='0' style='background-color: #ffffff; border: 1px solid #e2e8f0; border-radius: 16px; overflow: hidden;'>
                            <tr><td height='6' style='background-color: $accentColor;'></td></tr>
                            <tr>
                                <td style='padding: 40px;'>
                                    <h1 style='color: #1e293b; margin: 0; font-size: 24px; font-weight: 800;'>$title</h1>
                                    <p style='color: #475569; font-size: 16px; line-height: 1.6; margin: 25px 0;'>Dear <strong>$userName</strong>,</p>
                                    <p style='color: #475569; font-size: 16px; line-height: 1.6; margin: 0;'>$message</p>
                                    
                                    $remarkSection

                                    <div style='margin-top: 35px; padding-top: 25px; border-top: 1px solid #f1f5f9;'>
                                        <p style='margin: 0; font-size: 14px; color: #64748b;'>Account ID: <strong style='color: #1e293b;'>$uniqueId</strong></p>
                                    </div>

                                    <div style='margin-top: 30px;'>
                                        <a href='http://" . $_SERVER['HTTP_HOST'] . "/Prefix-MockTest/index.html' style='display: inline-block; background-color: #6366f1; color: #ffffff; padding: 14px 30px; border-radius: 10px; text-decoration: none; font-weight: 700; font-size: 14px;'>Visit Prefix Portal</a>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td style='padding: 30px; background-color: #f8fafc; border-top: 1px solid #e2e8f0; text-align: center;'>
                                    <p style='color: #94a3b8; font-size: 12px; margin: 0;'>&copy; 2026 Prefix EdTech. This is an automated notification.</p>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
        ";

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Account Status Mail Error: " . $e->getMessage());
        return false;
    }
}

function generateUniqueID($pdo, $table, $prefix) {
    // We now check the 'users' table for global uniqueness of unique_id
    $exists = true;
    $unique_id = "";
    
    while ($exists) {
        $digits = str_pad(mt_rand(0, 9999), 4, '0', STR_PAD_LEFT);
        $unique_id = strtoupper($prefix) . $digits;
        
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE unique_id = ?");
        $stmt->execute([$unique_id]);
        if ($stmt->fetchColumn() == 0) {
            $exists = false;
        }
    }
    
    return $unique_id;
}
?>
