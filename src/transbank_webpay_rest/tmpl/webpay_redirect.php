<?php

if (!defined('_JEXEC')) {
    return;
}

/**
 * @var string $sanitizedURL
 * @var array  $data
 */
extract($displayData);
?>
<form action="<?php echo $sanitizedURL; ?>" method="POST" name="webpayForm">
    <?php foreach ($data as $name => $value): ?>
        <input type="hidden" name="<?php echo htmlentities($name); ?>" value="<?php echo htmlentities($value); ?>">
    <?php endforeach; ?>
</form>
<script language="JavaScript">
    document.webpayForm.submit();
</script>
