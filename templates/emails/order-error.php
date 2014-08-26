<?php


if (!defined('ABSPATH')) exit; // Exit if accessed directly

?>

<?php do_action('woocommerce_email_header', $email_heading); ?>

<p>
    <?php printf("Something is technically wrong - we meet some issues while buying, please check details below."); ?> <br /> <br />

    <b>Error class</b>: <?php echo get_class($error); ?> <br />
    <b>Message</b>: <?php echo $error->getMessage(); ?> <br /> <br />

    <b>Dump:</b> <br />
    <?php var_dump($error); ?>
</p>

<?php do_action('woocommerce_email_footer'); ?>