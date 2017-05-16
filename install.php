<?php

echo "\nInstaling remote Libraries";

exec('php composer.phar install');




echo "\nCreating service";

copy('sismolink.service', '/lib/systemd/system/sismolink.service');
copy('sismolink-websocket.service', '/lib/systemd/system/sismolink-websocket.service');
exec('systemctl enable sismolink.service');
exec('systemctl enable sismolink-websocket.service');
