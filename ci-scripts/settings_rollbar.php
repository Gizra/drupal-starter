$config["rollbar.settings"]["enabled"] = TRUE;
$config["rollbar.settings"]["environment"] = "jep-rootone.travis-local";
$config["rollbar.settings"]["log_level"] = [0,1,2,3,4];
$config["rollbar.settings"]["access_token"] = getenv('ROLLBAR_SERVER_TOKEN');
