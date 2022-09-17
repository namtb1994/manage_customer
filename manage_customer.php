<?php /**
Plugin Name: Manage Customer
Author: DevTeam
Version: 1.0.0
*/

function manageCustomer_enqueue_admin_scripts() {
    wp_register_style( 'manageCustomer-admin-style',  '/wp-content/plugins/manage_customer/css/admin.css', array(), null) ;
    wp_enqueue_style('manageCustomer-admin-style');

    wp_register_style( 'bootstrap-admin-style',  '/wp-content/plugins/manage_customer/css/bootstrap.min.css', array(), null) ;
    wp_enqueue_style('bootstrap-admin-style');

    wp_enqueue_script( 'bootstrap', '/wp-content/plugins/manage_customer/js/bootstrap.min.js', array('jquery'), null, true );
    wp_enqueue_script( 'xlsx', '/wp-content/plugins/manage_customer/js/xlsx.min.js', array('jquery'), null, true );
}
add_action( 'admin_enqueue_scripts', 'manageCustomer_enqueue_admin_scripts' );

function manageCustomer_register_options_page() {
	add_menu_page(
		'Manage Customer',
		'Manage Customer',
		'read',
		'manage_customer',
		'manageCustomer_backendOptions',
		'',
		4.0
	);
	add_menu_page(
		'Customer Status Setting',
		'Customer Status Setting',
		'administrator',
		'customer_status_setting',
		'manageCustomer_statusSetting',
		'',
		4.0
	);
}
add_action('admin_menu', 'manageCustomer_register_options_page');

function getOptionStatusArr() {
	$options = get_option('list_options_customer_status');
	if ($options !== false && $options != '') {

		return json_decode($options);
	}

    return [
    	'Pending',
    	'option 1',
    	'option 2'
    ];
}

function manageCustomer_statusSetting() {
?>
	<?php if (current_user_can( 'administrator' )): ?>
	<div class="manage-customer">
		<div class="container">
			<div class="box-loading">
				<div class="progress"></div>
			</div>
			<div class="message"></div>
			<div class="status-section">
				<h1><?= esc_html( __( 'Customer status setting' ) ); ?></h1>
				<form class="form-status-customer">
					<ul class="items">
						<li class="item">
							<input placeholder="can't leave empty" type="text" name="status-0" value="<?= getOptionStatusArr()[0] ?>">
							<button disabled type="button" class="default"><?= __('Item Default (only change text)') ?></button>
						</li>
						<?php $keyLast = 0; ?>
						<?php foreach (getOptionStatusArr() as $key => $value): ?>
							<?php if ($key > 0): ?>
								<li class="item">
									<input type="text" name="status-<?= $key ?>" value="<?= $value ?>">
									<button type="button" class="btn-remove"><?= __('-') ?></button>
								</li>
							<?php endif ?>
							<?php $keyLast = $key ?>
						<?php endforeach ?>
					</ul>
					<button type="button" class="btn-add-more"><?= __('+') ?></button>
					<button disabled type="submit" class="btn-save"><?= __('Save') ?></button>
				</form>
			</div>
		 	<script type="text/javascript">
		 		var keyLast = parseInt('<?= $keyLast ?>');
		 		var btnSave = jQuery('.status-section .form-status-customer .btn-save');
		 		var inputDefault = jQuery('.form-status-customer [name="status-0"]');
		 	    jQuery('.status-section .form-status-customer').on('submit', function(e) {
		 	    	e.preventDefault();
		 	    	btnSave.prop('disabled', true);
		 	    	var thisForm = jQuery(this);
		 	    	if (inputDefault.val()) {
			 	    	jQuery('.manage-customer div.box-loading').addClass( "loading" );
			 	    	var formData = thisForm.serializeArray();
			 	    	var url = '<?= admin_url( "admin-ajax.php" ) ?>';
				        data = {
		    		        'action': 'manageCustomer_saveListStatus',
		    		        'data': formData
				        };
				        jQuery.post( url, data, function( json ) {
							if (json.success) {
								jQuery('.manage-customer .message').html(json.data.message);
								jQuery('.manage-customer .message').addClass(json.data.status);
								thisForm.find('.item').each(function() {
									var valueItem = jQuery(this).find('input').val();
									if (!valueItem) {
										jQuery(this).remove();
									}
								});
								jQuery('.manage-customer div.box-loading').removeClass( "loading" );
							}
			           	});
		 	    	} else {
		 	    		jQuery('.manage-customer .message').html('<?= __('Please insert text for Item Default') ?>');
		 	    		jQuery('.manage-customer .message').addClass('error');
		 	    	}
		 	    });
		 	    jQuery('.status-section .form-status-customer .item input').on('input', function() {
		 	    	btnSave.prop('disabled', false);
		 	    });
		 	    jQuery(document).on('click', '.status-section .form-status-customer .btn-remove', function(e) {
		 	    	e.preventDefault();
		 	    	jQuery(this).closest('.item').remove();
		 	    	btnSave.prop('disabled', false);
		 	    });
		 	    jQuery('.status-section .form-status-customer .btn-add-more').click(function(e) {
		 	    	e.preventDefault();
		 	    	jQuery(this).prop('disabled', true);
		 	    	keyLast++;
		 	    	var new_item = '<li class="item">';
		 	    	new_item += '<input type="text" name="status-'+keyLast+'">';
		 	    	new_item += '<button type="button" class="btn-remove"><?= __('-') ?></button>';
		 	    	new_item += '</li>';
		 	    	jQuery('.form-status-customer .items').append(new_item);
		 	    	jQuery(this).prop('disabled', false);
		 	    	btnSave.prop('disabled', false);
		 	    });
		 	</script>
	 	</div>
	</div>
	<?php endif ?>
<?php
}

function manageCustomer_saveListStatus() {
	$result = [
		"status" => 'error',
		"message" => __('Have Error! Please try again')
	];
	if ( isset($_REQUEST['data']) ) {
		$options = [];
		foreach ($_REQUEST['data'] as $item) {
			if ($item['value']) {
				$options[] = $item['value'];
			}
		}
		$getOptions = get_option('list_options_customer_status');
		if ($getOptions !== false) {
			update_option( 'list_options_customer_status', json_encode($options), '', 'yes' );
		} else {
			add_option( 'list_options_customer_status', json_encode($options), '', 'yes' );
		}
		$result["status"] = 'updated';
		$result["message"] = __('Save Info Success');

    }
	wp_send_json_success($result);
}
add_action('wp_ajax_manageCustomer_saveListStatus', 'manageCustomer_saveListStatus');
add_action('wp_ajax_nopriv_manageCustomer_saveListStatus', 'manageCustomer_saveListStatus');

function loginRedirect( $redirect_to, $request, $user ) {
    return admin_url( '/admin.php?page=manage_customer' );
}
add_filter("login_redirect", "loginRedirect", 10, 3);

function dashboardRedirect() {
	wp_redirect( admin_url( '/admin.php?page=manage_customer' ) );
}
add_action('wp_dashboard_setup', 'dashboardRedirect');

function removeMenus() {
	remove_menu_page( 'index.php' );                  //Dashboard
	remove_menu_page( 'edit.php' );                   //Posts
	remove_menu_page( 'upload.php' );                 //Media
	remove_menu_page( 'edit.php?post_type=page' );    //Pages
	remove_menu_page( 'edit-comments.php' );          //Comments
	remove_menu_page( 'themes.php' );                 //Appearance
	remove_menu_page( 'tools.php' );                  //Tools
	remove_menu_page( 'options-general.php' );        //Settings
	remove_menu_page( 'profile.php' );        		  //Profile
	remove_menu_page( 'plugins.php' );                //Plugins
	if( !current_user_can( 'administrator' ) ) {
		remove_menu_page( 'edit.php?post_type=customer' );
		remove_menu_page( 'edit.php?post_type=order' );
	}
}
add_action( 'admin_menu', 'removeMenus' );

function createCustomerPostType() {
	register_post_type(
		'customer',
		[
			'labels' => [
				'name' => __( 'Customer' ),
				'singular_name' => __( 'Customer' )
			],
			'public' => true,
			'has_archive' => true,
			'rewrite' => ['slug' => 'customer'],
			'supports' => ['title', 'editor', 'author']
		]
	);
}
add_action( 'init', 'createCustomerPostType' );

function createOrderPostType() {
	register_post_type(
		'order',
		[
			'labels' => [
				'name' => __( 'Order' ),
				'singular_name' => __( 'Order' )
			],
			'public' => true,
			'has_archive' => true,
			'rewrite' => ['slug' => 'order'],
			'supports' => ['title', 'editor']
		]
	);
}
add_action( 'init', 'createOrderPostType' );

function getOptionStatusDefault() {

    return 0;
}

function addCustomerFields() {
    add_meta_box(
        'customer_phone',
        __( 'Phone Number', 'customer_phone' ),
        'customer_phone_callback',
        'customer'
    );

    add_meta_box(
        'customer_last_interact',
        __( 'Customer Last Interact', 'customer_last_interact' ),
        'customer_last_interact_callback',
        'customer'
    );

    add_meta_box(
        'customer_status',
        __( 'Status', 'customer_status' ),
        'customer_status_callback',
        'customer'
    );

    add_meta_box(
        'customer_id',
        __( 'Customer Id', 'customer_id' ),
        'customer_id_callback',
        'order'
    );
}
add_action( 'add_meta_boxes', 'addCustomerFields' );
add_action( 'save_post', function( $post_id ) {
    if ( isset( $_REQUEST['customer_phone'] ) ) {
        update_post_meta( $post_id, 'customer_phone', $_REQUEST['customer_phone'] );
    }
    if ( isset( $_REQUEST['customer_last_interact'] ) ) {
        update_post_meta( $post_id, 'customer_last_interact', $_REQUEST['customer_last_interact'] );
    }
    if ( isset( $_REQUEST['customer_status'] ) ) {
        update_post_meta( $post_id, 'customer_status', $_REQUEST['customer_status'] );
    } else {
    	update_post_meta( $post_id, 'customer_status', getOptionStatusDefault() );
    }
    if ( isset( $_REQUEST['customer_id'] ) ) {
        update_post_meta( $post_id, 'customer_id', $_REQUEST['customer_id'] );
    }
});

function customer_phone_callback( $post ) {
    $value = get_post_meta( $post->ID, 'customer_phone', true );
    ?>
    <input type="text" name="customer_phone" value="<?= esc_attr( $value ) ?>">
    <?php
}

function customer_last_interact_callback( $post ) {
    $value = get_post_meta( $post->ID, 'customer_last_interact', true );
    ?>
    <input type="text" name="customer_last_interact" value="<?= esc_attr( $value ) ?>">
    <?php
}

function customer_id_callback( $post ) {
    $value = get_post_meta( $post->ID, 'customer_id', true );
    ?>
    <input type="text" name="customer_id" value="<?= esc_attr( $value ) ?>">
    <?php
}

function customer_status_callback( $post ) {
	if (is_int($post)) {
		$value = get_post_meta( $post, 'customer_status', true );
		$html = '<select name="customer_status">';
		foreach (getOptionStatusArr() as $key => $label) {
			$selected = '';
			if ($value == $key) {
				$selected = 'selected="selected"';
			}
			$html .= '<option '.$selected.' value="'.$key.'">'.__($label).'</option>';
		}
		$html .= '</select>';

		return $html;
	} else {
		$value = get_post_meta( $post->ID, 'customer_status', true );
	}
    ?>
    <select name="customer_status">
    	<?php foreach (getOptionStatusArr() as $key => $label): ?>
    		<option <?php if ($value == $key): ?>
    			selected="selected"
    		<?php endif ?> value="<?= $key ?>"><?= __($label) ?></option>
    	<?php endforeach ?>
    </select>
    <?php
}

function manageCustomer_backendOptions() {
?>
	<div class="manage-customer">
		<div class="container">
			<div class="box-loading">
				<div class="progress"></div>
			</div>
			<div class="message"></div>
			<?php if (current_user_can( 'administrator' )): ?>
				<div class="import-section">
					<h1><?= esc_html( __( 'Import customer by file xls' ) ); ?></h1>
					<form style="display: flex;" class="form-import">
						<input id="list_customer_xls" type="file" name="list_customer_xls">
						<button type="submit" class="run"><?= __('Run') ?></button>
					</form>
				</div>
			 	<script type="text/javascript">
			 		var ExcelToJSON = function() {
						this.parseExcel = function(file) {
							var reader = new FileReader();
							reader.onload = function(e) {
								var data = e.target.result;
								var workbook = XLSX.read(data, {
									type: 'binary'
								});
								workbook.SheetNames.forEach(function(sheetName) {
									var XL_row_object = XLSX.utils.sheet_to_row_object_array(workbook.Sheets[sheetName]);
									var json_object = JSON.stringify(XL_row_object);
									json_object = JSON.parse(json_object);
									console.log(json_object);
									var url = '<?= admin_url( "admin-ajax.php" ) ?>';
		    				        data = {
		    		    		        'action': 'manageCustomer_importCustomer',
		    		    		        'data': json_object
		    				        };
		    				        jQuery.post( url, data, function( json ) {
		    							if (json.success) {
		    								jQuery('.manage-customer .message').html(json.data.message);
		    								jQuery('.manage-customer .message').addClass(json.data.status);
		    								jQuery('.manage-customer div.box-loading').removeClass( "loading" );
		    								jQuery('.submit_search').trigger('click');
		    							}
		    			           	});
								})
							};
							reader.onerror = function(ex) {
								console.log(ex);
							};
							reader.readAsBinaryString(file);
						};
					};
			 	    jQuery('.import-section .form-import').on('submit', function(e) {
			 	    	e.preventDefault();
			        	const xlsFile = document.getElementById("list_customer_xls");
			        	const input = xlsFile.files[0];
			        	if (input) {
			        		var xl2json = new ExcelToJSON();
		        		    xl2json.parseExcel(input);
        			    	jQuery('.manage-customer div.box-loading').addClass( "loading" );
			        	}
			 	    });
			 	</script>
			<?php endif ?>
			<div class="manage-section">
				<h1><?= esc_html( __( 'Manage Customer' ) ); ?></h1>
				<?php manageCustomer_list() ?>
				<div class="modal" id="orderNote" role="dialog">
					<div class="modal-dialog">
						<div class="modal-content">
							<div class="modal-body">
								<input type="hidden" name="customer-id">
								<div class="address">
									<label><?= __('Customer Address') ?></label>
									<textarea name="order-address"></textarea>
								</div>
								<div style="margin-top: 15px" class="order-list">
									<label><?= __('List Order Note') ?></label>
									<textarea name="order-list"></textarea>
								</div>
								<div style="margin-top: 15px" class="actions">
									<button type="button" class="btn-submit-order"><?= __('Submit') ?></button>
									<button style="display: none;" class="btn-close-popup" type="button" data-dismiss="modal"><?= __('Close') ?></button>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
	 	</div>
	</div>
<?php
}

function manageCustomer_importCustomer() {
	$result = [
		'status' => 'updated',
		'message' => __('Import success')
	];
	if ( isset( $_REQUEST['data'] ) && !empty($_REQUEST['data']) ) {
		$rows = $_REQUEST['data'];
		foreach ($rows as $row) {
			if (isset($row['phone']) && isset($row['name'])) {
				$customerData = [
				  	'post_type' => 'customer',
				  	'post_status' => 'publish',
				  	'post_title' => $row['name']
				];
				if (isset($row['address'])) {
					$customerData['post_content'] = $row['address'];
				}
				$customerId = wp_insert_post($customerData);
				update_post_meta( $customerId, 'customer_phone', $row['phone'] );
				update_post_meta( $customerId, 'customer_status', getOptionStatusDefault() );
				if (isset($row['last_interact'])) {
					$lastInteract = $row['last_interact'];
					if (is_numeric($lastInteract)) {
						$UNIX_DATE = ($lastInteract - 25569) * 86400;
						$lastInteract = gmdate("d-m-Y", $UNIX_DATE);
					}
					update_post_meta( $customerId, 'customer_last_interact',  $lastInteract);
				}
			}
		}
    } else {
    	$result['status'] = 'error';
    	$result['message'] = __('Have error');
    }

	wp_send_json_success($result);
}
add_action('wp_ajax_manageCustomer_importCustomer', 'manageCustomer_importCustomer');
add_action('wp_ajax_nopriv_manageCustomer_importCustomer', 'manageCustomer_importCustomer');

function getListStaff() {
	$args = array(
	    'role'    => 'contributor',
	    'orderby' => 'user_nicename',
	    'order'   => 'ASC'
	);

	return get_users( $args );
}

function manageCustomer_list() {
	$categories = [];
	$numberPostPerPage = 20;
?>
	<div class="list-container row">
		<div class="box-loading">
			<div class="progress"></div>
		</div>
		<div class="filter col-12">
			<div class="row">
				<div class="col-12 search_key_word" style="display: none;">
					<input type="text" name="key_word" placeholder="<?= __('Search by customer name') ?>">
					<button type="button" class="submit_search"><?= __('Search') ?></button>
				</div>
				<?php if (current_user_can( 'administrator' )): ?>
					<div class="col-3">
						<select name="author">
							<option value=""><?= __('Staff') ?></option>
							<?php foreach (getListStaff() as $staff): ?>
								<option value="<?= $staff->ID ?>"><?= $staff->display_name ?></option>
							<?php endforeach ?>
						</select>
					</div>
				<?php endif ?>
				<div class="col-3">
					<select name="status">
						<option value=""><?= __('Status') ?></option>
						<?php foreach (getOptionStatusArr() as $key => $label): ?>
							<option value="<?= $key ?>"><?= __($label) ?></option>
						<?php endforeach ?>
					</select>
				</div>
			</div>
		</div>
		<div class="col-12">
			<div class="pagination">
				<div style="display: flex;">
					<span><?= __("page") ?></span>
					<input min="1" style="width: 100px; text-align: center;" type="number" name="current-page" value="1">
					<span><?= __("of") ?></span>
					<span class="max-page">1</span>
					<span><?= __("page(s)") ?></span>
					<span></span>
					<span><?= __("Total number of items: ") ?></span>
					<span class="total-items">0</span>
				</div>
			</div>
		</div>
		<div class="content col-12">
			<table>
				<thead style="display: none;">
					<tr>
						<th><?= __('#') ?></th>
						<?php if (current_user_can( 'administrator' )): ?>
							<th><?= __('Staff') ?></th>
						<?php endif ?>
						<th><?= __('Name') ?></th>
						<th><?= __('Phone Number') ?></th>
						<th><?= __('Address') ?></th>
						<th><?= __('Last interact') ?></th>
						<th><?= __('Status') ?></th>
						<?php foreach (getOptionStatusArr() as $key => $value): ?>
							<?php if ($key > 0): ?>
								<th><?= __('Count of '.$value) ?></th>
							<?php endif ?>
						<?php endforeach ?>
						<th><?= __('Actions') ?></th>
						<th class="no-border"><?= __('') ?></th>
					</tr>
				</thead>
				<tbody class="list-items items"></tbody>
			</table>
		</div>
		<div class="actions col-12">
			<button style="display: none;" type="button" class="load-more"><?= __('Load More') ?> <span class="count"></span></button>
		</div>
	</div>
	<script type="text/javascript">
		var urlAdminAjax = '<?= admin_url( "admin-ajax.php" ) ?>';
		var container = jQuery('.list-container');
		var content = container.find('.content');
		var containerPosts = container.find('.list-items');
		var buttonLoadMore = container.find('.load-more');
		var filterCategory = container.find('.filter .categories button');
		var filterSelect = container.find('.filter select');
		var listCategoriesDefault = <?= json_encode($categories) ?>;
		var author = container.find('[name="author"]');
		var customerStatus = container.find('[name="status"]');
		var inputKeyWord = container.find('input[name="key_word"]');
		var submitSearch = container.find('button.submit_search');
		var pagination = container.find('.pagination');
		var dataActionDefault = 'load_more';
		var data = {
			'action' : dataActionDefault,
			'categories' : listCategoriesDefault,
			'posts_per_page' : <?= $numberPostPerPage ?>,
			'page_load' : 1,
			'orderby' : 'id',
			'order' : 'ASC',
			'total_rest' : null,
			'key_word' : inputKeyWord.val(),
			'author' : null,
			'status' : customerStatus.val()
		};
		if (author.length) {
			data.author = author.val();
		}
		var scrollEnable = false;
		var fullWidth = '<?= $fullWidth ?>';

		function loadPosts(data, element) {
			scrollEnable = false;
	        var request = {
		        'action': 'manageCustomer_getListPost',
		        'data': data
	        };
	        buttonLoadMore.prop('disabled', true);
	        pagination.find('[name="current-page"]').prop('disabled', true);
	        submitSearch.prop('disabled', true);
	        jQuery.post( urlAdminAjax, request, function( json ) {
				if (json.success) {
					var maxPage = json.data.max_num_pages;
					var totalItems = json.data.count;
					pagination.find('[name="current-page"]').attr('max', maxPage);
					pagination.find('.max-page').html(maxPage);
					pagination.find('.total-items').html(totalItems);
					pagination.find('[name="current-page"]').val(parseInt(json.data.paged));
					if (maxPage > 1) {
						pagination.find('[name="current-page"]').prop('disabled', false);
					} else {
						pagination.find('[name="current-page"]').prop('disabled', true);
					}
					if (json.data.count) {
						element.closest('table').find('thead').show();
					} else {
						element.closest('table').find('thead').hide();
					}
					if (data.action == 'filter') {
						element.html(json.data.html);
						data.action = dataActionDefault;
					} else {
						element.append(json.data.html);
					}
					if (json.data.paged < json.data.max_num_pages) {
						data.page_load = parseInt(json.data.paged) + 1;
						data.total_rest = parseInt(json.data.total_rest);
						buttonLoadMore.find('.count').html('('+data.total_rest+')');
						//buttonLoadMore.show();
						buttonLoadMore.prop('disabled', false);
						scrollEnable = true;
					} else {
						scrollEnable = false;
						buttonLoadMore.hide();
					}
					if (container.hasClass('loading')) {
						container.removeClass('loading');
					}
					if (inputKeyWord.prop('disabled')) {
						inputKeyWord.prop('disabled', false);
					}
					submitSearch.prop('disabled', false);
				}
           	});
		}
		jQuery(document).ready(function() {
			container.addClass('loading');
			loadPosts(data, containerPosts);
		});
		buttonLoadMore.click(function(e) {
	    	e.preventDefault();
       		loadPosts(data, containerPosts);
		});
		pagination.find('[name="current-page"]').on('input', function(e) {
	    	e.preventDefault();
	    	data.action = 'filter';
	    	data.page_load = jQuery(this).val();
       		loadPosts(data, containerPosts);
		});
		submitSearch.click(function(e) {
			e.preventDefault();
			data.action = 'filter';
			data.key_word = inputKeyWord.val();
			data.total_rest = null;
			data.page_load = 1;
			container.addClass('loading');
			inputKeyWord.prop('disabled', true);
			loadPosts(data, containerPosts);
		});
		filterSelect.change(function() {
			var thisSelect = jQuery(this);
			data.action = 'filter';
			data.key_word = inputKeyWord.val();
			data[thisSelect.attr('name')] = thisSelect.val();
			data.total_rest = null;
			data.page_load = 1;
			container.addClass('loading');
			inputKeyWord.prop('disabled', true);
			loadPosts(data, containerPosts);
		});
		inputKeyWord.keyup(function(e) {
		    if (e.keyCode === 13) {
		        submitSearch.trigger('click');
		    }
		});
		jQuery(document).on('click', '.list-items .item .btn-save', function(e) {
			e.preventDefault();
			jQuery(this).hide();
			var thisItem = jQuery(this).closest('.item');
			
			var data = {
				'id' : thisItem.attr('data-id'),
				'status' : thisItem.find('[name="customer_status"]').val()
			};
	        var request = {
		        'action': 'manageCustomer_savePost',
		        'data': data
	        };
			jQuery.post( urlAdminAjax, request, function( json ) {
				if (json.success) {
					if (json.data.count) {
						thisItem.find('[data-id="customer_status_'+json.data.status_id+'"]').html(json.data.count);
					}
				}
           	});
		});
		
		jQuery(document).on('click', '.list-items .item .btn-order', function(e) {
			e.preventDefault();
			var thisItem = jQuery(this).closest('.item');
			var id = thisItem.attr('data-id');
			var address = thisItem.find('.address').html();
			var name = thisItem.find('.name').html();
			var phone = thisItem.find('.phone').html();
			var info = "Customer Name: "+name+' \r\n ';
			info += "Phone Number: "+phone+' \r\n ';
			info += "Address: "+address;
			var orderNote = jQuery('#orderNote');
			orderNote.find('[name="customer-id"]').val(id);
			orderNote.find('[name="order-address"]').val(info);
		});

		jQuery(document).on('click', '#orderNote .btn-submit-order', function(e) {
			e.preventDefault();
			var thisOrder = jQuery(this).closest('.modal-body');
			var id = thisOrder.find('[name="customer-id"]').val();
			var info = thisOrder.find('[name="order-address"]').val();
			var listNote = thisOrder.find('[name="order-list"]').val();
			if (listNote) {
				var data = {
					'id' : id,
					'info' : info,
					'list_note' : listNote
				};
		        var request = {
			        'action': 'manageCustomer_createOrder',
			        'data': data
		        };
				jQuery.post( urlAdminAjax, request, function( json ) {
					if (json.success) {
						thisOrder.find('[name="customer-id"]').val('');
						thisOrder.find('[name="order-address"]').val('');
						thisOrder.find('[name="order-list"]').val('');
						alert(json.data.message);
					}
	           	});
	           	jQuery('#orderNote .btn-close-popup').trigger('click');
			} else {
				alert('<?= __("Please insert info order in List Order Note") ?>');
			}
		});

		jQuery(document).on('change', '.list-items .item [name="customer_status"]', function() {
			var thisItem = jQuery(this).closest('.item');
			thisItem.find('.btn-save').show();
		});
	</script>
<?php
}

function manageCustomer_savePost() {
	$result = [
		'status' => 'updated',
		'message' => __('Update success'),
		'status_id' => null,
		'count' => null
	];
	if ( isset( $_REQUEST['data'] ) ) {
		$value = 0;
		if ($_REQUEST['data']['status']) {
			$value = $_REQUEST['data']['status'];
		}
        update_post_meta( $_REQUEST['data']['id'], 'customer_status', $value );
        if ($value > 0) {
        	$countStatus = get_post_meta($_REQUEST['data']['id'], 'customer_status_'.$value, true);
        	if ($countStatus) {
        		$countStatus = (int)$countStatus + 1;
        		update_post_meta( $_REQUEST['data']['id'], 'customer_status_'.$value, $countStatus );
        	} else {
        		$countStatus = 1;
        		add_post_meta( $_REQUEST['data']['id'], 'customer_status_'.$value, $countStatus );
        	}
        	$result['status_id'] = $value;
        	$result['count'] = $countStatus;
        }
    } else {
    	$result['status'] = 'error';
    	$result['message'] = __('Have error');
    }

	wp_send_json_success($result);
}
add_action('wp_ajax_manageCustomer_savePost', 'manageCustomer_savePost');
add_action('wp_ajax_nopriv_manageCustomer_savePost', 'manageCustomer_savePost');

function manageCustomer_createOrder() {
	$result = [
		'status' => 'updated',
		'message' => __('Created order success'),
		'id' => null
	];
	if ( isset( $_REQUEST['data'] ) ) {
		$content = '<h2>'.__("Info Customer").'</h2>';
		$content .= '<p>'.$_REQUEST['data']['info'].'</p>';
		$content .= '<h2>'.__("Order List Note").'</h2>';
		$content .= '<p>'.$_REQUEST['data']['list_note'].'</p>';
		$orderData = [
		  	'post_title'    => 'Order for customer id: '.$_REQUEST['data']['id'],
		  	'post_content'  => $content,
		  	'post_type' => 'order',
		  	'post_status' => 'publish'
		];
		$orderId = wp_insert_post($orderData);
		$updateOrder = [
			'ID'           => $orderId,
			'post_title'   => '#'.$orderId
	  	];
		wp_update_post( $updateOrder );
        update_post_meta( $orderId, 'customer_id', $_REQUEST['data']['id'] );
        $result['id'] = $orderId;
        $result['message'] = __('Created order: '.$updateOrder['post_title']);
    } else {
    	$result['status'] = 'error';
    	$result['message'] = __('Have error');
    }

	wp_send_json_success($result);
}
add_action('wp_ajax_manageCustomer_createOrder', 'manageCustomer_createOrder');
add_action('wp_ajax_nopriv_manageCustomer_createOrder', 'manageCustomer_createOrder');

function title_filter( $where, &$wp_query ) {
    global $wpdb;
    if ( $search_term = $wp_query->get( 'search_prod_title' ) ) {
        $where .= ' AND ' . $wpdb->posts . '.post_title LIKE \'%' . esc_sql( like_escape( $search_term ) ) . '%\'';
    }

    return $where;
}

function manageCustomer_getListPost() {
	$paged = 1;
	$numberPostPerPage = 20;
	$orderby = 'title';
	$order = 'ASC';
	$totalRest = null;
	$keyWord = null;
	$status = null;
	$author = null;
	if (isset($_REQUEST['data'])) {
        $paged = $_REQUEST['data']['page_load'];
        $numberPostPerPage = $_REQUEST['data']['posts_per_page'];
        $orderby = $_REQUEST['data']['orderby'];
        $order = $_REQUEST['data']['order'];
        $totalRest = $_REQUEST['data']['total_rest'];
        $keyWord = $_REQUEST['data']['key_word'];
        $status = $_REQUEST['data']['status'];
        $author = $_REQUEST['data']['author'];
    }
    $args = [
		'paged' => $paged,
		'post_type' => 'customer',
		'posts_per_page' => $numberPostPerPage,
		'post_status' => 'publish',
		'orderby' => $orderby,
		'order' => $order
	];
    if ( !current_user_can( 'administrator' ) ) {
		$user = wp_get_current_user();
		$args['author'] = $user->ID;
	}
	if ($keyWord) {
		$args['search_prod_title'] = $keyWord;
	}
	if ($author) {
		$args['author'] = $author;
	}
	if ($status != null) {
		$args['meta_query'][] = [
			'key' => 'customer_status',
			'value' => $status,
			'compare' => '='
		];
	}
	add_filter( 'posts_where', 'title_filter', 10, 2 );
	$query = new WP_Query( $args );
	remove_filter( 'posts_where', 'title_filter', 10, 2 );
	if (!$totalRest) {
		$totalRest = $query->found_posts - (int)$numberPostPerPage;
	} else {
		$totalRest = $totalRest - (int)$numberPostPerPage;
	}
	$html = '';
	if ($query->have_posts()) {
		while($query->have_posts()) : $query->the_post();
			$postId = get_the_ID();
			$html .= '<tr class="item" data-id="'.$postId.'">';
			$html .= '<td><div>'.$postId.'</div></td>';
			if (current_user_can( 'administrator' )) {
				$html .= '<td><div class="name">'.get_the_author().'</div></td>';
			}
			$html .= '<td><div class="name">'.get_the_title().'</div></td>';
			$html .= '<td><div class="phone">'.get_post_meta($postId, 'customer_phone', true).'</div></td>';
			$html .= '<td><div class="address">'.get_the_content().'</div></td>';
			$html .= '<td><div class="last_interact">'.get_post_meta($postId, 'customer_last_interact', true).'</div></td>';
			$html .= '<td><div>'.customer_status_callback($postId).'</div></td>';
			foreach (getOptionStatusArr() as $key => $value) {
				if ($key > 0) {
					$html .= '<td><div data-id="customer_status_'.$key.'">'.get_post_meta($postId, 'customer_status_'.$key, true).'</div></td>';
				}
			}
			$html .= '<td><div><button data-toggle="modal" class="btn-order" type="button" data-target="#orderNote">'.__("Create Order").'</button></div></td>';
			$html .= '<td class="no-border"><div><button style="display: none" class="btn-save" type="button">'.__("Save").'</button></div></td>';
			$html .= '</tr>';
		endwhile;
	} else {
		$html = '<tr class="item no-result"><td>'.__('No Items for the Selected Filter').'</td></tr>';
	}

	$result = [
		'html' => $html,
		'total_rest' => $totalRest,
		'paged' => $paged,
		'max_num_pages' => $query->max_num_pages,
		'count' => $query->found_posts
	];

	wp_send_json_success($result);
}
add_action('wp_ajax_manageCustomer_getListPost', 'manageCustomer_getListPost');
add_action('wp_ajax_nopriv_manageCustomer_getListPost', 'manageCustomer_getListPost');
