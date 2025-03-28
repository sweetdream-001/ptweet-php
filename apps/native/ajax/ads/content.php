<?php 
# @*************************************************************************@
# @ Software author: JOOJ Team (JOOJ.us)                           @
# @ Author_url 1: https://jooj.us                       @
# @ Author_url 2: http://jooj.us/twitter-clone                      @
# @ Author E-mail: sales@jooj.us                                   @
# @*************************************************************************@
# @ JOOJ Talk - The Ultimate Modern Social Media Sharing Platform           @
# @ Copyright (c) 2020 - 2021 JOOJ Talk. All rights reserved.               @
# @*************************************************************************@

if ($action == 'upload_ad_cover' && not_empty($cl['is_logged'])) {
	$data['err_code'] = 0;
    $data['status']   = 400;
    $ad_id            = fetch_or_get($_POST['ad_id'], false);
    $ad_data          = cl_raw_ad_data($ad_id);

    if (not_empty($ad_data) && $ad_data['user_id'] == $me['id']) {
		if (not_empty($_FILES['cover']) && not_empty($_FILES['cover']['tmp_name'])) {
	        $file_info      = array(
	            'file'      => $_FILES['cover']['tmp_name'],
	            'size'      => $_FILES['cover']['size'],
	            'name'      => $_FILES['cover']['name'],
	            'type'      => $_FILES['cover']['type'],
	            'file_type' => 'image',
	            'folder'    => 'covers',
	            'slug'      => 'cover',
	            'allowed'   => 'jpg,png,jpeg,gif'
	        );

	        $file_upload = cl_upload($file_info);

	        if (not_empty($file_upload['filename'])) {
	            $data['status'] = 200;
	            $data['url']    = cl_get_media($file_upload['filename']);

	            cl_delete_media($ad_data['cover']);

	            cl_update_ad_data($ad_id, array(
	                'cover' => $file_upload['filename']
	            ));

                if ($ad_data['status'] == 'active') {
                    cl_update_ad_data($ad_id, array(
                        'status' => 'inactive'
                    ));
                }
	        } 
	    }
    }
}

else if($action == 'save_ad_data' && not_empty($cl['is_logged'])) {
    $data['err_code'] = 0;
    $data['status']   = 400;
    $ad_id            = fetch_or_get($_POST['ad_id'], false);
    $ad_data          = cl_raw_ad_data($ad_id);
    
    if (not_empty($ad_data) && $ad_data['user_id'] == $me['id']) {
        $ad_data_changes  = array(
            'cover'       => fetch_or_get($ad_data['cover'], false),
    		'company'     => fetch_or_get($_POST['company'], false),
    		'target_url'  => fetch_or_get($_POST['target_url'], false),
    		'yt_url'      => fetch_or_get($_POST['yt'], null), // Nullable YouTube URL
    		'status'      => fetch_or_get($_POST['status'], false),
    		'audience'    => fetch_or_get($_POST['audience'], false),
    		'description' => fetch_or_get($_POST['description'], false),
    		'cta'         => fetch_or_get($_POST['cta'], false)
    	);
        
        // Retrieve the subscription type from the form
        $subscription_type = fetch_or_get($_POST['subscription_type'], '');
        // Verify that a subscription plan was chosen
        if (empty($subscription_type) || !in_array($subscription_type, array("monthly", "yearly"))) {
            $data['err_code'] = 'subscription_required';
            echo json_encode($data);
            exit;
        }

        // Determine the required fee and initial timeleft based on subscription type
        if ($subscription_type == "monthly") {
            $requiredFee = $cl["config"]["advertisement_mprice"];
            $initialTimeleft = 30; // days
        } else { // yearly
            $requiredFee = $cl["config"]["advertisement_yprice"];
            $initialTimeleft = 365; // days
        }
        // Check if the user has enough wallet balance
        if ($me["wallet"] < $requiredFee) {
            $data['err_code'] = 'insufficient_funds';
            echo json_encode($data);
            exit;
        }
        // Deduct the fee from the user's wallet
        cl_update_user_data($me["id"], array(
            "wallet" => ($me["wallet"] - $requiredFee)
        ));
        
        // At least one of cover or yt_url must be provided
    	if (!empty($ad_data_changes['cover']) || !empty($ad_data_changes['yt_url'])) {
            foreach ($ad_data_changes as $field_name => $field_val) {
                if ($field_name == 'company') {
                    if (empty($field_val) || len_between($field_val, 1, 115) != true) {
                        $data['err_code'] = "invalid_company"; break;
                    }
                }
    
                else if ($field_name == 'target_url') {
                    if (empty($field_val) || is_url($field_val) != true) {
                        $data['err_code'] = "invalid_target_url"; break;
                    }
                }

                else if ($field_name == 'status') {
                    if (empty($field_val) || in_array($field_val, array('active', 'inactive')) != true) {
                        $data['err_code'] = "invalid_status"; break;
                    }
                }
    
                else if ($field_name == 'audience') {
                    if (empty($field_val) || are_all($field_val, 'numeric') != true) {
                        $data['err_code'] = "invalid_audience"; break;
                    }
                }
    
                else if ($field_name == 'description') {
                    if (empty($field_val) || len_between($field_val, 1, 550) != true) {
                        $data['err_code'] = "invalid_description"; break;
                    }
                }
    
                else if ($field_name == 'cta') {
                    if (empty($field_val) || len_between($field_val, 1, 32) != true) {
                        $data['err_code'] = "invalid_cta"; break;
                    }
                }
            }
        } else {
            $data['err_code'] = "Please upload a cover or add a YouTube video URL.";
        }
        
    	if (empty($data['err_code'])) {
            $data['status']   = 200;
    		$ad_update_data   = array(
                'company'     => cl_text_secure($ad_data_changes['company']),
    			'target_url'  => cl_text_secure($ad_data_changes['target_url']),
    			'og_data'     => (not_empty($ad_data_changes['yt_url'])) ? json_encode(youTubeToText($ad_data_changes['yt_url'])) : null,
    			'link_src'    => (not_empty($ad_data_changes['yt_url'])) ? getYoutubeVideoIdS($ad_data_changes['yt_url']) : null,
    			'status'      => cl_text_secure($ad_data_changes['status']),
    			'audience'    => ((empty($ad_data_changes['audience'])) ? json(array(), true) : json($ad_data_changes['audience'], true)),
    			'description' => cl_text_secure($ad_data_changes['description']),
    			'cta'         => cl_text_secure($ad_data_changes['cta']),

    			'timeleft'    => $initialTimeleft,
    		);

            if (cl_update_ad_data($ad_id, $ad_update_data)) {
                // Log the wallet transaction with reference to this ad
                cl_db_insert(T_WALLET_HISTORY, array(
                    'user_id'   => $me['id'],
                    'operation' => 'ads_subscription_purchase',
                    'amount'    => $requiredFee,
                    'json_data' => json(array("ad_id" => $ad_id), true),
                    'time'      => time()
                ));
                
                $data['status'] = 200;
            } else {
                    $data['err_code'] = 'db_error';
                }

    		if (not_empty($me['last_ad'])) {
    			cl_update_user_data($me['id'], array(
    				'last_ad' => 0
    			));
    		}

            if ($ad_data_changes["status"] == "active") {
                cl_update_ad_data($ad_id, array(
                    'approved' => 'N'
                ));
            }

            else {
                cl_update_ad_data($ad_id, array(
                    'approved' => 'Y'
                ));
            }
    	}
    }
}

//Expired Ad - timeleft = 0
else if ($action == 'expire_ad_continue' && not_empty($cl['is_logged'])) {

    $data['err_code'] = 0;
    $data['status']   = 400;
    $ad_id            = fetch_or_get($_POST['id'], false);
    $ad_data          = cl_raw_ad_data($ad_id);
    $requiredAmount = fetch_or_get($_POST['requiredAmount'], false);
    $subscription_type = fetch_or_get($_POST['subscription_type'], false);

    if (empty($ad_data)) {
        $data['err_code'] = 'invalid_ad';
        return;
    }

    if ($ad_data['user_id'] != $me['id']) {
        $data['err_code'] = 'unauthorized';
        return;
    }

    if (empty($subscription_type) || !in_array($subscription_type, ['monthly', 'yearly'])) {
        $data['err_code'] = 'invalid_subscription';
        return;
    }

    if (empty($requiredAmount) || !is_numeric($requiredAmount)) {
        $data['err_code'] = 'invalid_amount';
        return;
    }

    if ($me['wallet'] < $requiredAmount) {
        $data['err_code'] = 'insufficient_funds';
        return;
    }

    if ($subscription_type == 'monthly') {
        if ($requiredAmount != $cl['config']['advertisement_mprice']) {
            $data['err_code'] = 'invalid_monthly_price';
            return;
        }

        // Add 30 days for monthly subscription
        if (!cl_update_ad_data($ad_id, array(
            'timeleft' => $ad_data['timeleft'] + 30
        ))) {
            $data['err_code'] = 'update_failed';
            return;
        }
        
        // Deduct monthly subscription fee from wallet
        if (!cl_update_user_data($me['id'], array(
            'wallet' => ($me['wallet'] - $cl['config']['advertisement_mprice'])
        ))) {
            $data['err_code'] = 'wallet_update_failed';
            return;
        }
        
        // Record the wallet transaction
        cl_db_insert(T_WALLET_HISTORY, array(
            'user_id'   => $me['id'],
            'operation' => 'expire_ad_continue',
            'amount'    => $cl['config']['advertisement_mprice'],
            'json_data' => json(array("ad_id" => $ad_id), true),
            'time'      => time()
        ));
    }
    else if ($subscription_type == 'yearly') {
        if ($requiredAmount != $cl['config']['advertisement_yprice']) {
            $data['err_code'] = 'invalid_yearly_price';
            return;
        }

        // Add 365 days for yearly subscription 
        if (!cl_update_ad_data($ad_id, array(
            'timeleft' => $ad_data['timeleft'] + 365
        ))) {
            $data['err_code'] = 'update_failed';
            return;
        }

        // Deduct yearly subscription fee from wallet
        if (!cl_update_user_data($me['id'], array(
            'wallet' => ($me['wallet'] - $cl['config']['advertisement_yprice'])
        ))) {
            $data['err_code'] = 'wallet_update_failed';
            return;
        }
        
        // Record the wallet transaction
        cl_db_insert(T_WALLET_HISTORY, array(
            'user_id'   => $me['id'],
            'operation' => 'expire_ad_continue',
            'amount'    => $cl['config']['advertisement_yprice'],
            'json_data' => json(array("ad_id" => $ad_id), true),
            'time'      => time()
        ));
    }

    // Send email notification about subscription renewal from ad owner to admin
    $cl['ad_message_data'] = array(
        'ad_name' => $ad_data['company'],
        'ad_owner_email' => $me['email'], 
        'ad_owner_name' => $me['fname'],
        'subscription_type' => $subscription_type,
        'amount_paid' => $requiredAmount,
        'timeleft' => ($subscription_type == 'monthly' ? 30 : 365),
        'site_name' => $cl['config']['name']
    );

    $email_data = array(
        'from_email' => $cl['config']['smtp_username'], // From ad owner
        'from_name' => $cl['config']['name'],  // Ad owner's name
        'to_email' => $cl['config']['email'], // To admin email
        'to_name' => $cl['config']['name'],
        'subject' => "Ad Subscription Renewal Notification",
        'charSet' => 'UTF-8',
        'is_html' => true,
        'message_body' => cl_template('emails/admin_ad_renewal', array(
            'ad_name' => $ad_data['company'],
            'ad_owner_email' => $me['email'],
            'ad_owner_name' => $me['fname'],
            'subscription_type' => $subscription_type,
            'amount_paid' => cl_money($requiredAmount),
            'timeleft' => ($subscription_type == 'monthly' ? '30 days' : '365 days'),
            'site_name' => $cl['config']['name']
        ))
    );

    // Send email using mailer library
    require_once(cl_full_path('core/libs/configs/mailer.php'));

    try {
        $mail->IsMail();
        if ($cl['config']['smtp_or_mail'] == 'smtp') {
            $mail->isSMTP();
            $mail->Timeout = 30;
            $mail->SMTPDebug = false;
            $mail->Host = $cl['config']['smtp_host'];
            $mail->SMTPAuth = true;
            $mail->Username = $cl['config']['smtp_username'];
            $mail->Password = $cl['config']['smtp_password'];
            $mail->SMTPSecure = $cl['config']['smtp_encryption'];
            $mail->Port = $cl['config']['smtp_port'];
            $mail->SMTPOptions = array(
                'ssl' => array(
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true
                )
            );
        }

        $mail->IsHTML($email_data['is_html']);
        $mail->setFrom($email_data['from_email'], $email_data['from_name']); // From ad owner
        $mail->addAddress($email_data['to_email'], $email_data['to_name']); // To admin
        $mail->Subject = $email_data['subject'];
        $mail->CharSet = $email_data['charSet'];
        $mail->MsgHTML($email_data['message_body']);
        $mail->send();
        $mail->ClearAddresses();
    }
    catch(Exception $e) {
        // Email sending failed, but continue with subscription renewal
    }
    $data['status'] = 200;

}

else if($action == 'ad_conversion') {
    if ($cl['config']['advertising_system'] == 'on') {
        $data['err_code'] = 0;
        $data['status']   = 400;
        $ad_id            = fetch_or_get($_POST['id'], false);
        $ad_data          = cl_raw_ad_data($ad_id);

        if (not_empty($ad_data)) {
            $ad_owner  = cl_raw_user_data($ad_data['user_id']);
            $conv_rate = $cl['config']['ad_conversion_rate'];
            $clicks    = cl_session('ad_clicks');

            if (not_empty($ad_owner)) {
                $data['status'] = 200;

                if (is_array($clicks) != true) {
                    $clicks = array();
                }

                cl_update_ad_data($ad_id, array(
                    'clicks' => ($ad_data['clicks'] += 1),
                    'budget' => ($ad_data['budget'] += $conv_rate)
                ));

                cl_update_user_data($ad_owner['id'], array(
                    'wallet' => ($ad_owner['wallet'] -= $conv_rate)
                ));

                if (in_array($ad_id, $clicks) != true) {
                    array_push($clicks, $ad_id);
                }

                cl_session('ad_clicks', $clicks);
            }
        }
    }
}

else if($action == 'delete_ad' && not_empty($cl['is_logged'])) {
    $data['err_code'] = 0;
    $data['status']   = 400;
    $ad_id            = fetch_or_get($_POST['id'], false);
    $ad_data          = cl_raw_ad_data($ad_id);

    if (not_empty($ad_data) && $ad_data['user_id'] == $me['id']) {
        cl_delete_media($ad_data['cover']);

        $db             = $db->where('id', $ad_id);
        $qr             = $db->delete(T_ADS);
        $data['status'] = 200;
    }
}

else if ($action == 'ad_like_count_update' && not_empty($cl['is_logged'])) {
    global $db, $cl;

    $user_id = (not_empty($cl["is_logged"])) ? $me["id"] : 0;
    $db = $db->where('user_id', $user_id);
    $has_liked = $db->getValue(T_AD_LIKES, 'COUNT(*)');
    
    if (not_empty($_POST['ad_id'])) {
        $adId = $_POST['ad_id'];
        if ($has_liked) {
            $db = $db->where('id', $adId);
            $likes_count = $db->getValue(T_ADS, 'likes_count');

            $db = $db->where('id', $adId);
            $up = $db->update(T_ADS, array(
                'likes_count' => $likes_count - 1
            ));

            if ($up) {
                $db = $db->where('id', $adId);
                $likes_count = $db->getValue(T_ADS, 'likes_count');
    
                $db = $db->where('user_id', $user_id);
                $adLike = $db->delete(T_AD_LIKES);
    
                $data = [
                    'errors' => 0,
                    'status' => 200,
                    'likes_count' => $likes_count
                ];
    
                return json_encode($data);
            }
        } else {
            $db = $db->where('id', $adId);
            $likes_count = $db->getValue(T_ADS, 'likes_count');
            
            $db = $db->where('id', $adId);
            $up = $db->update(T_ADS, array(
                'likes_count' => $likes_count + 1
            ));
    
            if ($up) {
                $db = $db->where('id', $adId);
                $likes_count = $db->getValue(T_ADS, 'likes_count');
    
                $likeByData = [
                    'ad_id' => $adId,
                    'user_id' => $user_id,
                    'time' => time()
                ];
                $adLike = $db->insert(T_AD_LIKES, $likeByData);
    
                $data = [
                    'errors' => 0,
                    'status' => 200,
                    'likes_count' => $likes_count
                ];
    
                return json_encode($data);
            }
        }
    }
}

elseif ($action == 'ad_reshare_count_update' && not_empty($cl['is_logged'])) {
    global $db, $cl;

    $user_id = (not_empty($cl["is_logged"])) ? $me["id"] : 0;
    $db = $db->where('user_id', $user_id);
    $has_reshared = $db->getValue(T_AD_SHARE, 'COUNT(*)');
    
    if (not_empty($_POST['ad_id'])) {
        $adId = $_POST['ad_id'];
        if ($has_reshared) {
            $db = $db->where('id', $adId);
            $reposts_count = $db->getValue(T_ADS, 'reposts_count');

            $db = $db->where('id', $adId);
            $up = $db->update(T_ADS, array(
                'reposts_count' => $reposts_count - 1
            ));

            if ($up) {
                $db = $db->where('id', $adId);
                $reposts_count = $db->getValue(T_ADS, 'reposts_count');
    
                $db = $db->where('user_id', $user_id);
                $adRepost = $db->delete(T_AD_SHARE);
    
                $data = [
                    'errors' => 0,
                    'status' => 200,
                    'reposts_count' => $reposts_count
                ];
    
                return json_encode($data);
            }
        } else {
            $db = $db->where('id', $adId);
            $reposts_count = $db->getValue(T_ADS, 'reposts_count');
            
            $db = $db->where('id', $adId);
            $up = $db->update(T_ADS, array(
                'reposts_count' => $reposts_count + 1
            ));
            
            if ($up) {
                $db = $db->where('id', $adId);
                $reposts_count = $db->getValue(T_ADS, 'reposts_count');
                
                $repostByData = [
                    'ad_id' => $adId,
                    'user_id' => $user_id,
                    'time' => time()
                ];
                $adRepost = $db->insert(T_AD_SHARE, $repostByData);
    
                $data = [
                    'errors' => 0,
                    'status' => 200,
                    'reposts_count' => $reposts_count
                ];
    
                return json_encode($data);
            }
        }
    }
}

elseif ($action == 'view_count_update' && not_empty($cl['is_logged'])) {
    global $db, $cl;

    $user_id = (not_empty($cl["is_logged"])) ? $me["id"] : 0;
    $db = $db->where('user_id', $user_id);
    $has_viewed = $db->getValue(T_AD_VIEW, 'COUNT(*)');

    // if (!$has_viewed) {
        if (not_empty($_POST['ad_id'])) {
            $adId = $_POST['ad_id'];
    
            $db = $db->where('id', $adId);
            $views_count = $db->getValue(T_ADS, 'views');
    
            $db = $db->where('id', $adId);
            $up = $db->update(T_ADS, array(
                'views' => $views_count + 1
            ));
    
            if ($up) {
                $db = $db->where('id', $adId);
                $views_count = $db->getValue(T_ADS, 'views');
    
                $viewByData = [
                    'ad_id' => $adId,
                    'user_id' => $user_id,
                    'time' => time()
                ];
                $adView = $db->insert(T_AD_VIEW, $viewByData);
    
                $data = [
                    'errors' => 0,
                    'status' => 200,
                    'views_count' => $views_count
                ];
    
                return json_encode($data);
            }
        }
    // }
}