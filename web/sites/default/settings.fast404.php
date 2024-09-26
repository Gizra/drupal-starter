<?php

// @codingStandardsIgnoreFile

/**
 * @file
 * Fast 404 settings.
 *
 * Fast 404 will do three separate types of 404 checking.
 *
 * The first is to check for URLs which appear to be files or images. If Drupal
 * is handling these items, then they were not found in the file system and are
 * a 404.
 *
 * The second is to check whether or not the URL exists in Drupal by checking
 * with the menu router, aliases and redirects. If the page does not exist, we
 * will serve a Fast 404 error and exit.
 *
 * The third is to listen to the KernelRequest event. If Drupal returns a
 * NotFoundHttpException exception, Fast 404 can intervene and serve a Fast 404
 * error and exit.
 */

/**
 * Disallowed extensions. Any extension set here will not be served by Drupal
 * and will get a Fast 404. This will not affect actual files on the filesystem
 * as requests hit them before defaulting to a Drupal request.
 *
 * Default extension list, this is considered safe and matches the list provided
 * by Drupal 8's $config['system.performance']['fast_404']['paths'].
 *
 * Default value for this setting is shown below.
 */
# $settings['fast404_exts'] = '/^(?!\/robots)^(?!\/system\/files).*\.(txt|png|gif|jpe?g|css|js|ico|swf|flv|cgi|bat|pl|dll|exe|asp)$/i';

/**
 * Allow anonymous users to hit URLs containing 'styles' even if the file
 * does not exist. TRUE is default behavior. If you know all image style
 * variations are already made set this to FALSE.
 *
 * Default value for this setting is TRUE.
 */
# $settings['fast404_allow_anon_imagecache'] = FALSE;

/**
 * BE CAREFUL with this setting as some modules use their own php files and you
 * need to be certain they do not bootstrap Drupal. If they do, you will need to
 * whitelist them too.
 *
 * Default value for this setting is FALSE.
 */
# $settings['fast404_url_whitelisting'] = TRUE;

/**
 * Array of allowed files/urls. Used if URL whitelisting is set to TRUE.
 *
 * Default value for this setting is an empty array.
 */
# $settings['fast404_whitelist'] = ['index.php', 'rss.xml', 'install.php', 'cron.php', 'update.php', 'xmlrpc.php'];

/**
 * Array of allowed URL fragment strings that conflict with Fast 404.
 *
 * Default value for this setting is FALSE.
 */
# $settings['fast404_string_whitelisting'] = ['/admin', '/cdn/ff', '/advagg_'];

/**
 * When Fast 404 checks for missing file assets, it will return a response
 * containing the message set in the fast404_html settings. You can override
 * this behavior with this setting and return the HTML from the file set in the
 * fast404_HTML_error_page setting.
 *
 * Default value for this setting is FALSE.
 */
# $settings['fast404_HTML_error_all_paths'] = TRUE;

/**
 * Subscribe to NotFoundHttpException event.
 *
 * The Fast 404 Event subscriber can listen to the NotFoundHttpException event
 * to completely replace the Drupal 404 page.
 *
 * By default, Fast 404 only listens to KernelRequest event. If a user hits a
 * valid path, but another module intervenes and returns a NotFoundHttpException
 * exception, e.g. m4032404 module, the native Drupal 404 page is returned
 * instead of the Fast 404 page.
 *
 * Default value for this setting is FALSE.
 */
# $settings['fast404_not_found_exception'] = TRUE;

/**
 * Path checking. USE AT YOUR OWN RISK.
 *
 * Path checking at this phase is more dangerous, but faster. Normally
 * Fast 404 will check paths during Drupal bootstrap via an early Event.
 * While this setting finds 404s faster, it adds a bit more load time to
 * regular pages, so only use if you are spending too much CPU/Memory/DB on
 * 404s and the trade-off is worth it.
 *
 * This setting will deliver 404s with less than 2MB of RAM.
 *
 * Default value for this setting is FALSE.
 */
$settings['fast404_path_check'] = TRUE;

/**
 * If you would prefer a stronger version of NO then return a 410 instead of a
 * 404. This informs clients that not only is the resource currently not
 * present but that it is not coming back and kindly do not ask again for it.
 * Reference: http://en.wikipedia.org/wiki/List_of_HTTP_status_codes
 *
 * Default value for this setting is FALSE.
 */
# $settings['fast404_return_gone'] = TRUE;

/**
 * If you use FastCGI, uncomment this line to send the type of header it needs.
 * Reference: http://php.net/manual/en/function.header.php
 *
 * Default value for this setting is 'mod_php'.
 */
$settings['fast404_HTTP_status_method'] = 'FastCGI';

/**
 * Default Fast 404 error message.
 *
 * Default value for this setting is shown below. The '@path' token will be
 * replaced by the the path being requested relative to the executed script.
 */
$settings['fast404_html'] = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML+RDFa 1.0//EN" "http://www.w3.org/MarkUp/DTD/xhtml-rdfa-1.dtd"><html xmlns="http://www.w3.org/1999/xhtml"><head><title>404 Not Found</title></head><body><h1>Not Found</h1><p>The requested URL "@path" was not found on this server. <a href="/">Go back to the homepage.</a></p></body></html>';

/**
 * By default we will show a super plain 404, because usually errors like this
 * are shown to browsers who only look at the headers. However, some cases
 * (usually when checking paths for Drupal pages) you may want to show a
 * regular 404 error. In this case you can specify a URL to another page and it
 * will be read and displayed (it can't be redirected to because we have to
 * give a 30x header to do that. This page needs to be in your docroot.
 * Accepts a normal path, or an array, keyed by langcodes.
 *
 * Default value for this setting is FALSE.
 */
# $settings['fast404_HTML_error_page'] = './my_page.html';
# The page is translatable.
# $settings['fast404_HTML_error_page'] = [
#   'en' => './my_page-en.html',
#   'fr' => './my_page-fr.html',
# ];

/**
 * Default value for this setting is FALSE. This setting needs to be enabled,
 * so that fast 404 respects the redirect module.
 */
$settings['fast404_respect_redirect'] = TRUE;

/**
 * Load the fast404.inc file.
 *
 * This is needed if you wish to do extension checking in settings.php.
 *
 * WARNING: This is not fully implemented and not ready to use.
 * @see: https://www.drupal.org/project/fast_404/issues/2961512
 *
 */
# if (file_exists($app_root . '/modules/contrib/fast_404/fast404.inc')) {
#   include_once $app_root . '/modules/contrib/fast_404/fast404.inc';
#   fast404_preboot($settings);
# }
