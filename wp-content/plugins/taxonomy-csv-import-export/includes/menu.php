<?php
namespace TaxoCSIE;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use TaxoCSIE\Helper;

class Menu {

	public static function init() {
		$self = new self();
		add_action( 'admin_menu', array( $self, 'add_taxocsie_menu_page' ) );
		add_filter( 'admin_footer_text', '__return_empty_string', 11 );
	}

	public function add_taxocsie_menu_page() {
		$icon_url   = $this->tcie_encode_svg_to_base64( TAXOCSIE_ASSETS_DIR_PATH . '/images/menu-button.svg' );
		$page_title = __( 'Import Export', 'taxonomy-csv-import-export' );
		add_menu_page( $page_title, $page_title, 'manage_options', TAXOCSIE_PLUGIN_SLUG, [ $this, 'get_taxocsie_export_function' ], $icon_url, 41 );
		foreach ( Helper::taxoscie_main_menu_title() as $item_key => $item ) {
			add_submenu_page( $item['parent_slug'], $item['title'], $item['title'], $item['capability'], $item_key, [ $this, $item['callback'] ] );
		}
	}

	/* -------------------------------------------------------
	   Shared: page header
	------------------------------------------------------- */
	private function page_header( string $title, string $desc = '' ): void {
		?>
		<div class="taxocsie-page-header">
			<h1 class="taxocsie-page-header__title"><?php echo esc_html( $title ); ?></h1>
			<?php if ( $desc ) : ?>
				<p class="taxocsie-page-header__desc"><?php echo esc_html( $desc ); ?></p>
			<?php endif; ?>
		</div>
		<?php
	}

	/* -------------------------------------------------------
	   Taxonomy — Import / Export
	------------------------------------------------------- */
	public function get_taxocsie_export_function() {
		$taxonomies   = get_taxonomies( array( 'public' => true ), 'objects' );
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$default_mode = isset( $_GET['imported'] ) ? 'import' : 'export';
		?>
		<div class="taxocsie-wrap">

			<?php $this->page_header(
				__( 'Taxonomy Import / Export', 'taxonomy-csv-import-export' ),
				__( 'Export taxonomy terms to a CSV file, or import terms from a CSV.', 'taxonomy-csv-import-export' )
			); ?>

			<div class="taxocsie-tabs">
				<button class="taxocsie-tab<?php echo $default_mode === 'export' ? ' is-active' : ''; ?>" data-panel="taxocsie-export-panel">
					<?php esc_html_e( 'Export CSV', 'taxonomy-csv-import-export' ); ?>
				</button>
				<button class="taxocsie-tab<?php echo $default_mode === 'import' ? ' is-active' : ''; ?>" data-panel="taxocsie-import-panel">
					<?php esc_html_e( 'Import CSV', 'taxonomy-csv-import-export' ); ?>
				</button>
			</div>

			<!-- Export Panel -->
			<div id="taxocsie-export-panel" class="taxocsie-panel"<?php echo $default_mode !== 'export' ? ' style="display:none;"' : ''; ?>>
				<h2 class="taxocsie-panel__title">
					<?php esc_html_e( 'Export Taxonomy', 'taxonomy-csv-import-export' ); ?>
				</h2>

				<form class="taxocsie-panel__form" method="post">
					<?php wp_nonce_field( 'taxonomy_csv_export', 'taxonomy_csv_export_nonce' ); ?>
					<div class="taxocsie-panel__row">
						<select class="taxocsie-panel__select" id="export-taxonomy" name="taxocsie_type" required>
							<option value=""><?php esc_html_e( 'Select Taxonomy', 'taxonomy-csv-import-export' ); ?></option>
							<?php foreach ( $taxonomies as $taxonomy ) : ?>
								<option value="<?php echo esc_attr( $taxonomy->name ); ?>">
									<?php echo esc_html( $taxonomy->label . ' (' . $taxonomy->name . ')' ); ?>
								</option>
							<?php endforeach; ?>
						</select>
						<div class="taxocsie-panel__actions">
							<button type="button" id="export-preview-btn" class="button taxocsie-panel__btn-preview">
								<?php esc_html_e( 'Preview', 'taxonomy-csv-import-export' ); ?>
							</button>
							<button type="submit" name="taxonomy_export_csv" class="button button-primary taxocsie-panel__btn-submit">
								<?php esc_html_e( 'Export CSV', 'taxonomy-csv-import-export' ); ?>
							</button>
						</div>
					</div>
				</form>

				<div id="export-preview-container" class="taxocsie-panel__preview" style="display:none;"></div>
			</div>

			<!-- Import Panel -->
			<div id="taxocsie-import-panel" class="taxocsie-panel"<?php echo $default_mode !== 'import' ? ' style="display:none;"' : ''; ?>>
				<h2 class="taxocsie-panel__title">
					<?php esc_html_e( 'Import Taxonomy', 'taxonomy-csv-import-export' ); ?>
				</h2>

				<form class="taxocsie-panel__form" method="post" enctype="multipart/form-data">
					<?php wp_nonce_field( 'taxonomy_csv_import', 'taxonomy_csv_import_nonce' ); ?>
					<div class="taxocsie-panel__row">
						<select class="taxocsie-panel__select" id="taxonomy" name="taxocsie_type" required>
							<option value=""><?php esc_html_e( 'Select Taxonomy', 'taxonomy-csv-import-export' ); ?></option>
							<?php foreach ( $taxonomies as $taxonomy ) : ?>
								<option value="<?php echo esc_attr( $taxonomy->name ); ?>">
									<?php echo esc_html( $taxonomy->label . ' (' . $taxonomy->name . ')' ); ?>
								</option>
							<?php endforeach; ?>
						</select>
						<input class="taxocsie-panel__file" type="file" name="csv_file" id="csv_file" accept=".csv" required>
						<div class="taxocsie-panel__actions">
							<button type="submit" name="taxonomy_import_csv" class="button button-primary taxocsie-panel__btn-submit">
								<?php esc_html_e( 'Import CSV', 'taxonomy-csv-import-export' ); ?>
							</button>
						</div>
					</div>
				</form>

				<?php // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				if ( isset( $_GET['imported'] ) ) :
					$csv_data = get_transient( 'taxoscie_csv_data' );
					if ( ! empty( $csv_data ) && is_array( $csv_data ) ) : ?>
						<div class="notice notice-success taxocsie-panel__notice">
							<p><?php esc_html_e( 'Import completed successfully.', 'taxonomy-csv-import-export' ); ?></p>
						</div>
						<div class="taxocsie-panel__tablewrap">
							<table class="widefat striped taxocsie-panel__table">
								<thead>
									<tr>
										<th><?php esc_html_e( 'Title', 'taxonomy-csv-import-export' ); ?></th>
										<th><?php esc_html_e( 'Slug', 'taxonomy-csv-import-export' ); ?></th>
										<th><?php esc_html_e( 'Description', 'taxonomy-csv-import-export' ); ?></th>
										<th><?php esc_html_e( 'Parent Slug', 'taxonomy-csv-import-export' ); ?></th>
									</tr>
								</thead>
								<tbody>
									<?php foreach ( $csv_data as $row ) : ?>
										<tr>
											<?php foreach ( $row as $cell ) : ?>
												<td><?php echo esc_html( html_entity_decode( $cell ) ); ?></td>
											<?php endforeach; ?>
										</tr>
									<?php endforeach; ?>
								</tbody>
							</table>
						</div>
					<?php endif; ?>
				<?php endif; ?>
			</div>

		</div>
		<?php
		$this->taxocsie_custom_admin_footer();
	}

	/* -------------------------------------------------------
	   Create Term
	------------------------------------------------------- */
	public function get_taxocsie_create_term_function() {
		$taxonomies = get_taxonomies( array( 'public' => true ), 'objects' );
		?>
		<div class="taxocsie-wrap">

			<?php $this->page_header(
				__( 'Create Term', 'taxonomy-csv-import-export' ),
				__( 'Select a taxonomy then add a new term with a title and optional description.', 'taxonomy-csv-import-export' )
			); ?>

			<div class="taxocsie-panel">
				<h2 class="taxocsie-panel__title">
					<?php esc_html_e( 'Choose a Taxonomy', 'taxonomy-csv-import-export' ); ?>
				</h2>

				<div class="taxocsie-taxonomy-list">
					<?php foreach ( $taxonomies as $taxonomy ) : ?>
						<div class="taxocsie-taxonomy-row" data-taxonomy="<?php echo esc_attr( $taxonomy->name ); ?>">

							<button type="button" class="taxocsie-taxonomy-row__header">
								<span class="taxocsie-taxonomy-row__label"><?php echo esc_html( $taxonomy->label ); ?></span>
								<span class="taxocsie-taxonomy-row__slug"><?php echo esc_html( $taxonomy->name ); ?></span>
								<span class="taxocsie-taxonomy-row__arrow">&#8964;</span>
							</button>

							<div class="taxocsie-taxonomy-row__body" style="display:none;">
								<div class="taxocsie-taxonomy-row__fields">

									<div class="taxocsie-panel__field">
										<label class="taxocsie-panel__field-label">
											<?php esc_html_e( 'Title', 'taxonomy-csv-import-export' ); ?>
											<span class="taxocsie-required">*</span>
										</label>
										<input
											class="taxocsie-panel__input taxocsie-row-title"
											type="text"
											placeholder="<?php esc_attr_e( 'Enter term title…', 'taxonomy-csv-import-export' ); ?>"
										>
									</div>

									<div class="taxocsie-panel__field">
										<label class="taxocsie-panel__field-label">
											<?php esc_html_e( 'Description', 'taxonomy-csv-import-export' ); ?>
											<span class="taxocsie-optional"><?php esc_html_e( '(optional)', 'taxonomy-csv-import-export' ); ?></span>
										</label>
										<textarea
											class="taxocsie-panel__textarea taxocsie-row-desc"
											rows="2"
											placeholder="<?php esc_attr_e( 'Enter description…', 'taxonomy-csv-import-export' ); ?>"
										></textarea>
									</div>

								</div>

								<div class="taxocsie-taxonomy-row__footer">
									<button type="button" class="button button-primary taxocsie-panel__btn-submit taxocsie-row-create-btn">
										<?php esc_html_e( 'Create Term', 'taxonomy-csv-import-export' ); ?>
									</button>
									<div class="taxocsie-row-feedback" style="display:none;"></div>
								</div>
							</div>

						</div>
					<?php endforeach; ?>
				</div>
			</div>

		</div>
		<?php
		$this->taxocsie_custom_admin_footer();
	}

	/* -------------------------------------------------------
	   Register Taxonomy
	------------------------------------------------------- */
	public function get_taxocsie_register_taxonomy_function() {
		$post_types       = get_post_types( array( 'public' => true ), 'objects' );
		$saved_taxonomies = TaxonomyCreator::get_saved();
		?>
		<div class="taxocsie-wrap">

			<?php $this->page_header(
				__( 'Register Taxonomy', 'taxonomy-csv-import-export' ),
				__( 'Create a new custom taxonomy and attach it to one or more post types.', 'taxonomy-csv-import-export' )
			); ?>

			<div class="taxocsie-panel">
				<h2 class="taxocsie-panel__title">
					<?php esc_html_e( 'New Taxonomy', 'taxonomy-csv-import-export' ); ?>
				</h2>

				<div class="taxocsie-panel__form" id="taxocsie-reg-form">

					<div class="taxocsie-reg-grid">
						<div class="taxocsie-panel__field">
							<label class="taxocsie-panel__field-label" for="reg-label">
								<?php esc_html_e( 'Label (Plural)', 'taxonomy-csv-import-export' ); ?>
								<span class="taxocsie-required">*</span>
							</label>
							<input class="taxocsie-panel__input" type="text" id="reg-label" placeholder="<?php esc_attr_e( 'e.g. Genres', 'taxonomy-csv-import-export' ); ?>">
						</div>

						<div class="taxocsie-panel__field">
							<label class="taxocsie-panel__field-label" for="reg-singular">
								<?php esc_html_e( 'Singular Label', 'taxonomy-csv-import-export' ); ?>
								<span class="taxocsie-optional"><?php esc_html_e( '(optional)', 'taxonomy-csv-import-export' ); ?></span>
							</label>
							<input class="taxocsie-panel__input" type="text" id="reg-singular" placeholder="<?php esc_attr_e( 'e.g. Genre', 'taxonomy-csv-import-export' ); ?>">
						</div>

						<div class="taxocsie-panel__field">
							<label class="taxocsie-panel__field-label" for="reg-slug">
								<?php esc_html_e( 'Slug', 'taxonomy-csv-import-export' ); ?>
								<span class="taxocsie-required">*</span>
							</label>
							<input class="taxocsie-panel__input" type="text" id="reg-slug" placeholder="<?php esc_attr_e( 'e.g. genre', 'taxonomy-csv-import-export' ); ?>">
							<span class="taxocsie-field-hint"><?php esc_html_e( 'Auto-generated. Lowercase, no spaces.', 'taxonomy-csv-import-export' ); ?></span>
						</div>
					</div>

					<div class="taxocsie-panel__field">
						<label class="taxocsie-panel__field-label" for="reg-description">
							<?php esc_html_e( 'Description', 'taxonomy-csv-import-export' ); ?>
							<span class="taxocsie-optional"><?php esc_html_e( '(optional)', 'taxonomy-csv-import-export' ); ?></span>
						</label>
						<textarea class="taxocsie-panel__textarea" id="reg-description" rows="3" placeholder="<?php esc_attr_e( 'Describe what this taxonomy is used for…', 'taxonomy-csv-import-export' ); ?>"></textarea>
					</div>

					<div class="taxocsie-panel__field">
						<p class="taxocsie-panel__field-label">
							<?php esc_html_e( 'Taxonomy Type', 'taxonomy-csv-import-export' ); ?>
							<span class="taxocsie-required">*</span>
						</p>
						<div class="taxocsie-type-options">
							<label class="taxocsie-type-option taxocsie-type-option--active">
								<input type="radio" name="reg-type" value="0" checked>
								<span class="taxocsie-type-option__title"><?php esc_html_e( 'Tags', 'taxonomy-csv-import-export' ); ?></span>
								<span class="taxocsie-type-option__desc"><?php esc_html_e( 'Non-hierarchical. Flat list of terms.', 'taxonomy-csv-import-export' ); ?></span>
							</label>
							<label class="taxocsie-type-option">
								<input type="radio" name="reg-type" value="1">
								<span class="taxocsie-type-option__title"><?php esc_html_e( 'Categories', 'taxonomy-csv-import-export' ); ?></span>
								<span class="taxocsie-type-option__desc"><?php esc_html_e( 'Hierarchical. Terms can have parent terms.', 'taxonomy-csv-import-export' ); ?></span>
							</label>
						</div>
					</div>

					<div class="taxocsie-panel__field">
						<p class="taxocsie-panel__field-label">
							<?php esc_html_e( 'Attach to Post Types', 'taxonomy-csv-import-export' ); ?>
							<span class="taxocsie-required">*</span>
						</p>
						<div class="taxocsie-post-types-grid">
							<?php foreach ( $post_types as $pt ) : ?>
								<label class="taxocsie-checkbox-label">
									<input type="checkbox" class="reg-post-type" value="<?php echo esc_attr( $pt->name ); ?>">
									<span><?php echo esc_html( $pt->label . ' (' . $pt->name . ')' ); ?></span>
								</label>
							<?php endforeach; ?>
						</div>
					</div>

					<div class="taxocsie-panel__actions">
						<button type="button" id="taxocsie-reg-submit" class="button button-primary taxocsie-panel__btn-submit">
							<?php esc_html_e( 'Register Taxonomy', 'taxonomy-csv-import-export' ); ?>
						</button>
						<div id="taxocsie-reg-feedback" class="taxocsie-reg-feedback" style="display:none;"></div>
					</div>

				</div>
			</div>

			<div class="taxocsie-panel taxocsie-panel--mt" id="taxocsie-reg-panel"<?php echo empty( $saved_taxonomies ) ? ' style="display:none;"' : ''; ?>>
				<h2 class="taxocsie-panel__title">
					<?php esc_html_e( 'Registered Custom Taxonomies', 'taxonomy-csv-import-export' ); ?>
				</h2>
				<div class="taxocsie-panel__tablewrap taxocsie-panel__tablewrap--flush">
					<table class="widefat striped taxocsie-panel__table" id="taxocsie-reg-table">
						<thead>
							<tr>
								<th><?php esc_html_e( 'Label', 'taxonomy-csv-import-export' ); ?></th>
								<th><?php esc_html_e( 'Slug', 'taxonomy-csv-import-export' ); ?></th>
								<th><?php esc_html_e( 'Type', 'taxonomy-csv-import-export' ); ?></th>
								<th><?php esc_html_e( 'Post Types', 'taxonomy-csv-import-export' ); ?></th>
								<th><?php esc_html_e( 'Actions', 'taxonomy-csv-import-export' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ( $saved_taxonomies as $slug => $tax ) : ?>
								<tr data-slug="<?php echo esc_attr( $slug ); ?>">
									<td><strong><?php echo esc_html( $tax['label'] ); ?></strong></td>
									<td><code><?php echo esc_html( $slug ); ?></code></td>
									<td><?php echo ! empty( $tax['hierarchical'] ) ? esc_html__( 'Categories', 'taxonomy-csv-import-export' ) : esc_html__( 'Tags', 'taxonomy-csv-import-export' ); ?></td>
									<td><?php echo esc_html( implode( ', ', $tax['post_types'] ) ); ?></td>
									<td>
										<button type="button" class="button taxocsie-delete-taxonomy" data-slug="<?php echo esc_attr( $slug ); ?>">
											<?php esc_html_e( 'Delete', 'taxonomy-csv-import-export' ); ?>
										</button>
									</td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				</div>
			</div>

		</div>
		<?php
		$this->taxocsie_custom_admin_footer();
	}

	/* -------------------------------------------------------
	   Users — Import / Export
	------------------------------------------------------- */
	public function taxocsie_users_export_import_function() {
		global $wp_roles;
		$roles = $wp_roles->get_names();
		?>
		<div class="taxocsie-wrap">

			<?php $this->page_header(
				__( 'User Import / Export', 'taxonomy-csv-import-export' ),
				__( 'Export users to a CSV file filtered by role, or import users from a CSV.', 'taxonomy-csv-import-export' )
			); ?>

			<div class="taxocsie-two-col">

				<!-- Export -->
				<div class="taxocsie-panel">
					<h2 class="taxocsie-panel__title">
						<?php esc_html_e( 'Export Users', 'taxonomy-csv-import-export' ); ?>
					</h2>
					<form class="taxocsie-panel__form" method="get" action="">

						<div class="taxocsie-panel__field">
							<p class="taxocsie-panel__field-label">
								<?php esc_html_e( 'Filter by Role', 'taxonomy-csv-import-export' ); ?>
								<span class="taxocsie-optional"><?php esc_html_e( '(optional)', 'taxonomy-csv-import-export' ); ?></span>
							</p>
							<div class="taxocsie-roles-grid">
								<?php foreach ( $roles as $key => $label ) : ?>
									<label class="taxocsie-checkbox-label">
										<input type="checkbox" name="roles[]" value="<?php echo esc_attr( $key ); ?>">
										<span><?php echo esc_html( $label ); ?></span>
									</label>
								<?php endforeach; ?>
							</div>
							<span class="taxocsie-field-hint"><?php esc_html_e( 'Leave all unchecked to export every user.', 'taxonomy-csv-import-export' ); ?></span>
						</div>

						<div class="taxocsie-panel__actions">
							<button type="submit" name="taxocsie_export_users" class="button button-primary taxocsie-panel__btn-submit">
								<?php esc_html_e( 'Export CSV', 'taxonomy-csv-import-export' ); ?>
							</button>
						</div>

					</form>
				</div>

				<!-- Import -->
				<div class="taxocsie-panel">
					<h2 class="taxocsie-panel__title">
						<?php esc_html_e( 'Import Users', 'taxonomy-csv-import-export' ); ?>
					</h2>

					<?php // phpcs:ignore WordPress.Security.NonceVerification.Recommended
					if ( isset( $_GET['user-imported'] ) && $_GET['user-imported'] === 'success' ) : ?>
						<div class="notice notice-success taxocsie-panel__notice">
							<p><?php esc_html_e( 'Users imported successfully!', 'taxonomy-csv-import-export' ); ?></p>
						</div>
					<?php endif; ?>

					<form class="taxocsie-panel__form" method="post" enctype="multipart/form-data" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
						<?php wp_nonce_field( 'taxocsie_import_users_nonce', 'taxocsie_nonce' ); ?>

						<div class="taxocsie-panel__field">
							<label class="taxocsie-panel__field-label" for="taxocsie_import_file">
								<?php esc_html_e( 'CSV File', 'taxonomy-csv-import-export' ); ?>
								<span class="taxocsie-required">*</span>
							</label>
							<input class="taxocsie-panel__file taxocsie-panel__file--block" type="file" id="taxocsie_import_file" name="taxocsie_import_file" accept=".csv" required>
							<span class="taxocsie-field-hint"><?php esc_html_e( 'Columns: username, email, password_hash, roles, first_name, last_name, meta', 'taxonomy-csv-import-export' ); ?></span>
						</div>

						<div class="taxocsie-panel__actions">
							<button type="submit" name="taxocsie_import_users" class="button button-primary taxocsie-panel__btn-submit">
								<?php esc_html_e( 'Import Users', 'taxonomy-csv-import-export' ); ?>
							</button>
						</div>

					</form>
				</div>

			</div>

		</div>
		<?php
	}

	/* -------------------------------------------------------
	   Helpers
	------------------------------------------------------- */
	public function tcie_encode_svg_to_base64( string $path ) {
		if ( ! file_exists( $path ) ) {
			return false;
		}
		$svg = file_get_contents( $path ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		if ( $svg === false ) {
			return false;
		}
		return 'data:image/svg+xml;base64,' . base64_encode( $svg ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
	}

	public function taxocsie_custom_admin_footer() {}
}
