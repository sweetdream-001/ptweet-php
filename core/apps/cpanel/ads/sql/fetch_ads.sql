SELECT 
    a.`id`, 
    a.`user_id`, 
    a.`cover`, 
    a.`company`,
    a.`target_url`, a.`timeleft`, 
    a.`views`, 
    a.`clicks`, 
    a.`budget`, 
    a.`approved`, 
    a.`time`, 
    a.`status`, 
    u.`avatar`, 
    u.`username`, 
    u.`verified`, 
    u.`email`,
    CONCAT(u.`fname`) AS name 
FROM 
    `<?php echo($data['t_ads']); ?>` a
INNER JOIN 
    `<?php echo($data['t_users']); ?>` u 
    ON a.`user_id` = u.`id`
WHERE 
    a.`status` != 'orphan' 
    AND a.`target` = 'ad' 
    AND u.`active` = '1'
    <?php if (!empty($data['search'])): ?>
        AND a.`company` LIKE "%<?php echo cl_text_secure($data['search']); ?>%"
    <?php endif; ?>
ORDER BY 
    a.`id` <?php echo($data['order']); ?> 
LIMIT <?php echo($data['limit']); ?> 
OFFSET <?php echo($data['offset']); ?>;