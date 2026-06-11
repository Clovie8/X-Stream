<?php

setcookie('user_categories', '', time() - 3600, "/");
setcookie('user_translators', '', time() - 3600, "/");
setcookie('series_categories', '', time() - 3600, "/");
setcookie('series_translators', '', time() - 3600, "/");

header("Location: /");
exit;
?>