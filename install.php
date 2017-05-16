<?php

echo "\nInstaling remote Libraries";

exec('php composer.phar install');




echo "\nCreating service";

copy('sismolink.sevice', '/lib/systemd/system/sismolink.sevice');

exec('systemctl enable sismolink.service');
