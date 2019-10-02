# OAuth2 Demo

This is based on the demo provided at https://www.oauth.com/oauth2-servers/accessing-data/

## How to use

* Copy `secrets.php.SAMPLE` to `secrets.php`
* Create a `.gitignore` file and add `secrets.php`: don't save your secrets to GitHub!
* Visit https://github.com/settings/developers and click _New OAuth App_
* For _Homepage URL_, enter `http://localhost:8000`
* For _Authorization Callback URL_, enter `http://localhost:8000/index.php`
* Edit `secrets.php` and replace the Client ID and Client Secret with the values provided by GitHub
* Run `php -S localhost:8000`
* Visit `http://localhost:8000` in your web browser

## Troubleshooting

The _logout_ functionality is not implemented. If the application is not behaving as expected, here are some things to try:

* Check the messages in the PHP server output. Are there any error messages?
* The OAuth application in GitHub will allow you to revoke all user tokens
* Session variables may persist on disk. You can destroy a session by deleting any session files.

### Session files

PHP can display the path to stored sessions with the following command:

    echo session_save_path();

Assuming your session files are stored in `/var/lib/php/sessions`, the following should work:

    rm /var/lib/php/sessions/*

