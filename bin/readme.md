# Dev tools

## Generate password hash (CLI only)

    php newPass.php "MyPassword123!"

Outputs an Argon2id hash and a ready-to-paste SQL UPDATE statement.
Cannot be run from a browser — refuses with 403.

in phpMyadmin

UPDATE `users` SET `password` = '$argon2id$v=19$m=65536,t=4,p=1$WTY3SVRQTnF0OFA3cGZNTg$xA5u698zTMlucEvREjJ/gWrF7777BzO9jsQ2ytNGeew' WHERE `phone` = '+6281234567890';