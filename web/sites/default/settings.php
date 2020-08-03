<?php

/**
 * Load services definition file.
 */
$settings['container_yamls'][] = __DIR__ . '/services.yml';

/**
 * Include the Pantheon-specific settings file.
 *
 * n.b. The settings.pantheon.php file makes some changes
 *      that affect all environments that this site
 *      exists in.  Always include this file, even in
 *      a local development environment, to ensure that
 *      the site settings remain consistent.
 */
include __DIR__ . "/settings.pantheon.php";

/**
 * Place the config directory outside of the Drupal root.
 */
/**
 * Drupal 8.8 workaround
 */
$settings['config_sync_directory'] = dirname(DRUPAL_ROOT) . '/config';


$conf['https'] = TRUE;

/**
 * If there is an environment settings file, then include it
 */
$envirosettings = __DIR__ . "/enviros/settings." . $_ENV['PANTHEON_ENVIRONMENT'] . ".php";
if (file_exists($envirosettings)) {
  include $envirosettings;
}


/**
 * If there is a local settings file, then include it
 */
$local_settings = __DIR__ . "/settings.local.php";
if (file_exists($local_settings)) {
  include $local_settings;
}

/**
 * Always install the 'standard' profile to stop the installer from
 * modifying settings.php.
 */
$settings['install_profile'] = 'standard';
