<?php
return [
  'install_path'   => realpath(__DIR__ . '/..'),
  'data_dir'       => realpath(__DIR__),

  // DB
  'db_host'        => '127.0.0.1',
  'db_name'        => 'edtb',
  'db_user'        => 'edtb',
  'db_pass'        => 'edtbpass',
  'db_port'        => 3306,

  // Elite Dangerous paths (Proton)
  'netlog_dir'     => '/mnt/Unlimited-Gaming/SteamLibrary/steamapps/compatdata/359320/pfx/drive_c/users/steamuser/Saved Games/Frontier Developments/Elite Dangerous',
  'screens_dir'    => '/mnt/Unlimited-Gaming/SteamLibrary/steamapps/compatdata/359320/pfx/drive_c/users/steamuser/Pictures/Frontier Developments/Elite Dangerous',

  // Now Playing file
  'nowplaying_txt' => __DIR__ . '/nowplaying.txt',
];
