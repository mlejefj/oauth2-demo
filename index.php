<?php

// load the secrets file
require('secrets.php');

// Define CONSTANTS (note uppercase), which have global scope

// Send the user to this URL for authorization
define("AUTHORIZE_URL",     "https://github.com/login/oauth/authorize");

// API endpoint URL to get an access token
define("TOKEN_URL",         "https://github.com/login/oauth/access_token");

// GitHub's base URL for API requests
define("API_URL_BASE",      "https://api.github.com/");

// This is the base URL for this app
define("BASE_URL",          'http://' . $_SERVER['SERVER_NAME'] . ':' . $_SERVER['SERVER_PORT'] . $_SERVER['PHP_SELF']);


// Start a session so we have a place to
// store things between redirects
session_start();


// apiRequest: process an API request
// Takes 3 parameters:
// - URL
// - post (boolean): false for GET requests, true for POST
// - headers (array): HTTP headers
function apiRequest($url, $post=FALSE, $headers=array()) {
  $ch = curl_init($url);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
 
  if($post)
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post));
 
  $headers = [
    'Accept: application/vnd.github.v3+json, application/json',
    'User-Agent: https://example-app.com/'
  ];
 
  if(isset($_SESSION['access_token']))
    $headers[] = 'Authorization: Bearer '.$_SESSION['access_token'];
 
  curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
 
  $response = curl_exec($ch);
  return json_decode($response, true);
}


// Process a user login
//
// This function will:
// - Set session state (we will verify on return from GitHub)
// - Build GET parameters
// - Redirect to GitHub
function login() {

  // Generate a random hash and store in the session
  $_SESSION['state'] = bin2hex(random_bytes(16));

  // Build GET parameter array
  $params = array(
    'response_type' => 'code',
    'client_id' => CLIENT_ID,
    'redirect_uri' => BASE_URL,
    'scope' => 'user public_repo',
    'state' => $_SESSION['state']
  );

  // Redirect the user to Github's authorization page
  header('Location: ' . AUTHORIZE_URL . '?' . http_build_query($params));
  die();
}

// Retrieve an OAuth2 access token
//
// This function will:
// - Verify that the state returned by GitHub matches SESSION state
// - Make an API request to GitHub with auth code to request access code
// - Store the access code in the SESSION scope
// - Redirect to the application home page
function getAccessToken() {

  // Verify the state matches our stored state
  if(!isset($_GET['state']) || $_SESSION['state'] != $_GET['state']) {

    header('Location: ' . BASE_URL . '?error=invalid_state');
    die();
  }

  // Exchange the auth code for an access token
  $token = apiRequest(TOKEN_URL, array(
    'grant_type' => 'authorization_code',
    'client_id' => CLIENT_ID,
    'client_secret' => CLIENT_SECRET,
    'redirect_uri' => BASE_URL,
    'code' => $_GET['code']
  ));

  // Store the access token in the SESSION scope
  $_SESSION['access_token'] = $token['access_token'];

  // Redirect user to the main application page
  header('Location: ' . BASE_URL);
  // Stop further page processing
  die();
}


// Retrieve GitHub repos and display a formatted HTML list
//
// This function will:
// - Call the GitHub API to request a list of repos
// - Loop over the returned list and format it as HTML
function getRepos() {
    // Find all repos created by the authenticated user
    $repos = apiRequest(API_URL_BASE . 'user/repos?' . http_build_query([
      'sort' => 'created', 'direction' => 'desc'
    ]));
   
    echo '<ul>';
    foreach($repos as $repo)
      echo '<li><a href="' . $repo['html_url'] . '">'
        . $repo['name'] . '</a></li>';
    echo '</ul>';
}


// Display the default application view
//
// This function will:
// - Display a logged in view if an access token is present
// - Otherwise, provide a link to log in
function displayDefaultView() {

  // Check for access token in the SESSION scope
  if(!empty($_SESSION['access_token'])) {
    echo '<h3>Logged In</h3>';
    echo '<p><a href="?action=repos">View Repos</a></p>';
    echo '<p><a href="?action=logout">Log Out</a> (currently non-functional)</p>';
  } else {
    echo '<h3>Not logged in</h3>';
    echo '<p><a href="?action=login">Log In</a></p>';
  }

  // Stop further page processing
  die();
}


// User requests login functionality when:
// - There is an appropriate GET parameter, e.g. ?action=login
if(isset($_GET['action']) && $_GET['action'] == 'login') {
  login();
}


// GitHub redirects the user to the callback URL when:
// - There are appropriate GET parameters:
//       - ?code=xxxxx&state=xxxxx
if(isset($_GET['code']) && isset($_GET['state'])) {
  getAccessToken();
}


// User requests a list of Git repos when:
// - There is an appropriate GET parameter:
//       - ?action=repos
if(isset($_GET['action']) && $_GET['action'] == 'repos') {
  getRepos();
}


// User is logged in but no action is requested when:
// - There are no GET parameters
if(!isset($_GET['action'])) {
  displayDefaultView();
}
