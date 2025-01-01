<?php 

$api_url  = get_option( 'api_url', 'https://api.linkedin.com/v2/ugcPosts'  );
$api_key  = get_option( 'api_key' );

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

    <button type="button" class="save-btn mt-20 button-flex" id="save_credentials">
        <span>Save</span>
        <span class="spinner-loader-wrapper"></span>
    </button>
</div>