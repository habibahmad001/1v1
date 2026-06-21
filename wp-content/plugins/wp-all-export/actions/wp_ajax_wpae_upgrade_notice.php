<?php

defined( 'ABSPATH' ) || exit;


function pmxe_wp_ajax_wpae_upgrade_notice()
{
    if (!check_ajax_referer('wp_all_export_secure', 'security', false)) {
        exit(json_encode(array('html' => __('Security check', 'wp-all-export'))));
    }

    if (!current_user_can(PMXE_Plugin::$capabilities)) {
        exit(json_encode(array('html' => __('Security check', 'wp-all-export'))));
    }

	?>
	<style type="text/css">
		.easing-spinner {
            width: 30px;
            height: 30px;
            position: relative;
            display: inline-block;

            margin-top: 7px;
            margin-left: -25px;

            float: left;
        }

        .double-bounce1, .double-bounce2 {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            background-color: #fff;
            opacity: 0.6;
            position: absolute;
            top: 0;
            left: 0;

            -webkit-animation: sk-bounce 2.0s infinite ease-in-out;
            animation: sk-bounce 2.0s infinite ease-in-out;
        }

        .double-bounce2 {
            -webkit-animation-delay: -1.0s;
            animation-delay: -1.0s;
        }

        .wpai-save-button svg {
            margin-top: 7px;
            margin-left: -215px;
            display: inline-block;
            position: relative;
        }

        @-webkit-keyframes sk-bounce {
            0%, 100% {
                -webkit-transform: scale(0.0)
            }
            50% {
                -webkit-transform: scale(1.0)
            }
        }

        @keyframes sk-bounce {
            0%, 100% {
                transform: scale(0.0);
                -webkit-transform: scale(0.0);
            }
            50% {
                transform: scale(1.0);
                -webkit-transform: scale(1.0);
            }
        }
	</style>
	<div id="post-preview" class="wpallexport-preview wpallexport-upgrade-notice">
        <a class="custom-close" href="#"></a>
        <div class="upgrade">
    		<h1><?php esc_html_e( 'Exporting Users is a Pro Feature', 'wp-all-export' ); ?></h1>
    		<h2><?php echo wp_kses( __( 'Purchase a Pro package to export users, along with<br>many other powerful features.', 'wp-all-export' ), array( 'br' => array() ) ); ?></h2>

    		<div class="features">
    			<div class="column list">
    				<span><?php esc_html_e( 'Export all user data', 'wp-all-export' ); ?></span>
    				<span><?php esc_html_e( 'Drag & drop interface', 'wp-all-export' ); ?></span>
    				<span><?php esc_html_e( 'Create any CSV or XML', 'wp-all-export' ); ?></span>
    				<span><?php esc_html_e( 'WooCommerce, ACF and more', 'wp-all-export' ); ?></span>
    			</div>
    			<div class="column cta">
    				<div class="button upgrade-button">
    					<span class="subscribe-button-text"><?php esc_html_e( 'Upgrade to Pro', 'wp-all-export' ); ?></span>
    				</div>
    				<span class="trusted"><?php esc_html_e( 'Trusted by over 200,000 happy users', 'wp-all-export' ); ?></span>
    			</div>
    		</div>
    		<ul class="perks">
    			<li class="guarantee"><span><?php esc_html_e( '90 Day Guarantee', 'wp-all-export' ); ?></span><small><?php echo wp_kses( __( 'No questions,<br> full refund.', 'wp-all-export' ), array( 'br' => array() ) ); ?></small></li>
    			<li class="updates"><span><?php esc_html_e( 'Lifetime Updates', 'wp-all-export' ); ?></span><small><?php echo wp_kses( __( 'Pay once and get<br>updates for life.', 'wp-all-export' ), array( 'br' => array() ) ); ?></small></li>
    			<li class="support"><span><?php esc_html_e( 'World Class Support', 'wp-all-export' ); ?></span><small><?php echo wp_kses( __( 'Get help from a team<br>of experts.', 'wp-all-export' ), array( 'br' => array() ) ); ?></small></li>
    			<li class="license"><span><?php esc_html_e( 'Unlimited Sites', 'wp-all-export' ); ?></span><small><?php echo wp_kses( __( 'Install on as many sites<br>as you like.', 'wp-all-export' ), array( 'br' => array() ) ); ?></small></li>
    		</ul>
    		<p class="already-have"><a class="already-have-link"><?php esc_html_e( 'Already own the Pro version?', 'wp-all-export' ); ?></a></p>
        </div>
        <div class="install">
            <h1><?php esc_html_e( 'User Export Add-On is not installed', 'wp-all-export' ); ?></h1>
            <p><?php echo 'You can download the User Export Add-On from the customer portal: <a href="https://www.wpallimport.com/portal/">https://www.wpallimport.com/portal/</a>. Once you install it, you\'ll be able to export users.'; ?></p>
        </div>
	</div>
    <?php
    wp_die();
}