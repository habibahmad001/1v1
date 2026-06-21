<?php 
/**
 * Manage Imports
 * 
 * @author Pavel Kulbakin <p.kulbakin@gmail.com>
 */
class PMXE_Admin_Manage extends PMXE_Controller_Admin {
	
	public function init() {
		parent::init();
		
		if ('update' == PMXE_Plugin::getInstance()->getAdminCurrentScreen()->action) {
			$this->isInline = true;			
		}
	}
	
	/**
	 * Previous Imports list
	 */
	public function index() {

		$get = $this->input->get(array(
			's' => '',
			'order_by' => 'id',
			'order' => 'DESC',
			'pagenum' => 1,
			'perPage' => 25,
		));
		$get['pagenum'] = absint($get['pagenum']);
		extract($get);
		$this->data += $get;

		if ( ! in_array($order_by, array('registered_on', 'id', 'friendly_name'))){
			$order_by = 'registered_on';
		}

		if ( ! in_array($order, array('DESC', 'ASC'))){
			$order = 'DESC';
		}
		
		$list = new PMXE_Export_List();		
		$by = array('parent_id' => 0);
		if ('' != $s) {
			$like = '%' . preg_replace('%\s+%', '%', preg_replace('/[%?]/', '\\\\$0', $s)) . '%';
			$by[] = array(array('friendly_name LIKE' => $like, 'registered_on LIKE' => $like), 'OR');
		}
		
		$this->data['list'] = $list->setColumns(
				$list->getTable() . '.*'				
			)->getBy($by, "$order_by $order", $pagenum, $perPage, $list->getTable() . '.id');
			
		$this->data['page_links'] = paginate_links(array(
			'base' => esc_url_raw(add_query_arg('pagenum', '%#%', $this->baseUrl)),
			'add_args' => array('page' => 'pmxe-admin-manage'),
			'format' => '',
			'prev_text' => __('&laquo;', 'wp-all-export'),
			'next_text' => __('&raquo;', 'wp-all-export'),
			'total' => ceil($list->total() / $perPage),
			'current' => $pagenum,
		));

		PMXE_Plugin::$session->clean_session();
        
        $this->render();
	}	
	
	/**
	 * Edit Options
	 */
	public function options() {

		// deligate operation to other controller
		$controller = new PMXE_Admin_Export();
		$controller->set('isTemplateEdit', true);
		$controller->options();
	}

	/**
	 * Edit Template
	 */
	public function template() {

		// deligate operation to other controller
		$controller = new PMXE_Admin_Export();
		$controller->set('isTemplateEdit', true);
		$controller->template();
	}	

	/**
	 * Cron Scheduling
	 */
	public function scheduling() {
		$this->data['id'] = $id = $this->input->get('id');
		$this->data['cron_job_key'] = PMXE_Plugin::getInstance()->getOption('cron_job_key');
		$this->data['item'] = $item = new PMXE_Export_Record();
		if ( ! $id or $item->getById($id)->isEmpty()) {
			wp_safe_redirect($this->baseUrl); die();
		}

		$wp_uploads = wp_upload_dir();	

		$this->data['file_path'] = site_url() . '/wp-load.php?security_token=' . substr(md5($this->data['cron_job_key'] . $item['id']), 0, 16) . '&export_id=' . $item['id'] . '&action=get_data';

		$this->data['bundle_url'] = ''; 

		if ( ! empty($item['options']['bundlepath']) )
		{			
			$this->data['bundle_url'] = site_url() . '/wp-load.php?security_token=' . substr(md5($this->data['cron_job_key'] . $item['id']), 0, 16) . '&export_id=' . $item['id'] . '&action=get_bundle&t=zip';
		}		

		$this->render();
	}

	/**
	 * Google merchants info
	 */
	public function google_merchants_info() {

		$this->data['id'] = $id = $this->input->get('id');
		$this->data['cron_job_key'] = PMXE_Plugin::getInstance()->getOption('cron_job_key');
		$this->data['item'] = $item = new PMXE_Export_Record();
		if ( ! $id or $item->getById($id)->isEmpty()) {
			wp_safe_redirect($this->baseUrl); die();
		}
		
		$this->data['file_path'] = site_url() . '/wp-load.php?security_token=' . substr(md5($this->data['cron_job_key'] . $item['id']), 0, 16) . '&export_id=' . $item['id'] . '&action=get_data';

		$this->render();
	}

	/**
	 * Download import templates
	 */
	public function templates() {
		$this->data['id'] = $id = $this->input->get('id');		
		$this->data['item'] = $item = new PMXE_Export_Record();
		if ( ! $id or $item->getById($id)->isEmpty()) {
			wp_safe_redirect($this->baseUrl); die();
		}

		$this->render();
	}

	/**
	 * Cancel import processing
	 */
	public function cancel(){

		check_admin_referer('cancel-export', '_wpnonce_cancel-export');

		$id = $this->input->get('id');

		PMXE_Plugin::$session->clean_session( $id );

		$item = new PMXE_Export_Record();
		if ( ! $id or $item->getById($id)->isEmpty()) {
			wp_safe_redirect($this->baseUrl); die();
		}
		$item->set(array(
			'triggered'   => 0,
			'processing'  => 0,
			'executing'   => 0,
			'canceled'    => 1,
			'canceled_on' => date('Y-m-d H:i:s')  // phpcs:ignore WordPress.DateTime.RestrictedFunctions.date_date -- DB timestamp must match local-timezone format used by Manage Exports UI readers (mysql2date / strtotime / human_time_diff)
		))->update();		

		wp_safe_redirect(esc_url_raw(add_query_arg('pmxe_nt', urlencode(__('Export canceled', 'wp-all-export')), $this->baseUrl)));
		die();
	}
	
	/**
	 * Reexport
	 */
	public function update() {
			
		$id = $this->input->get('id');
		
		PMXE_Plugin::$session->clean_session($id);			

		$action_type = $this->input->get('type');

		$this->data['item'] = $item = new PMXE_Export_Record();
		if ( ! $id or $item->getById($id)->isEmpty()) {
			wp_safe_redirect($this->baseUrl); die();
		}			

		$item->fix_template_options();				

		$default = PMXE_Plugin::get_default_import_options();
		$DefaultOptions = $item->options + $default;
		if (empty($item->options['export_variations'])){
			$DefaultOptions['export_variations'] = XmlExportEngine::VARIABLE_PRODUCTS_EXPORT_PARENT_AND_VARIATION;
		}
		if (empty($item->options['export_variations_title'])){
			$DefaultOptions['export_variations_title'] = XmlExportEngine::VARIATION_USE_DEFAULT_TITLE;
		}
		$this->data['post'] = $post = $this->input->post($DefaultOptions);	
		$this->data['iteration'] = $item->iteration;

		if ($this->input->post('is_confirmed')) {

			check_admin_referer('update-export', '_wpnonce_update-export');	
			
			$iteration = ( empty($item->options['creata_a_new_export_file']) && ! empty($post['creata_a_new_export_file'])) ? 0 : $item->iteration;			

			$item->set(array( 'options' => $post, 'iteration' => $iteration))->save();
			if ( ! empty($post['friendly_name']) ) {
				$item->set( array( 'friendly_name' => $post['friendly_name'], 'scheduled' => (($post['is_scheduled']) ? $post['scheduled_period'] : '') ) )->save();	
			}			

			// compose data to look like result of wizard steps
			$sesson_data = $post + array('update_previous' => $item->id ) + $default;
			
			foreach ($sesson_data as $key => $value) {
				PMXE_Plugin::$session->set($key, $value);
			}

			$this->data['engine'] = new XmlExportEngine($sesson_data, $this->errors);	
			$this->data['engine']->init_additional_data();
			$this->data['engine']->init_available_data();	

			PMXE_Plugin::$session->save_data();			

			if ( ! $this->errors->get_error_codes() && $this->input->post('record-count')) {

				// deligate operation to other controller
				$controller = new PMXE_Admin_Export();
				$controller->data['update_previous'] = $item;
				$controller->process();
				return;
				
			}

			$this->errors->remove('count-validation');
			if ( ! $this->errors->get_error_codes()) {												
				wp_safe_redirect(esc_url_raw(add_query_arg('pmxe_nt', urlencode(__('Options updated', 'wp-all-export')), $this->baseUrl)));
				die();
			}

		}

		$this->data['isWizard'] = false;		
		$this->data['engine'] = new XmlExportEngine($post, $this->errors);	
		$this->data['engine']->init_available_data();	
		
		$this->render();
	}
	
	/**
	 * Delete an export
	 */
	public function delete() {
		$id = $this->input->get('id');
		$this->data['item'] = $item = new PMXE_Export_Record();
		if ( ! $id or $item->getById($id)->isEmpty()) {
			wp_safe_redirect($this->baseUrl); die();
		}

		if ($this->input->post('is_confirmed')) {
			check_admin_referer('delete-export', '_wpnonce_delete-export');
			$item->delete();

			$scheduling = \Wpae\Scheduling\Scheduling::create();
			$scheduling->deleteScheduleIfExists($id);

			wp_safe_redirect(esc_url_raw(add_query_arg('pmxe_nt', urlencode(__('Export deleted', 'wp-all-export')), $this->baseUrl))); die();
		}

		$this->render();
	}
	
	/**
	 * Bulk actions
	 */
	public function bulk() {
		check_admin_referer('bulk-exports', '_wpnonce_bulk-exports');
		if ($this->input->post('doaction2')) {
			$this->data['action'] = $action = $this->input->post('bulk-action2');
		} else {
			$this->data['action'] = $action = $this->input->post('bulk-action');
		}		
		$this->data['ids'] = $ids = $this->input->post('items');
		$this->data['items'] = $items = new PMXE_Export_List();
		if (empty($action) or ! in_array($action, array('delete')) or empty($ids) or $items->getBy('id', $ids)->isEmpty()) {
			wp_safe_redirect($this->baseUrl); die();
		}		
		if ($this->input->post('is_confirmed')) {			
			foreach($items->convertRecords() as $item) {
				
				if ($item->attch_id) wp_delete_attachment($item->attch_id, true);

				$item->delete();

                $scheduling = \Wpae\Scheduling\Scheduling::create();
                $scheduling->deleteScheduleIfExists($item->id);
			}			
			/* translators: 1: count, 2: singular/plural noun */
			wp_safe_redirect(esc_url_raw(add_query_arg('pmxe_nt', urlencode(sprintf(__('%1$d %2$s deleted', 'wp-all-export'), $items->count(), _n('export', 'exports', $items->count(), 'wp-all-export'))), $this->baseUrl)));
			die();
		}		
		$this->render();
	}

	public function get_template(){
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- nonce verified on next line
		$nonce = (!empty($_REQUEST['_wpnonce'])) ? sanitize_text_field( wp_unslash( $_REQUEST['_wpnonce'] ) ) : '';
		if ( ! wp_verify_nonce( $nonce, '_wpnonce-download_template' ) ) {	    
		    die( esc_html__('Security check', 'wp-all-export') ); 
		} else {	
			
			$id = $this->input->get('id');

			$export = new PMXE_Export_Record();

			$filepath = '';

			$export_data = array();
			
			if ( ! $export->getById($id)->isEmpty()){
				
				$export_data[] = $export->options['tpl_data'];
				$uploads   = wp_upload_dir();
				$targetDir = $uploads['basedir'] . DIRECTORY_SEPARATOR . PMXE_Plugin::TEMP_DIRECTORY;
				
				$export_file_name = "WP All Import Template - " . sanitize_file_name($export->friendly_name) . ".txt";

				file_put_contents($targetDir . DIRECTORY_SEPARATOR . $export_file_name, json_encode($export_data));						
				
				PMXE_download::csv($targetDir . DIRECTORY_SEPARATOR . $export_file_name);		

			}
		}
	}

	/*
	 * Download bundle for WP All Import
	 *
	 */
	public function bundle()
	{
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- nonce verified on next line
		$nonce = (!empty($_REQUEST['_wpnonce'])) ? sanitize_text_field( wp_unslash( $_REQUEST['_wpnonce'] ) ) : '';
		if ( ! wp_verify_nonce( $nonce, '_wpnonce-download_bundle' ) ) {
		    die( esc_html__('Security check', 'wp-all-export') ); 
		} else {

			$uploads  = wp_upload_dir();

			$id = $this->input->get('id');

			$export = new PMXE_Export_Record();		

			if ( ! $export->getById($id)->isEmpty())
			{				
				if ( ! empty($export->options['bundlepath']) )
				{
					$bundle_path = wp_all_export_get_absolute_path($export->options['bundlepath']);

					if ( @file_exists($bundle_path) )
					{						
						$bundle_url = $uploads['baseurl'] . str_replace($uploads['basedir'], '', $bundle_path);

						PMXE_download::zip($bundle_path);	
					}					
				}
				else
				{
					wp_safe_redirect(esc_url_raw(add_query_arg('pmxe_nt', urlencode(__('The exported bundle is missing and can\'t be downloaded. Please re-run your export to re-generate it.', 'wp-all-export')), $this->baseUrl)));
					die();
				}
			}
			else
			{
				wp_safe_redirect(esc_url_raw(add_query_arg('pmxe_nt', urlencode(__('This export doesn\'t exist.', 'wp-all-export')), $this->baseUrl))); die();
			}			
		}
	}	

	public function split_bundle(){
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- nonce verified on next line
		$nonce = (!empty($_REQUEST['_wpnonce'])) ? sanitize_text_field( wp_unslash( $_REQUEST['_wpnonce'] ) ) : '';
		if ( ! wp_verify_nonce( $nonce, '_wpnonce-download_split_bundle' ) ) {		    
		    die( esc_html__('Security check', 'wp-all-export') ); 
		} else {

			$uploads  = wp_upload_dir();
			
			$id = PMXE_Plugin::$session->update_previous;

			if (empty($id))
				$id = $this->input->get('id');

			$export = new PMXE_Export_Record();					
		
			if ( ! $export->getById($id)->isEmpty())
			{	
				if ( ! empty($export->options['split_files_list']))
				{					
					$tmp_dir    = $uploads['basedir'] . DIRECTORY_SEPARATOR . PMXE_Plugin::TEMP_DIRECTORY . DIRECTORY_SEPARATOR . md5($export->id) . DIRECTORY_SEPARATOR;
					$bundle_dir = $tmp_dir . 'split_files' . DIRECTORY_SEPARATOR;

					wp_all_export_rrmdir($tmp_dir);

					// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_mkdir -- export plugin reads/writes its own files in uploads dir, WP_Filesystem not viable for streamed CSV/XML output
					@mkdir($tmp_dir);
					// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_mkdir -- export plugin reads/writes its own files in uploads dir, WP_Filesystem not viable for streamed CSV/XML output
					@mkdir($bundle_dir);

					foreach ($export->options['split_files_list'] as $file) {
						@copy( $file, $bundle_dir . basename($file) );							
					}

					$friendly_name = sanitize_file_name($export->friendly_name);

					$bundle_path = $tmp_dir . $friendly_name . '-split-files.zip';

					PMXE_Zip::zipDir($bundle_dir, $bundle_path);

					if (file_exists($bundle_path))
					{
						$bundle_url = $uploads['baseurl'] . str_replace($uploads['basedir'], '', $bundle_path);

						PMXE_download::zip($bundle_path);						
					}	
				}						
			}
		}
	}

	/*
	 * Download import log file
	 *
	 */
	public function get_file(){

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- nonce verified on next line
		$nonce = (!empty($_REQUEST['_wpnonce'])) ? sanitize_text_field( wp_unslash( $_REQUEST['_wpnonce'] ) ) : '';
		if ( ! wp_verify_nonce( $nonce, '_wpnonce-download_feed' ) ) {		    
		    die( esc_html__('Security check', 'wp-all-export') ); 
		} else {

			$is_secure_import = PMXE_Plugin::getInstance()->getOption('secure');

			$id = $this->input->get('id');

			$export = new PMXE_Export_Record();

			$filepath = '';

			if ( ! $export->getById($id)->isEmpty())
			{
				if ( ! $is_secure_import)
				{
					$filepath = get_attached_file($export->attch_id);					
				}
				else
				{
					$filepath = wp_all_export_get_absolute_path($export->options['filepath']);
				}				

				if ( @file_exists($filepath) )
				{					
					switch ($export->options['export_to']) 
					{
						case 'xml':
							if($export['options']['xml_template_type'] == XmlExportEngine::EXPORT_TYPE_GOOLE_MERCHANTS) {
								PMXE_Download::txt($filepath);
							} else {
								PMXE_download::xml($filepath);
							}

							break;
						case 'csv':							
							if (empty($export->options['export_to_sheet']) or $export->options['export_to_sheet'] == 'csv')
							{
								PMXE_download::csv($filepath);		
							}							
							else 
							{													
								PMXE_download::xls($filepath);		
							}
							break;
						default:
							wp_safe_redirect(esc_url_raw(add_query_arg('pmxe_nt', urlencode(__('File format not supported', 'wp-all-export')), $this->baseUrl)));
							die();
							break;
					}
				}	
				else
				{
					wp_safe_redirect(esc_url_raw(add_query_arg('pmxe_nt', urlencode(__('The exported file is missing and can\'t be downloaded. Please re-run your export to re-generate it.', 'wp-all-export')), $this->baseUrl)));
					die();
				}
			}
			else 
			{
				wp_safe_redirect(esc_url_raw(add_query_arg('pmxe_nt', urlencode(__('The exported file is missing and can\'t be downloaded. Please re-run your export to re-generate it.', 'wp-all-export')), $this->baseUrl)));
				die();
			}		
		}
	}

    /**
     * @param $post
     * @return string
     */
    protected function getFriendlyName($post)
    {
        $friendly_name = '';
        $post_types = PMXE_Plugin::$session->get('cpt');
        if (!empty($post_types)) {
            if (in_array('users', $post_types)) {
                $friendly_name = 'Users Export - ' . wp_date("Y F d H:i");
                return $friendly_name;
            } elseif (in_array('shop_customer', $post_types)) {
                $friendly_name = 'Customers Export - ' . wp_date("Y F d H:i");
                return $friendly_name;
            } elseif (in_array('comments', $post_types)) {
                $friendly_name = 'Comments Export - ' . wp_date("Y F d H:i");
                return $friendly_name;
            } elseif (in_array('taxonomies', $post_types)) {
                $tx = get_taxonomy($post['taxonomy_to_export']);
                if (!empty($tx->labels->name)) {
                    $friendly_name = $tx->labels->name . ' Export - ' . wp_date("Y F d H:i");
                    return $friendly_name;
                } else {
                    $friendly_name = 'Taxonomy Terms Export - ' . wp_date("Y F d H:i");
                    return $friendly_name;
                }
            } else {
                $post_type_details = get_post_type_object(array_shift($post_types));
                $friendly_name = $post_type_details->labels->name . ' Export - ' . wp_date("Y F d H:i");
                return $friendly_name;
            }
        } else {
            $friendly_name = 'WP_Query Export - ' . wp_date("Y F d H:i");
            return $friendly_name;
        }
    }

}