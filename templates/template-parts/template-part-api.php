<?php

$api_url       = get_option( 'api_url', 'https://api.linkedin.com/v2/ugcPosts' );
$api_key       = get_option( 'api_key' );
$auth_token    = get_option( 'auth_token' );
$client_id     = get_option( 'linkedin_client_id' );
$client_secret = get_option( 'linkedin_client_secret' );
$redirect_url  = get_option( 'linkedin_callback_url' );

?>

<h4 class="common-title">Linkedin API Credentials</h4>

<div class="credentials-wrapper overflow-hidden">
    <div class="common-input-group">
        <label for="api_url">API Url</label>
        <input type="text" class="common-form-input" name="api_url" id="api_url" placeholder="API Url"
            value="<?= $api_url ?>" required>
    </div>
    <div class="common-input-group mt-20">
        <label for="api_key">Access Token</label>
        <input type="text" class="common-form-input" name="api_key" id="api_key" placeholder="Access Token"
            value="<?= $api_key ?>" required>
    </div>
    <div class="common-input-group mt-20">
        <label for="auth_token">Auth Token</label>
        <input type="text" class="common-form-input" name="auth_token" id="auth_token" placeholder="Auth Token"
            value="<?= $auth_token ?>" required>
    </div>
    <div class="common-input-group mt-20">
        <label for="client_id">Client Id</label>
        <input type="text" class="common-form-input" name="client_id" id="client_id" placeholder="Client Id"
            value="<?= $client_id ?>" required>
    </div>
    <div class="common-input-group mt-20">
        <label for="client_secret">Client Secret</label>
        <input type="text" class="common-form-input" name="client_secret" id="client_secret" placeholder="Client Secret"
            value="<?= $client_secret ?>" required>
    </div>
    <div class="common-input-group mt-20">
        <label for="redirect_url">Redirect Url</label>
        <input type="text" class="common-form-input" name="redirect_url" id="redirect_url" placeholder="Client Secret"
            value="<?= $redirect_url ?>" required>
    </div>

    <button type="button" class="save-btn mt-20 button-flex" id="save_credentials">
        <span>Save</span>
        <span class="spinner-loader-wrapper"></span>
    </button>
</div>