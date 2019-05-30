<?php
/**
 * @var $user
 * @var $meta
 */
$user = $data;
?>
-----------------------------------
Public info
-----------------------------------
Full name:          <?php echo $user->full_name . PHP_EOL; ?>
Description:        <?php echo $user->biography . PHP_EOL; ?>
<?php if ($user->is_business_account == 1) { ?>
Business_category:  <?php echo $user->business_category_name . PHP_EOL;
} ?>
Account:            <?php if ($user->is_verified) {
    echo 'verified, ';
} else {
    echo 'not verified, ';
}
if ($user->is_private) {
    echo 'private' . PHP_EOL;
} else {
    echo 'public' . PHP_EOL;
} ?>
External url:       <?php echo $user->external_url . PHP_EOL; ?>
Instagram url:      <?php echo 'https://instagram.com/' . $user->username . PHP_EOL; ?>

-----------------------------------
Countable
-----------------------------------
Posts:              <?php echo $user->edge_owner_to_timeline_media . PHP_EOL; ?>
Followers:          <?php echo $user->edge_followed_by . PHP_EOL; ?>
Following:          <?php echo $user->edge_follow . PHP_EOL; ?>

-----------------------------------
Metadata
-----------------------------------
Instagram id:       <?php echo $user->id . PHP_EOL; ?>
Service sn:         <?php echo $user->usersn . PHP_EOL; ?>
Set update:         <?php echo $user->update_time . PHP_EOL; ?>