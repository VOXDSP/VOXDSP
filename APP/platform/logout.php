<?php

    session_start();
    unset($_SESSION['auth']);
    unset($_SESSION['switcher']);
    setcookie("uah", 'erased', time()+1337);
    header('Location: index.php');

?>