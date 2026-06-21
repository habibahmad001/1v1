<?php

// phpcs:ignoreFile WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound,WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound,WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedClassFound -- legitimate plugin prefixes (pmxe/PMXE/wpae/Wpae/wp_all_export/wpallexport/XmlExport/CdataStrategy/VariableProductTitle/Soflyy/GF_Export); Plugin Check does not honor phpcs.xml prefix declaration
defined( 'ABSPATH' ) || exit;

/**
*	AJAX action for preview export row
*/

function pmxe_wp_ajax_wpae_preview(){

	if ( ! check_ajax_referer( 'wp_all_export_secure', 'security', false )){
		exit( json_encode(array('html' => __('Security check', 'wp-all-export'))) );
	}

	if ( ! current_user_can( PMXE_Plugin::$capabilities ) ){
		exit( json_encode(array('html' => __('Security check', 'wp-all-export'))) );
	}

	XmlExportEngine::$is_preview = true;

	$custom_xml_valid = true;

	ob_start();

	$values = array();

	// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized,WordPress.Security.ValidatedSanitizedInput.InputNotValidated,WordPress.Security.ValidatedSanitizedInput.MissingUnslash -- structured payload parsed via parse_str; values keep their escape backslashes for the downstream stripslashes loop on cc_options
	parse_str(isset($_POST['data']) ? (string) $_POST['data'] : '', $values);

	if(is_array($values['cc_options'])) {

		foreach ($values['cc_options'] as &$value) {
			$value = stripslashes($value);
		}
	}

	$export_id = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;

	$exportOptions = $values + (PMXE_Plugin::$session->has_session() ? PMXE_Plugin::$session->get_clear_session_data() : array()) + PMXE_Plugin::get_default_import_options();

	// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized,WordPress.Security.ValidatedSanitizedInput.MissingUnslash -- XML template payload validated/parsed downstream; stripcslashes() is the intended decoder for this field and pairs with the form-side encoding
	$exportOptions['custom_xml_template'] = (isset($_POST['custom_xml'])) ? stripcslashes( (string) $_POST['custom_xml'] ) : '';
	$exportOptions['custom_xml_template'] = str_replace('<ID>','<id>', $exportOptions['custom_xml_template'] );
	$exportOptions['custom_xml_template'] = str_replace('</ID>','</id>', $exportOptions['custom_xml_template'] );

	if ( ! empty($exportOptions['custom_xml_template'])) {
		$custom_xml_template_line_count = substr_count($exportOptions['custom_xml_template'], "\n");
    }

	if(empty($exportOptions['cpt'])) {
		$postTypes           = [];
		$exportqueryPostType = [];

		if ( isset( $exportOptions['exportquery'] ) && ! empty( $exportOptions['exportquery']->query['post_type'] ) ) {
			$exportqueryPostType = [ $exportOptions['exportquery']->query['post_type'] ];
		}

		if ( empty( $postTypes ) ) {
			$postTypes = $exportqueryPostType;
		}

		$exportOptions['cpt'] = $postTypes;
	}

    $errors = new WP_Error();

	$engine = new XmlExportEngine($exportOptions, $errors);

	XmlExportEngine::$exportOptions      = $exportOptions;
	XmlExportEngine::$is_user_export     = $exportOptions['is_user_export'];
	XmlExportEngine::$is_comment_export  = $exportOptions['is_comment_export'];
	XmlExportEngine::$is_taxonomy_export = $exportOptions['is_taxonomy_export'];
	XmlExportEngine::$exportID 			 = $export_id;

	if ( class_exists('SitePress') && ! empty(XmlExportEngine::$exportOptions['wpml_lang'])){
		do_action( 'wpml_switch_language', XmlExportEngine::$exportOptions['wpml_lang'] );
	}

	if (XmlExportEngine::$exportOptions['export_to'] == XmlExportEngine::EXPORT_TYPE_XML && in_array(XmlExportEngine::$exportOptions['xml_template_type'], array('custom', 'XmlGoogleMerchants')) ){

		if ( empty(XmlExportEngine::$exportOptions['custom_xml_template']) )
		{
			$errors->add('form-validation', __('XML template is empty.', 'wp-all-export'));
		}

		if ( ! empty(XmlExportEngine::$exportOptions['custom_xml_template'])){

			$engine->init_additional_data();

			$engine->init_available_data();

			$result = $engine->parse_custom_xml_template();		
			$line_numbers = $result['line_numbers'];
			if ( ! $errors->get_error_codes()) {
				XmlExportEngine::$exportOptions = array_merge(XmlExportEngine::$exportOptions, $result);
			}

			$originalXmlTemplate = $exportOptions['custom_xml_template'];
			libxml_use_internal_errors(true);
			libxml_clear_errors();

			//Add root se we make sure there is a root tag
			$result['original_post_loop'] = '<root>'.$result['original_post_loop'].'</root>';

			$custom_xml_template = simplexml_load_string($result['original_post_loop']);

			if ($custom_xml_template === false) {
				$custom_xml_template_errors = libxml_get_errors();
				libxml_clear_errors();
				$custom_xml_valid = false;
				// Remove one line because we added root
				$line_difference = $custom_xml_template_line_count - $line_numbers - 1;
			}
			$exportOptions['custom_xml_template'] = str_replace("<!-- BEGIN POST LOOP -->", "<!-- BEGIN LOOP -->", $exportOptions['custom_xml_template']);
			$exportOptions['custom_xml_template'] = str_replace("<!-- END POST LOOP -->", "<!-- END LOOP -->", $exportOptions['custom_xml_template']);

		}
	}

	if(isset($_GET['show_cdata'])) {
		XmlExportEngine::$exportOptions['show_cdata_in_preview'] = (bool)$_GET['show_cdata'];
	} else {
		XmlExportEngine::$exportOptions['show_cdata_in_preview'] = false;
	}

	if ( $errors->get_error_codes()) {
		$msgs = $errors->get_error_messages();
		if ( ! is_array($msgs)) {
			$msgs = array($msgs);
		}
		foreach ($msgs as $msg): ?>
			<div class="error"><p><?php echo wp_kses_post($msg); ?></p></div>
		<?php endforeach;
		exit( json_encode(array('html' => ob_get_clean())) );
	}

	if ( 'advanced' == $exportOptions['export_type'] )
	{
		if ( XmlExportEngine::$is_user_export ) {
			// phpcs:ignore Generic.PHP.ForbiddenFunctions.Found -- intentional: executes saved WP_Query argument string
			$exportQuery = eval('return new WP_User_Query(array(' . $exportOptions['wp_query'] . ', \'offset\' => 0, \'number\' => 10));');
		}
		elseif ( XmlExportEngine::$is_comment_export ) {
			// phpcs:ignore Generic.PHP.ForbiddenFunctions.Found -- intentional: executes saved WP_Query argument string
			$exportQuery = eval('return new WP_Comment_Query(array(' . $exportOptions['wp_query'] . ', \'offset\' => 0, \'number\' => 10));');
		}
		else {
			remove_all_actions('parse_query');
			remove_all_actions('pre_get_posts');
			remove_all_filters('posts_clauses');
			
			// phpcs:ignore Generic.PHP.ForbiddenFunctions.Found -- intentional: executes saved WP_Query argument string
			$exportQuery = eval('return new WP_Query(array(' . $exportOptions['wp_query'] . ', \'offset\' => 0, \'posts_per_page\' => 10));');
		}
	}
	else
	{
		XmlExportEngine::$post_types = $exportOptions['cpt'];

		if ( in_array('users', $exportOptions['cpt']) or in_array('shop_customer', $exportOptions['cpt']))
		{
			add_action('pre_user_query', 'wp_all_export_pre_user_query', 10, 1);
			$exportQuery = new WP_User_Query( array( 'orderby' => 'ID', 'order' => 'ASC', 'number' => 10 ));
			remove_action('pre_user_query', 'wp_all_export_pre_user_query');
		}
		elseif ( in_array('taxonomies', $exportOptions['cpt']))
		{
			add_filter('terms_clauses', 'wp_all_export_terms_clauses', 10, 3);
			$exportQuery = new WP_Term_Query( array( 'taxonomy' => $exportOptions['taxonomy_to_export'], 'orderby' => 'term_id', 'order' => 'ASC', 'number' => 10, 'hide_empty' => false ));
			remove_filter('terms_clauses', 'wp_all_export_terms_clauses');
		}
		elseif( in_array('comments', $exportOptions['cpt']))
		{
			add_action('comments_clauses', 'wp_all_export_comments_clauses', 10, 1);

			global $wp_version;

			if ( version_compare($wp_version, '4.2.0', '>=') )
			{
				$exportQuery = new WP_Comment_Query( array( 'orderby' => 'comment_ID', 'order' => 'ASC', 'number' => 10 ));
			}
			else
			{
				$exportQuery = get_comments( array( 'orderby' => 'comment_ID', 'order' => 'ASC', 'number' => 10 ));
			}
			remove_action('comments_clauses', 'wp_all_export_comments_clauses');
		} else if(in_array('shop_order', $exportOptions['cpt']) && PMXE_Plugin::hposEnabled()) {
			$exportQuery = new \Wpae\WordPress\OrderQuery();

		} else {

            if(strpos($exportOptions['cpt'][0], 'custom_') === 0) {
                $addon = GF_Export_Add_On::get_instance();

                $filter_args = array(
                    'filter_rules_hierarhy' => empty($exportOptions['filter_rules_hierarhy']) ? array() : $exportOptions['filter_rules_hierarhy'],
                    'product_matching_mode' => empty($exportOptions['product_matching_mode']) ? 'strict' : $exportOptions['product_matching_mode'],
                    'taxonomy_to_export' => empty($exportOptions['taxonomy_to_export']) ? '' : $exportOptions['taxonomy_to_export'],
                    'sub_post_type_to_export' => empty($exportOptions['sub_post_type_to_export']) ? '' : $exportOptions['sub_post_type_to_export']
                );

                $exportQuery = $addon->add_on->get_query(0, 0, $filter_args);
            } else {

                remove_all_actions('parse_query');
                remove_all_actions('pre_get_posts');
                remove_all_filters('posts_clauses');

                add_filter('posts_join', 'wp_all_export_posts_join', 10, 1);
                add_filter('posts_where', 'wp_all_export_posts_where', 10, 1);
                $exportQuery = new WP_Query(array('post_type' => $exportOptions['cpt'], 'post_status' => 'any', 'orderby' => 'title', 'order' => 'ASC', 'posts_per_page' => 10));

                remove_filter('posts_where', 'wp_all_export_posts_where');
                remove_filter('posts_join', 'wp_all_export_posts_join');
            }
		}
	}

	XmlExportEngine::$exportQuery = $exportQuery;

    $engine->init_additional_data();

	?>

	<div id="post-preview" class="wpallexport-preview">
		
		<p class="wpallexport-preview-title"><?php echo sprintf("Preview first 10 %s", esc_html(wp_all_export_get_cpt_name($exportOptions['cpt'], 10, $exportOptions))); ?></p>

		<div class="wpallexport-preview-content">

		<?php

		if(!$custom_xml_valid) {
			$error_msg = '<strong class="error">' . __('Invalid XML', 'wp-all-export') . '</strong><ul  class="error">';
			foreach($custom_xml_template_errors as $error) {
				$error_msg .= '<li>';
				$error_msg .= __('Line', 'wp-all-export') . ' ' . ($error->line + $line_difference) . ', ';
				$error_msg .= __('Column', 'wp-all-export') . ' ' . $error->column . ', ';
				$error_msg .= __('Code', 'wp-all-export') . ' ' . $error->code . ': ';
				$error_msg .= '<em>' . trim(esc_html($error->message)) . '</em>';
				$error_msg .= '</li>';
			}
			$error_msg .= '</ul>';
			echo wp_kses_post($error_msg);
			exit( json_encode(array('html' => ob_get_clean())) );
		}

		switch ($exportOptions['export_to']) {

			case 'xml':

				$dom = new DOMDocument('1.0', $exportOptions['encoding']);
				libxml_use_internal_errors(true);
				try{
					$xml = XmlCsvExport::export_xml(true);
				} catch (WpaeMethodNotFoundException $e) {
					// Find the line where the function is
					$errorMessage = '';
					$functionName = $e->getMessage();
					$txtParts = explode("\n",$originalXmlTemplate);
					for ($i=0, $length = count($txtParts);$i<$length;$i++)
					{
						$tmp = strstr($txtParts[$i], $functionName);
						if ($tmp) {
							$errorMessage .= 'Error parsing XML feed: Call to undefined function <em>"'.$functionName.'"</em> on Line '.($i+1);
						}
					}

					$error_msg = '<span class="error">'.esc_html($errorMessage).'</span>';
					echo wp_kses_post($error_msg);
					exit( json_encode(array('html' => ob_get_clean())) );
				} catch (WpaeInvalidStringException $e) {
					// Find the line where the function is
					$errorMessage = '';
					$functionName = $e->getMessage();
					$txtParts = explode("\n",$originalXmlTemplate);
					for ($i=0, $length = count($txtParts);$i<$length;$i++)
					{
						$tmp = strstr($txtParts[$i], $functionName);
						if ($tmp) {
							$errorMessage .= 'Error parsing XML feed: Unterminated string on line '.($i+1);
						}
					}

					$error_msg = '<span class="error">'.esc_html($errorMessage).'</span>';
					echo wp_kses_post($error_msg);
					exit( json_encode(array('html' => ob_get_clean())) );
				} catch (WpaeTooMuchRecursionException $e) {
					$errorMessage = __( 'There was a problem parsing the custom XML template', 'wp-all-export' );
					$error_msg = '<span class="error">'.esc_html($errorMessage).'</span>';
					echo wp_kses_post($error_msg);
					exit( json_encode(array('html' => ob_get_clean())) );
				}

                $xml_errors = false;

                $main_xml_tag = '';

                switch ( XmlExportEngine::$exportOptions['xml_template_type'] ){

                    case 'custom':
                    case 'XmlGoogleMerchants':

                        require_once PMXE_ROOT_DIR . '/classes/XMLWriter.php';

                        $preview_xml = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>" . "\n<Preview>\n" . $xml . "\n</Preview>";

                        $preview_xml = str_replace('<![CDATA[', 'CDATABEGIN', $preview_xml);
                        $preview_xml = str_replace(']]>', 'CDATACLOSE', $preview_xml);
                        $preview_xml = str_replace('&amp;', '&', $preview_xml);
                        $preview_xml = str_replace('&', '&amp;', $preview_xml);

                        $xml = PMXE_XMLWriter::preprocess_xml( XmlExportEngine::$exportOptions['custom_xml_template_header'] ) . "\n" . $xml . "\n" . PMXE_XMLWriter::preprocess_xml( XmlExportEngine::$exportOptions['custom_xml_template_footer'] );

                        $xml = str_replace('<![CDATA[', 'CDATABEGIN', $xml);
                        $xml = str_replace(']]>', 'CDATACLOSE', $xml);
                        $xml = str_replace('&amp;', '&', $xml);
                        $xml = str_replace('&', '&amp;', $xml);

                        // Determine XML root element
                        preg_match_all("%<[\w]+[\s|>]{1}%", XmlExportEngine::$exportOptions['custom_xml_template_header'], $matches);

                        if ( ! empty($matches[0]) ){
                          $main_xml_tag = preg_replace("%[\s|<|>]%","",array_shift($matches[0]));
                        }

                        libxml_clear_errors();
                        $dom->loadXML($xml);
                        $xml_errors = libxml_get_errors();
                        libxml_clear_errors();
                        if (! $xml_errors ){
                          $xpath = new DOMXPath($dom);
                          if (($elements = @$xpath->query('/' . $main_xml_tag)) and $elements->length){
                            pmxe_render_xml_element($elements->item( 0 ), true);
                          }
                          else{
                            $xml_errors = true;
                          }
                        }

                    break;

                    default:

                        libxml_clear_errors();
                        $dom->loadXML($xml);
                        $xml_errors = libxml_get_errors();
                        libxml_clear_errors();

                        $xpath = new DOMXPath($dom);

                        // Determine XML root element
                        $main_xml_tag = apply_filters('wp_all_export_main_xml_tag', $exportOptions['main_xml_tag'], XmlExportEngine::$exportID);
						$elements = @$xpath->query('/' . $main_xml_tag);
                        if ($elements->length){
                          pmxe_render_xml_element($elements->item( 0 ), true);
                          $xml_errors = false;
                        }
                        else{
                          $error_msg = '<strong>' . __('Can\'t preview the document.', 'wp-all-export') . '</strong><ul>';
                          $error_msg .= '<li>';
                          $error_msg .= __('You can continue export or try to use &lt;data&gt; tag as root element.', 'wp-all-export');
                          $error_msg .= '</li>';
                          $error_msg .= '</ul>';
                          echo wp_kses_post($error_msg);
                          exit( json_encode(array('html' => ob_get_clean())) );
                        }
                    break;

                }

				if ( $xml_errors ){

					$preview_dom = new DOMDocument('1.0', $exportOptions['encoding']);
					libxml_clear_errors();
					$preview_dom->loadXML($preview_xml);
					$preview_xml_errors = libxml_get_errors();
					libxml_clear_errors();

					if ($preview_xml_errors){
						$error_msg = '<strong class="error">' . __('Invalid XML', 'wp-all-export') . '</strong><ul  class="error">';
						foreach($preview_xml_errors as $error) {
							$error_msg .= '<li>';
							$error_msg .= __('Line', 'wp-all-export') . ' ' . $error->line . ', ';
							$error_msg .= __('Column', 'wp-all-export') . ' ' . $error->column . ', ';
							$error_msg .= __('Code', 'wp-all-export') . ' ' . $error->code . ': ';
							$error_msg .= '<em>' . trim(esc_html($error->message)) . '</em>';
							$error_msg .= '</li>';
						}
						$error_msg .= '</ul>';
						echo wp_kses_post($error_msg);
						exit( json_encode(array('html' => ob_get_clean())) );
					}
					else{
						$xpath = new DOMXPath($preview_dom);
						if (($elements = @$xpath->query('/Preview')) and $elements->length){
							pmxe_render_xml_element($elements->item( 0 ), true);
						}
						else{
							$error_msg = '<strong>' . __('Can\'t preview the document. Root element is not detected.', 'wp-all-export') . '</strong><ul>';
							$error_msg .= '<li>';
							$error_msg .= __('You can continue export or try to use &lt;data&gt; tag as root element.', 'wp-all-export');
							$error_msg .= '</li>';
							$error_msg .= '</ul>';
							echo wp_kses_post($error_msg);
							exit( json_encode(array('html' => ob_get_clean())) );
						}
					}
				}

				break;

			case 'csv':
				?>
				<small>
				<?php

					$csv = XmlCsvExport::export_csv( true );

					if (!empty($csv)){
						$csv_rows = array_filter(explode("\n", $csv));
						if ($csv_rows){
							?>
							<table class="pmxe_preview" cellpadding="0" cellspacing="0">
							<?php
							foreach ($csv_rows as $rkey => $row) {
								$cells = str_getcsv($row, $exportOptions['delimiter'], '"', '\\');
								if ($cells){
									?>
									<tr>
										<?php
										foreach ($cells as $key => $value) {
											?>
											<td>
												<?php if (!$rkey):?><strong><?php endif;?>
												<?php echo esc_html($value); ?>
												<?php if (!$rkey):?></strong><?php endif;?>
											</td>
											<?php
										}
										?>
									</tr>
									<?php
								}
							}
							?>
							</table>
							<?php
						}
					}
					else{
						esc_html_e('Data not found.', 'wp-all-export');
					}
				?>
				</small>
				<?php
				break;

			default:

				esc_html_e('This format is not supported.', 'wp-all-export');

				break;
		}
		wp_reset_postdata();
		?>

		</div>

	</div>

	<?php

	exit(json_encode(array('html' => ob_get_clean()))); die;
}
