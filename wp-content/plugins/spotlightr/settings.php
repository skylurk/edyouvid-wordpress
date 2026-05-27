<div class="spotlightr-page">

<div id="spotlightr_wrapper">
    <?php if (get_option("spotlightr_token") != "") { ?>
        <div id="spotlightr_login_container" >
          <span class="spotlightr-msg"
                style="color:#000"><?php esc_html_e('You are logged in as', $this->namespace); ?> <b><?php echo esc_attr(get_option("spotlightr_username")); ?></b></span><span>&nbsp;&nbsp;<a
                  href='#' id="spotlightr_logout"><?php esc_html_e('Log Out', $this->namespace); ?></a></span>
        </div>
    <?php } else { ?>
        <div id="spotlightr_not_login_container" style="width:600px;">
            <div>
                <h3 style="padding: 2em 0 1em 0"><?php esc_html_e('Customers Sign in', $this->namespace); ?></h3>
                <div style="color: rgb(102,101,102);font-family: 'Open Sans', sans-serif;">
                    Already have a <a href="https://spotlightr.com" target="_blank">Spotlightr</a> account?<br/>
                </div>
                <form id="spotlightr_login" name="spotlightr_login">
                    <table>
                        <tr class="spotlightr-loginForm">
                            <td><input type="text" name="login" id="spotlightr_login_login" size="30"
                                       placeholder='<?php esc_html_e('Username', $this->namespace); ?>'
                                       class="spotlightr_input"></td>
                        </tr>
                        <tr class="spotlightr-loginForm">
                            <td><input type="password" autocomplete="on" name="password" id="spotlightr_login_password" size="30"
                                       placeholder='<?php esc_html_e('Password', $this->namespace); ?>'
                                       class="spotlightr_input"></td>
                        </tr>
                        <tr class="spotlightr-apiKeyForm" style="display:none">
                            <td><input type="text" name="apiKey" id="spotlightr_login_api_key" size="30"
                                       placeholder='<?php esc_html_e('Your API Key', $this->namespace); ?>'
                                       class="spotlightr_input"></td>
                        </tr>
                        <tr style="display: flex;width: 100%;justify-content: space-between;">
                            <td><a href="https://projects.spotlightr.com/auth/forgot"
                                   target="_blank"><?php esc_html_e('Forgot Password', $this->namespace); ?>?</a></td>
                            <td id="spotlightr_apiKeyLink"><a href="javascript:void(0)"><?php esc_html_e('Log in with API key', $this->namespace); ?>?</a></td>
                        </tr>
                        <tr>
                            <td><input type="submit" id="spotlightr_login_button" name="login"
                                       value="<?php esc_html_e('Login', $this->namespace); ?>" class="spotlightr-button">&nbsp;<img
                                    src='<?php echo esc_url($this->plugin_url . '/resources/img/loading.gif') ?>' id='spotlightr_login_wait'
                                    class="spotlightr-wait spotlightr-button" style='display:none;'></td>
                        </tr>
                    </table>
                    <div id="spotlightr_login_msg" style="height:35px;color: red; margin-top:10px;" class="spotlightr-msg"></div>
                </form>
            </div>
            <div>
                <h4 style="font-weight:bold"><?php esc_html_e('Not a customer yet?', $this->namespace); ?></h4>
                <div style="color: rgb(102,101,102);font-family: 'Open Sans', sans-serif;">
                    Sign up for a free 14 day trial.  No credit card required<br/> <br/>
                    <button type="button" class="spotlightr-button" data-toggle="modal" data-target="#myModal">
                        Free Trial
                    </button>
                </div>
            </div>
            <div class="modal fade spotlightr-modal-fade" id="myModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
                <div class="modal-dialog spotlightr-modal-dialog" role="document">
                    <div class="modal-content spotlightr-modal-content">
                        <div class="modal-header">
                            <h4 class="modal-title" id="myModalLabel">Sign Up</h4>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                        </div>
                        <div class="modal-body spotlightr-modal-body">
                            <form id="spotlightr_sign_up" action="javascript:void(0);">
                                <div>
                                    <label for="firstName"><b>First Name</b></label><br/>
                                    <input type="text" placeholder="Enter First Name" id="spotlightr_firstName" name="firstName" required>
                                </div>

                                <div>
                                    <label for="lastName"><b>Last Name</b></label><br/>
                                    <input type="text" placeholder="Enter Last Name" id="spotlightr_lastName" name="lastName" required>
                                </div>

                                <div>
                                    <label for="username"><b>Email</b></label><br/>
                                    <input type="email" placeholder="Enter Email" id="spotlightr_username" name="username" required>
                                </div>

                                <div>
                                    <label for="password"><b>Password</b></label><br/>
                                    <input type="password" placeholder="Enter Password" id="spotlightr_password" name="password" minlength="8" required>
                                </div>

                                <div>
                                    <label for="subdomain"><b>Subdomain ( for:
                                            your_subdomain.spotlightr.com)</b></label><br/>
                                    <input type="text" placeholder="Enter Subdomain" id="spotlightr_subdomain" name="subdomain" required>
                                </div>
                                <div id="sign_up_msg" style="height:35px;color: red; margin-top:10px;" class="spotlightr-msg"></div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                                    <button type="submit" id="spotlightr_sign_up_button" class="btn btn-primary">Create account</button>
                                    <img
                                        src='<?php echo esc_url( $this->plugin_url . '/resources/img/loading.gif') ?>' id='spotlightr_sign_up_wait'
                                        class="spotlightr-wait" style='display:none;'>
                                </div>
                            </form>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    <?php } ?>
</div>

<script>
    jQuery(document).ready(function ($) {

        $(document).on('click', '#spotlightr_sign_up_button', function (event) {
            $("#spotlightr_sign_up_wait").show();
            let firstName = jQuery("#spotlightr_firstName").val();
            let lastName = jQuery("#spotlightr_lastName").val();
            let username = jQuery("#spotlightr_username").val();
            let password = jQuery("#spotlightr_password").val();
            let subdomain = jQuery("#spotlightr_subdomain").val();

            $.ajax({
                url: "<?php echo esc_url(get_site_url())?>"+"/wp-admin/admin-ajax.php",
                method: 'POST',
                data: {
                    action: 'spotlightr_ajax_sign_up',
                    firstName: firstName,
                    lastName: lastName,
                    username: username,
                    password: password,
                    subdomain: subdomain
                },
                success: function (response) {
                    if (response == 'Created.') {
                        $('#spotlightr_sign_up_msg').html(response);

                        $.ajax({
                            url: "<?php echo esc_url(get_site_url())?>"+"/wp-admin/admin-ajax.php",
                            method: 'POST',
                            data: {
                                action: 'spotlightr_ajax_login',
                                username: username,
                                password: password
                            },
                            success: function (response) {
                                location.reload();
                            }
                        })
                    } else {
                        $('#spotlightr_sign_up_msg').html(response);
                        $("#spotlightr_sign_up_wait").hide();
                    }
                }
            });

        })

        $(document).on('click', '#spotlightr_login_button', function (event) {
            event.preventDefault();
            $("#spotlightr_login_wait").show();
            let data = {}
            data.action = 'spotlightr_ajax_login'
            let isSubmit = false
            if($('.spotlightr-apiKeyForm').is(":visible")){
                let apiKey = jQuery("#spotlightr_login_api_key").val();
                if (apiKey === '') {
                    $('#spotlightr_login_msg').html('API key is required');
                    $("#spotlightr_login_wait").hide();
                } else {
                    isSubmit = true
                    data.apiKey = apiKey
                }
            }
            else{
                let name = jQuery("#spotlightr_login_login").val();
                let pass = jQuery("#spotlightr_login_password").val();

                if (name == '' || pass == '') {
                    $('#spotlightr_login_msg').html('Username and Password fields are required');
                    $("#spotlightr_login_wait").hide();
                } else {
                    isSubmit = true
                    data.username = name
                    data.password = pass
                }
            }
            if(isSubmit){
                $.ajax({
                    url: "<?php echo esc_url(get_site_url())?>"+"/wp-admin/admin-ajax.php",
                    method: 'POST',
                    data: data,
                    success: function (response) {
                        if (response == 'success') {
                            location.reload();
                        } else {
                            $('#spotlightr_login_msg').html(response);
                            $("#spotlightr_login_wait").hide();
                        }
                    }
                });
            }
        })

        $(document).on('click', '#spotlightr_logout', function (event) {
            event.preventDefault();

            $.ajax({
                url: "<?php echo esc_url(get_site_url())?>"+"/wp-admin/admin-ajax.php",
                method: 'POST',
                data: {
                    action: 'spotlightr_ajax_logout',
                },
                success: function (response) {
                    location.reload();
                }
            });
        })
        $(document).on('click','#spotlightr_apiKeyLink',function(event){
            event.preventDefault();
            $('#spotlightr_login_msg').html('')
            if($('.spotlightr-apiKeyForm').is(":visible")){
                $('.spotlightr-loginForm').show()
                $('.spotlightr-apiKeyForm').hide();
                $(this).find('a').text('Log in with API key')
            }
            else{
                $('.spotlightr-loginForm').hide()
                $('.spotlightr-apiKeyForm').show();
                $(this).find('a').text('Log in with Credentials')
            }
        })
    })
</script>
</div>
