<?php
return array(
	'site_title'                            => 'SimPas',
	'site_description'                      => 'Site description',
	'home_url'                              => 'http://example.com/',
	'favicon_url'                           => '',
	'show_social_icons'                     => true,
	'social_sites'                          => array(
												'st_email'      => 'E-mail',
												'st_twitter'    => 'Tweet',
												'st_facebook'   => 'Facebook',
												'st_googleplus' => 'Google+'
											),
	'enable_line_numbers'                   => true,
	'max_title_len'                         => 20,
	'max_author_len'                        => 8,
	'show_ie_info' 				            => true,
	'friendly_syntax_names'                 => true,
	'show_syntax_in_title'                  => true,
	'default_syntax'                        => 'text',
	'show_additional_buttons_in_error_info' => false,
	'default_lang'                          => 'en',
	'show_breadcrumb'                       => true,
	'google_analitycs_account_key'          => '',
	'google_bots'                           => false,
	'show_ip_sender'                        => false,
	'show_ip_sender_except_ip'              => array(
												'127.0.0.1'
											),
	'blocked_ip'                            => array(),
	'max_len'                               => 10000,
	'max_kb_size'                           => 512,
	'my_global_message'                     => array(
											    //'info'    => 'Example message 1',
												//'error'   => 'Example message 2',
												//'success' => 'Example message 3'
											),
	'antyflood_status'                      => true,
	'antyflood_time'                        => 10,
	'antyflood_except_ip'                   => array(
												//'127.0.0.1'
											),

	//--- Advanced settings
	'in_dev'                                => false,
	'simple_debug'                          => false,
	'extra_debug'                           => false,
	'show_version'                          => true,
	'slow_query'                            => 0.5,
	'use_furl'                              => false,
	'errorlog_prefix_filename'              => 'errorlog_',
	'error_reporting'                       => false,
	'status'                                => true,
	'installed'                             => false
);