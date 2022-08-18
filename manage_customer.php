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
    wp_enqueue_script( 'jszip', '/wp-content/plugins/manage_customer/js/jszip.js', array('jquery'), null, true );
    wp_enqueue_script( 'xlsx', '/wp-content/plugins/manage_customer/js/xlsx.js', array('jquery'), null, true );
}
add_action( 'admin_enqueue_scripts', 'manageCustomer_enqueue_admin_scripts' );

function manageCustomer_register_options_page() {
	add_menu_page('Manage Customer', 'Manage Customer', 'read', 'manage_customer', 'manageCustomer_backendOptions', '', 4.0);
}
add_action('admin_menu', 'manageCustomer_register_options_page');

function loginRedirect( $redirect_to, $request, $user ){
    return "/wp-admin/admin.php?page=manage_customer";
}
add_filter("login_redirect", "loginRedirect", 10, 3);

function removeMenus(){  
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
	if( !current_user_can( 'administrator' ) ):
        remove_menu_page( 'edit.php?post_type=customer' );
        remove_menu_page( 'edit.php?post_type=order' );
    endif;
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

function getOptionStatusArr() {
    return $options = [
    	'Pending',
    	'option 1',
    	'option 2',
    	'option 3',
    ];
}

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
    if ( isset( $_POST['customer_phone'] ) ) {
        update_post_meta( $post_id, 'customer_phone', $_POST['customer_phone'] );
    }
    if ( isset( $_POST['customer_status'] ) ) {
        update_post_meta( $post_id, 'customer_status', $_POST['customer_status'] );
    } else {
    	update_post_meta( $post_id, 'customer_status', getOptionStatusDefault() );
    }
    if ( isset( $_POST['customer_id'] ) ) {
        update_post_meta( $post_id, 'customer_id', $_POST['customer_id'] );
    }
});

function customer_phone_callback( $post ) {
    $value = get_post_meta( $post->ID, 'customer_phone', true );
    ?>
    <input type="text" name="customer_phone" value="<?= esc_attr( $value ) ?>">
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
					<div id="ExcelTable"></div>
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
									// Here is your object
									var XL_row_object = XLSX.utils.sheet_to_row_object_array(workbook.Sheets[sheetName]);
									var json_object = JSON.stringify(XL_row_object);
									var url = '<?= admin_url( "admin-ajax.php" ) ?>';
		    				        data = {
		    		    		        'action': 'manageCustomer_importCustomer',
		    		    		        'data': JSON.parse(json_object)
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
	if ( isset( $_POST['data'] ) && !empty($_POST['data']) ) {
		$rows = $_POST['data'];
		foreach ($rows as $row) {
			$customerData = [
			  	'post_title'    => $row['name'],
			  	'post_content'  => $row['address'],
			  	'post_type' => 'customer',
			  	'post_status' => 'publish'
			];
			$customerId = wp_insert_post($customerData);
	        update_post_meta( $customerId, 'customer_phone', $row['phone'] );
	        update_post_meta( $customerId, 'customer_status', getOptionStatusDefault() );
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
		<div style="margin-top: 30px;" class="content col-12">
			<table>
				<thead style="display: none;">
					<tr>
						<th><?= __('#') ?></th>
						<th><?= __('Name') ?></th>
						<th><?= __('Phone Number') ?></th>
						<th><?= __('Address') ?></th>
						<th><?= __('Status') ?></th>
						<?php foreach (getOptionStatusArr() as $key => $value): ?>
							<?php if ($key > 0): ?>
								<th><?= __('Count of '.$value) ?></th>
							<?php endif ?>
						<?php endforeach ?>
						<th><?= __('Actions') ?></th>
						<th><?= __('') ?></th>
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
		var dataActionDefault = 'load_more';
		var data = {
			'action' : dataActionDefault,
			'categories' : listCategoriesDefault,
			'posts_per_page' : <?= $numberPostPerPage ?>,
			'page_load' : 1,
			'orderby' : 'title',
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
	        submitSearch.prop('disabled', true);
	        jQuery.post( urlAdminAjax, request, function( json ) {
				if (json.success) {
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
						buttonLoadMore.show();
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
	if ( isset( $_POST['data'] ) ) {
		$value = 0;
		if ($_POST['data']['status']) {
			$value = $_POST['data']['status'];
		}
        update_post_meta( $_POST['data']['id'], 'customer_status', $value );
        if ($value > 0) {
        	$countStatus = get_post_meta($_POST['data']['id'], 'customer_status_'.$value, true);
        	if ($countStatus) {
        		$countStatus = (int)$countStatus + 1;
        		update_post_meta( $_POST['data']['id'], 'customer_status_'.$value, $countStatus );
        	} else {
        		$countStatus = 1;
        		add_post_meta( $_POST['data']['id'], 'customer_status_'.$value, $countStatus );
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
	if ( isset( $_POST['data'] ) ) {
		$content = '<h2>'.__("Info Customer").'</h2>';
		$content .= '<p>'.$_POST['data']['info'].'</p>';
		$content .= '<h2>'.__("Order List Note").'</h2>';
		$content .= '<p>'.$_POST['data']['list_note'].'</p>';
		$orderData = [
		  	'post_title'    => 'Order for customer id: '.$_POST['data']['id'],
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
        update_post_meta( $orderId, 'customer_id', $_POST['data']['id'] );
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
	if (isset($_POST['data'])) {
        $paged = $_POST['data']['page_load'];
        $numberPostPerPage = $_POST['data']['posts_per_page'];
        $orderby = $_POST['data']['orderby'];
        $order = $_POST['data']['order'];
        $totalRest = $_POST['data']['total_rest'];
        $keyWord = $_POST['data']['key_word'];
        $status = $_POST['data']['status'];
        $author = $_POST['data']['author'];
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
			$html .= '<td><div class="name">'.get_the_title().'</div></td>';
			$html .= '<td><div class="phone">'.get_post_meta($postId, 'customer_phone', true).'</div></td>';
			$html .= '<td><div class="address">'.get_the_content().'</div></td>';
			$html .= '<td><div>'.customer_status_callback($postId).'</div></td>';
			foreach (getOptionStatusArr() as $key => $value) {
				if ($key > 0) {
					$html .= '<td><div data-id="customer_status_'.$key.'">'.get_post_meta($postId, 'customer_status_'.$key, true).'</div></td>';
				}
			}
			$html .= '<td><div><button data-toggle="modal" class="btn-order" type="button" data-target="#orderNote">'.__("Create Order").'</button></div></td>';
			$html .= '<td><div><button style="display: none" class="btn-save" type="button">'.__("Save").'</button></div></td>';
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
