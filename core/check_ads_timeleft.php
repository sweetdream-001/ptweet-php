<?php
// Include necessary files
require_once("settings.php");
require_once("definitions.php");
require_once("components/tools.php");
require_once("components/shortcuts.php");
require_once("components/compilers.php");
require_once("components/localization.php");
require_once("components/glob_context.php");
require_once("components/user.php");
require_once("components/post.php");
require_once("components/ad.php");
require_once("configs/conf.php");
require_once("libs/DB/vendor/autoload.php");

$cl["db_errors"] = array();
$sql_db_host     = (isset($sql_db_host) ? $sql_db_host : "");
$sql_db_user     = (isset($sql_db_user) ? $sql_db_user : "");
$sql_db_pass     = (isset($sql_db_pass) ? $sql_db_pass : "");
$sql_db_name     = (isset($sql_db_name) ? $sql_db_name : "");
$site_url        = (isset($site_url)    ? $site_url    : "");
$mysqli          = new mysqli($sql_db_host, $sql_db_user, $sql_db_pass, $sql_db_name);

if (mysqli_connect_errno()) {
    array_push($cl["db_errors"], mysqli_connect_error());

    if (not_empty($cl["db_errors"])) {
        echo cl_html_template("db_errors");
        die();
    }
}

$me            = array();
$db_connection = $mysqli;
$query         = $mysqli->query("SET NAMES utf8");
$set_charset   = $mysqli->set_charset('utf8mb4');
$set_charset   = $mysqli->query("SET collation_connection = utf8mb4_unicode_ci");
$db            = new MysqliDb($mysqli);
$url           = $site_url;

$config        = cl_get_configurations();

        // Get today's date (current time)
        $current_time = time();
        
        // Query to fetch all active ads
        $ads = $db->where('status', 'active') // Only active ads
                  ->get(T_ADS); // T_ADS is the ads table



        if ($ads) {
            foreach ($ads as $ad) {
                
                $new_timeleft = $ad['timeleft'] - 1; // 1 day in seconds
                // If timeleft is zero or negative, deactivate the ad
                    if ($new_timeleft < 0) {
                        // Deactivate ad if expired
                        $db->where('id', $ad['id'])->update(T_ADS, array('approved' => 'N', 'timeleft' => 0));
                        echo "Ad ID {$ad['id']} expired.\n";
                        return;
                    } else {
                        // Update the timeleft in the database
                        $db->where('id', $ad['id'])->update(T_ADS, array('timeleft' => $new_timeleft));
                        // Check if timeleft is exactly 7 days and send email to the owner
                        if ($new_timeleft <= 6 && $new_timeleft >= 0) { // 7 days in seconds
                        // Prepare email data
                        $ad_owner = $db->where('id', $ad['user_id'])->getOne(T_USERS);
                        
                        $cl['ad_message_data']['ad_name'] = $ad['company'];
                        $cl['ad_message_data']['timeleft'] = $new_timeleft;
                        $cl['ad_message_data']['ad_url'] = cl_link('ad_thread/'.$ad['id']);
                        $cl['ad_message_data']['ad_owner'] = $ad_owner['fname'];
                        $cl['ad_message_data']['site_name'] = $config['name'];
                        
                        $data = array(
                            'from_email'     => $config['smtp_username'],
                            'from_name'      => $config['name'],
                            'to_email'       => $ad_owner['email'],
                            'to_name'        => $ad_owner['fname'],
                            'subject'        => "P-tweets Don't Miss Out – Renew Your AD for Continued Exposure!",
                            'charSet'        => 'UTF-8',
                            'is_html'        => true,
                            'message_body'   => cl_template('emails/ad_expiry_notice', array(
                                'ad_name'    => $ad['company'],
                                'timeleft'   => cl_number($ad['timeleft']),
                                'ad_url'     => cl_link('ad_thread/'.$ad['id']),
                                'site_name'  => $config['name']
                            ))
                        );
                        
                        //------Send email function------start---------
                        require_once(cl_full_path('core/libs/configs/mailer.php'));
                        try {
                            $email_from      = $data['from_email'];
                            $to_email        = $data['to_email'];
                            $subject         = $data['subject'];
                            $data['charSet'] = $data['charSet'];
                    
                            if ($config['smtp_or_mail'] == 'mail') {
                                $mail->IsMail();
                            } else if ($config['smtp_or_mail'] == 'smtp') {
                                
                                $mail->isSMTP();
                                $mail->Timeout     = 30;
                                $mail->SMTPDebug   = false;  // Change to 3 or 4 for even more details
                                $mail->Host        = $config['smtp_host'];
                                $mail->SMTPAuth    = true;
                                $mail->Username    = $config['smtp_username'];
                                $mail->Password    = $config['smtp_password'];
                                $mail->SMTPSecure  = $config['smtp_encryption'];
                                $mail->Port        = $config['smtp_port'];
                                $mail->SMTPOptions = array(
                                    'ssl'          => array(
                                        'verify_peer'       => false,
                                        'verify_peer_name'  => false,
                                        'allow_self_signed' => true
                                    )
                                );  
                                
                            } else {
                                return false;
                            }
                            
                            $mail->IsHTML($data['is_html']);
                            $mail->setFrom($email_from, $data['from_name']);
                            $mail->addAddress($to_email, $data['to_name']);
                            $mail->Subject = $data['subject'];
                            $mail->CharSet = $data['charSet'];
                            $mail->MsgHTML($data['message_body']);
                            
                            if ($mail->send()) {
                                $mail->ClearAddresses();
                                
                                echo 'Message has been sent successfully';
                            }
                            
                        } catch (Exception $e) {
                            echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
                        }
                        
                        
                        //
                        }
                    }

            }
        } else {
            echo "No active ads found.\n";
        }
?>