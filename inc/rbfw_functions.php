<?php
	if ( ! defined( 'ABSPATH' ) ) {
		exit;
	}
	
	// Language Load
	add_action( 'init', 'rbfw_language_load' );
	function rbfw_language_load() {
		$plugin_dir = basename( dirname( __DIR__ ) ) . "/languages/";
		load_plugin_textdomain( 'booking-and-rental-manager-for-woocommerce', false, $plugin_dir );
	}
	function rbfw_array_strip( $array_or_string ) {
		if ( is_string( $array_or_string ) ) {
			$array_or_string = sanitize_text_field( $array_or_string );
		} elseif ( is_array( $array_or_string ) ) {
			foreach ( $array_or_string as $key => &$value ) {
				if ( is_array( $value ) ) {
					$value = rbfw_array_strip( $value );
				} else {
					$value = sanitize_text_field( $value );
				}
			}
		}

		return $array_or_string;
	}
	function rbfw_get_location_arr() {
		$terms = get_terms( array(
			'taxonomy'   => 'rbfw_item_location',
			'hide_empty' => false,
		) );
		$arr   = array(
			'' => __( 'Please Select a Location', 'booking-and-rental-manager-for-woocommerce' )
		);
		foreach ( $terms as $_terms ) {
			$arr[ $_terms->name ] = $_terms->name;
		}

		return $arr;
	}
	function rbfw_get_location_dropdown( $name, $saved_value = '', $class = '' ) {
		$location_arr = rbfw_get_location_arr();
		echo "<select name=$name class=$class>";
		foreach ( $location_arr as $key => $value ) {
			$selected_text = ! empty( $saved_value ) && $saved_value == $key ? 'Selected' : '';
			echo "<option value='$key' $selected_text>" . esc_html( $value ) . "</option>";
		}
		echo "</select>";
	}

	function rbfw_get_option( $option, $section, $default = '' ) {
		global $rbfw;

		return $rbfw->get_option( $option, $section, $default );
	}
	function rbfw_get_string( $option_name, $default_string ) {
		return rbfw_get_option( $option_name, 'rbfw_basic_translation_settings', $default_string );
	}
	function rbfw_string( $option_name, $default_string ) {
		echo rbfw_get_option( $option_name, 'rbfw_basic_translation_settings', $default_string );
	}
	function rbfw_string_return( $option_name, $default_string ) {
		return rbfw_get_option( $option_name, 'rbfw_basic_translation_settings', $default_string );
	}
	function rbfw_get_template_list() {
		global $rbfw;
		$options = $rbfw->get_template_list();

		return apply_filters( 'rbfw_template_list_arr', $options );
	}

	function rbfw_get_datetime( $date, $type = 'date-time-text' ) {
		global $rbfw;
		return $rbfw->get_datetime( $date, $type );
	}
	
	function rbfw_get_order_item_meta( $item_id, $key ) {
		global $rbfw;

		return $rbfw->get_order_meta( $item_id, $key );
	}

	function rbfw_check_product_exists( $id ) {
		return is_string( get_post_status( $id ) );
	}

	function rbfw_get_event_extra_price_by_name( $name, $event_id ) {
		$ticket_type_arr = get_post_meta( $event_id, 'rbfw_extra_service_data', true ) ? get_post_meta( $event_id, 'rbfw_extra_service_data', true ) : [];
		foreach ( $ticket_type_arr as $price ) {
			if ( $price['service_name'] === $name ) {
				$p = $price['service_price'];
			}
		}

		return $p;
	}
	function rbfw_get_extra_price_arr( $ticket_type, $rbfw_id ) {
		$price = [];
		//print_r($ticket_type);
		foreach ( $ticket_type as $ticket ) {
			// print_r($ticket);
			$exp_qty = explode( "*", $ticket );
			//$price[] = rbfw_get_event_extra_price_by_name( $exp_qty[1], $rbfw_id );
			$price[] = rbfw_get_event_extra_price_by_name( $ticket, $rbfw_id );
		}

		return $price;
	}

	function rbfw_cart_event_extra_service( $type, $total_price, $product_id ) {
		global $rbfw;
		$t_price = $total_price;

		$rbfw_pickup_start_date = isset( $_POST['rbfw_pickup_start_date'] ) ? rbfw_array_strip( $_POST['rbfw_pickup_start_date'] ) : current_time( 'Y-m-d' );
		$rbfw_pickup_start_time = isset( $_POST['rbfw_pickup_start_time'] ) ? rbfw_array_strip( $_POST['rbfw_pickup_start_time'] ) : '';
		$rbfw_pickup_end_date   = isset( $_POST['rbfw_pickup_end_date'] ) ? rbfw_array_strip( $_POST['rbfw_pickup_end_date'] ) : current_time( 'Y-m-d' );
		$rbfw_pickup_end_time   = isset( $_POST['rbfw_pickup_end_time'] ) ? rbfw_array_strip( $_POST['rbfw_pickup_end_time'] ) : '';
		$start_datetime = date( 'Y-m-d H:i', strtotime( $rbfw_pickup_start_date . ' ' . $rbfw_pickup_start_time ) );
		$end_datetime   = date( 'Y-m-d H:i', strtotime( $rbfw_pickup_end_date . ' ' . $rbfw_pickup_end_time ) );
		$extra_service_name  = isset( $_POST['service_name'] ) ? rbfw_array_strip( $_POST['service_name'] ) : array();
		$extra_service_qty   = isset( $_POST['service_qty'] ) ? rbfw_array_strip( $_POST['service_qty'] ) : array();
		$extra_service_price = 0;
		$extra_service_price = rbfw_get_extra_price_arr( $extra_service_qty, $product_id );
		$rbfw_extra          = [];
		
		if ( ! empty($extra_service_name) ) {

			for ( $i = 0; $i < count( $extra_service_name ); $i ++ ) {

				if(! empty($extra_service_qty[ $i ])):
					
				if($extra_service_qty[ $i ] == $extra_service_name[ $i ]){
					$exp_qty = explode( "*", $extra_service_qty[ $i ] );
					$rbfw_extra[ $i ]['service_name']   = ! empty( $exp_qty[0] ) ? stripslashes( strip_tags( $exp_qty[0] ) ) : '';
					$rbfw_extra[ $i ]['service_price']  = ! empty( $extra_service_price[ $i ] ) ? stripslashes( strip_tags( $extra_service_price[ $i ] ) ) : '';
					//$rbfw_extra[ $i ]['service_qty']    = ! empty( $exp_qty[0] ) ? stripslashes( strip_tags( $exp_qty[0] ) ) : 1;
					$rbfw_extra[ $i ]['service_qty']    = 1;
					$rbfw_extra[ $i ]['start_datetime'] = $start_datetime;
					$rbfw_extra[ $i ]['end_datetime']   = $end_datetime;
					//$extprice                           = ( (int)$extra_service_price[ $i ] * (int)$extra_service_qty[ $i ] );
					$extprice                           = ((float)$extra_service_price[ $i ]);
					$t_price                        	+= $extprice;
				}
				endif;
				
			}
		}

		if ( $type == 'ticket_price' ) {
			return $t_price;
			//return $extprice;
		} else {
			return $rbfw_extra;
		}

	}

	if ( ! function_exists( 'mep_get_date_diff' ) ) {
		function mep_get_date_diff( $start_datetime, $end_datetime ) {
			$current   = date( 'Y-m-d H:i', strtotime( $start_datetime ) );
			$newformat = date( 'Y-m-d H:i', strtotime( $end_datetime ) );
			$datetime1 = new DateTime( $newformat );
			$datetime2 = new DateTime( $current );
			$interval  = date_diff( $datetime2, $datetime1 );

			$arr = [];
			if($start_datetime == $end_datetime){ $days = 1; } else { $days = $interval->days; }
			if(!empty($interval->h)){ $hours = $interval->h; } else { $hours = 0; }
			if(!empty($interval->i)){ $minutes = $interval->i; } else { $minutes = 0; }

			return [ $days, $hours, $minutes ];
			
		}
	}
	// Getting event exprie date & time
	if ( ! function_exists( 'rbfw_day_diff_status' ) ) {
		function rbfw_day_diff_status( $start_datetime, $end_datetime ) {
			$current   = date( 'Y-m-d H:i', strtotime( $start_datetime ) );
			$newformat = date( 'Y-m-d H:i', strtotime( $end_datetime ) );
			$datetime1 = new DateTime( $newformat );
			$datetime2 = new DateTime( $current );
			$interval  = date_diff( $datetime2, $datetime1 );

			if ( current_time( 'Y-m-d H:i' ) > $newformat ) {
				return "<span class=err>Expired</span>";
			} else {
				$days    = $interval->days;
				$hours   = $interval->h;
				$minutes = $interval->i;
				if ( $days > 0 ) {
					$dd = $days . " days ";
				} else {
					$dd = "";
				}
				if ( $hours > 0 ) {
					$hh = $hours . " hours ";
				} else {
					$hh = "";
				}
				if ( $minutes > 0 ) {
					$mm = $minutes . " minutes ";
				} else {
					$mm = "";
				}

				return "<span class='active'>" . esc_html( $dd ) . " " . esc_html( $hh ) . " " . esc_html( $mm ) . "</span>";
			}
		}
	}
	function rbfw_price_calculation( $item_id, $start_datetime, $end_datetime, $start_date = null) {

		$daily_rate  = get_post_meta( $item_id, 'rbfw_daily_rate', true ) ? get_post_meta( $item_id, 'rbfw_daily_rate', true ) : 0;
		$hourly_rate = get_post_meta( $item_id, 'rbfw_hourly_rate', true ) ? get_post_meta( $item_id, 'rbfw_hourly_rate', true ) : 0;
		// sunday rate
		$hourly_rate_sun = get_post_meta($item_id, 'rbfw_sun_hourly_rate', true) ? get_post_meta($item_id, 'rbfw_sun_hourly_rate', true) : 0;
		$daily_rate_sun = get_post_meta($item_id, 'rbfw_sun_daily_rate', true) ? get_post_meta($item_id, 'rbfw_sun_daily_rate', true) : 0;
		$enabled_sun = get_post_meta($item_id, 'rbfw_enable_sun_day', true) ? get_post_meta($item_id, 'rbfw_enable_sun_day', true) : 'yes';

		// monday rate
		$hourly_rate_mon = get_post_meta($item_id, 'rbfw_mon_hourly_rate', true) ? get_post_meta($item_id, 'rbfw_mon_hourly_rate', true) : 0;
		$daily_rate_mon = get_post_meta($item_id, 'rbfw_mon_daily_rate', true) ? get_post_meta($item_id, 'rbfw_mon_daily_rate', true) : 0;
		$enabled_mon = get_post_meta($item_id, 'rbfw_enable_mon_day', true) ? get_post_meta($item_id, 'rbfw_enable_mon_day', true) : 'yes';

		// tuesday rate
		$hourly_rate_tue = get_post_meta($item_id, 'rbfw_tue_hourly_rate', true) ? get_post_meta($item_id, 'rbfw_tue_hourly_rate', true) : 0;
		$daily_rate_tue = get_post_meta($item_id, 'rbfw_tue_daily_rate', true) ? get_post_meta($item_id, 'rbfw_tue_daily_rate', true) : 0;
		$enabled_tue = get_post_meta($item_id, 'rbfw_enable_tue_day', true) ? get_post_meta($item_id, 'rbfw_enable_tue_day', true) : 'yes';

		// wednesday rate
		$hourly_rate_wed = get_post_meta($item_id, 'rbfw_wed_hourly_rate', true) ? get_post_meta($item_id, 'rbfw_wed_hourly_rate', true) : 0;
		$daily_rate_wed = get_post_meta($item_id, 'rbfw_wed_daily_rate', true) ? get_post_meta($item_id, 'rbfw_wed_daily_rate', true) : 0;
		$enabled_wed = get_post_meta($item_id, 'rbfw_enable_wed_day', true) ? get_post_meta($item_id, 'rbfw_enable_wed_day', true) : 'yes';

		// thursday rate
		$hourly_rate_thu = get_post_meta($item_id, 'rbfw_thu_hourly_rate', true) ? get_post_meta($item_id, 'rbfw_thu_hourly_rate', true) : 0;
		$daily_rate_thu = get_post_meta($item_id, 'rbfw_thu_daily_rate', true) ? get_post_meta($item_id, 'rbfw_thu_daily_rate', true) : 0;
		$enabled_thu = get_post_meta($item_id, 'rbfw_enable_thu_day', true) ? get_post_meta($item_id, 'rbfw_enable_thu_day', true) : 'yes';

		// friday rate
		$hourly_rate_fri = get_post_meta($item_id, 'rbfw_fri_hourly_rate', true) ? get_post_meta($item_id, 'rbfw_fri_hourly_rate', true) : 0;
		$daily_rate_fri = get_post_meta($item_id, 'rbfw_fri_daily_rate', true) ? get_post_meta($item_id, 'rbfw_fri_daily_rate', true) : 0;
		$enabled_fri = get_post_meta($item_id, 'rbfw_enable_fri_day', true) ? get_post_meta($item_id, 'rbfw_enable_fri_day', true) : 'yes';	

		// saturday rate
		$hourly_rate_sat = get_post_meta($item_id, 'rbfw_sat_hourly_rate', true) ? get_post_meta($item_id, 'rbfw_sat_hourly_rate', true) : 0;
		$daily_rate_sat = get_post_meta($item_id, 'rbfw_sat_daily_rate', true) ? get_post_meta($item_id, 'rbfw_sat_daily_rate', true) : 0;
		$enabled_sat = get_post_meta($item_id, 'rbfw_enable_sat_day', true) ? get_post_meta($item_id, 'rbfw_enable_sat_day', true) : 'yes';

		$current_day = date('D', strtotime($start_date));

		if($current_day == 'Sun' && $enabled_sun == 'yes'){
			$hourly_rate = $hourly_rate_sun;
			$daily_rate = $daily_rate_sun;
		}elseif($current_day == 'Mon' && $enabled_mon == 'yes'){
			$hourly_rate = $hourly_rate_mon;
			$daily_rate = $daily_rate_mon;
		}elseif($current_day == 'Tue' && $enabled_tue == 'yes'){
			$hourly_rate = $hourly_rate_tue;
			$daily_rate = $daily_rate_tue;
		}elseif($current_day == 'Wed' && $enabled_wed == 'yes'){
			$hourly_rate = $hourly_rate_wed;
			$daily_rate = $daily_rate_wed;
		}elseif($current_day == 'Thu' && $enabled_thu == 'yes'){
			$hourly_rate = $hourly_rate_thu;
			$daily_rate = $daily_rate_thu;
		}elseif($current_day == 'Fri' && $enabled_fri == 'yes'){
			$hourly_rate = $hourly_rate_fri;
			$daily_rate = $daily_rate_fri;
		}elseif($current_day == 'Sat' && $enabled_sat == 'yes'){
			$hourly_rate = $hourly_rate_sat;
			$daily_rate = $daily_rate_sat;
		}else{
			$hourly_rate = $hourly_rate;
			$daily_rate = $daily_rate;		
		}

		$current_date = date('Y-m-d');
		$rbfw_sp_prices = get_post_meta( $item_id, 'rbfw_seasonal_prices', true );
		if(!empty($rbfw_sp_prices)){
			$sp_array = [];
			$i = 0;
			foreach ($rbfw_sp_prices as $value) {
				$rbfw_sp_start_date = $value['rbfw_sp_start_date'];
				$rbfw_sp_end_date 	= $value['rbfw_sp_end_date'];
				$rbfw_sp_price_h 	= $value['rbfw_sp_price_h'];
				$rbfw_sp_price_d 	= $value['rbfw_sp_price_d'];
				$sp_array[$i]['sp_dates'] = rbfw_getBetweenDates($rbfw_sp_start_date, $rbfw_sp_end_date);
				$sp_array[$i]['sp_hourly_rate'] = $rbfw_sp_price_h;
				$sp_array[$i]['sp_daily_rate']  = $rbfw_sp_price_d;
				$i++;
			}
	
			foreach ($sp_array as $sp_arr) {
				if (in_array($current_date,$sp_arr['sp_dates'])){
					$hourly_rate = $sp_arr['sp_hourly_rate'];
					$daily_rate  = $sp_arr['sp_daily_rate'];
				}
			}
		}

		$diff        = mep_get_date_diff( $start_datetime, $end_datetime );
		$days        = $diff[0];
		$hour        = $diff[1];
		$minute      = $diff[2];
		$day_price  = $days * $daily_rate;
		$hour_price = $hour * $hourly_rate;
		$total_price = $day_price + $hour_price;

		return $total_price;
	}

	add_action( 'rbfw_availabe_label', 'rbfw_show_availabe_label', 10, 2 );
	function rbfw_show_availabe_label( $availabe_type_seat, $rbfw_id ) {
		$stock_status = get_post_meta( $rbfw_id, 'rbfw_inventory_manage', true ) ? get_post_meta( $rbfw_id, 'rbfw_inventory_manage', true ) : 'yes';
		if ( $stock_status == 'yes' ) {
			?>
		  <p class='rbfw_availabe_seat_label'><?php echo esc_html( $availabe_type_seat ) . ' ';
					  rbfw_string( 'rbfw_string_availabe', __( 'Availabe', 'booking-and-rental-manager-for-woocommerce' ) ); ?></p>
			<?php
		}
	}
	function rbfw_woo_install_check() {
		include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
		$plugin_dir = ABSPATH . 'wp-content/plugins/woocommerce';
		if ( is_plugin_active( 'woocommerce/woocommerce.php' ) ) {
			return 'Yes';
		} elseif ( is_dir( $plugin_dir ) ) {
			return 'Installed But Not Active';
		} else {
			return 'No';
		}
	}
	add_filter( 'manage_rbfw_item_posts_columns', 'rbfw_item_col_mod_head' );
	function rbfw_item_col_mod_head( $columns ) {
		unset( $columns['date'] );
		// unset( $columns['taxonomy-rbfw_item_cat'] );
		// unset( $columns['taxonomy-rbfw_item_org'] );
		unset( $columns['taxonomy-rbfw_item_location'] );
		$columns['rbfw_type'] = __( 'Type', 'mage-eventpress' );

		// $columns['mep_event_date'] = __( 'Event Start Date', 'mage-eventpress' );
		return $columns;
	}
	function rbfw_create_tag_taxonomy() {
		$labels = array(
			'name'              => _x( 'Tags', 'dora' ),
			'singular_name'     => _x( 'Tags', 'booking-and-rental-manager-for-woocommerce' ),
			'search_items'      => __( 'Search Tags' ),
			'all_items'         => __( 'All Tags' ),
			'parent_item'       => __( 'Parent Tag' ),
			'parent_item_colon' => __( 'Parent Tag:' ),
			'edit_item'         => __( 'Edit Tag' ),
			'update_item'       => __( 'Update Tag' ),
			'add_new_item'      => __( 'Add New Tag' ),
			'new_item_name'     => __( 'New Tag Name' ),
			'menu_name'         => __( 'Tags' ),
		);
// 		register_taxonomy( 'rbfw_item_tag', array( 'rbfw_item' ), array(
// 			'hierarchical'      => false,
// 			'labels'            => $labels,
// 			'show_ui'           => true,
// 			'show_in_rest'      => true,
// 			'show_admin_column' => true,
// 			'query_var'         => true,
// 			'rewrite'           => array( 'slug' => 'rbfw_item_tag' ),
// 		) );
	}
	add_action( 'init', 'rbfw_create_tag_taxonomy', 0 );
	if ( ! function_exists( 'mep_esc_html' ) ) {
		function mep_esc_html( $string ) {
			$allow_attr = array(
				'input'    => array(
					'br'                 => [],
					'type'               => [],
					'class'              => [],
					'id'                 => [],
					'name'               => [],
					'value'              => [],
					'size'               => [],
					'placeholder'        => [],
					'min'                => [],
					'max'                => [],
					'checked'            => [],
					'required'           => [],
					'disabled'           => [],
					'readonly'           => [],
					'step'               => [],
					'data-default-color' => [],
				),
				'p'        => [
					'class' => []
				],
				'img'      => [
					'class' => [],
					'id'    => [],
					'src'   => [],
					'alt'   => [],
				],
				'fieldset' => [
					'class' => []
				],
				'label'    => [
					'for'   => [],
					'class' => []
				],
				'select'   => [
					'class' => [],
					'name'  => [],
					'id'    => [],
				],
				'option'   => [
					'class'    => [],
					'value'    => [],
					'id'       => [],
					'selected' => [],
				],
				'textarea' => [
					'class' => [],
					'rows'  => [],
					'id'    => [],
					'cols'  => [],
					'name'  => [],
				],
				'h2'       => [ 'class' => [], 'id' => [], ],
				'a'        => [ 'class' => [], 'id' => [], 'href' => [], ],
				'div'      => [ 'class' => [], 'id' => [], 'data' => [], ],
				'span'     => [
					'class' => [],
					'id'    => [],
					'data'  => [],
				],
				'i'        => [
					'class' => [],
					'id'    => [],
					'data'  => [],
				],
				'table'    => [
					'class' => [],
					'id'    => [],
					'data'  => [],
				],
				'tr'       => [
					'class' => [],
					'id'    => [],
					'data'  => [],
				],
				'td'       => [
					'class' => [],
					'id'    => [],
					'data'  => [],
				],
				'thead'    => [
					'class' => [],
					'id'    => [],
					'data'  => [],
				],
				'tbody'    => [
					'class' => [],
					'id'    => [],
					'data'  => [],
				],
				'th'       => [
					'class' => [],
					'id'    => [],
					'data'  => [],
				],
				'svg'      => [
					'class'   => [],
					'id'      => [],
					'width'   => [],
					'height'  => [],
					'viewBox' => [],
					'xmlns'   => [],
				],
				'g'        => [
					'fill' => [],
				],
				'path'     => [
					'd' => [],
				],
				'br'       => array(),
				'em'       => array(),
				'strong'   => array(),
			);

			return wp_kses( $string, $allow_attr );
		}
	}
	if ( ! function_exists( 'rbfw_field_generator' ) ) {
		function rbfw_field_generator( $type, $option ) {
			$FormFieldsGenerator = new RbfwFormFieldsGenerator();
			if ( $type === 'text' ) {
				return $FormFieldsGenerator->field_text( $option );
			} elseif ( $type === 'text_multi' ) {
				return $FormFieldsGenerator->field_text_multi( $option );
			} elseif ( $type === 'textarea' ) {
				return $FormFieldsGenerator->field_textarea( $option );
			} elseif ( $type === 'checkbox' ) {
				return $FormFieldsGenerator->field_checkbox( $option );
			} elseif ( $type === 'checkbox_multi' ) {
				return $FormFieldsGenerator->field_checkbox_multi( $option );
			} elseif ( $type === 'radio' ) {
				return $FormFieldsGenerator->field_radio( $option );
			} elseif ( $type === 'select' ) {
				return $FormFieldsGenerator->field_select( $option );
			} elseif ( $type === 'range' ) {
				return $FormFieldsGenerator->field_range( $option );
			} elseif ( $type === 'range_input' ) {
				return $FormFieldsGenerator->field_range_input( $option );
			} elseif ( $type === 'switch' ) {
				return $FormFieldsGenerator->field_switch( $option );
			} elseif ( $type === 'switch_multi' ) {
				return $FormFieldsGenerator->field_switch_multi( $option );
			} elseif ( $type === 'switch_img' ) {
				return $FormFieldsGenerator->field_switch_img( $option );
			} elseif ( $type === 'time_format' ) {
				return $FormFieldsGenerator->field_time_format( $option );
			} elseif ( $type === 'date_format' ) {
				return $FormFieldsGenerator->field_date_format( $option );
			} elseif ( $type === 'datepicker' ) {
				return $FormFieldsGenerator->field_datepicker( $option );
			} elseif ( $type === 'color_sets' ) {
				return $FormFieldsGenerator->field_color_sets( $option );
			} elseif ( $type === 'colorpicker' ) {
				return $FormFieldsGenerator->field_colorpicker( $option );
			} elseif ( $type === 'colorpicker_multi' ) {
				return $FormFieldsGenerator->field_colorpicker_multi( $option );
			} elseif ( $type === 'link_color' ) {
				return $FormFieldsGenerator->field_link_color( $option );
			} elseif ( $type === 'icon' ) {
				return $FormFieldsGenerator->field_icon( $option );
			} elseif ( $type === 'icon_multi' ) {
				return $FormFieldsGenerator->field_icon_multi( $option );
			} elseif ( $type === 'dimensions' ) {
				return $FormFieldsGenerator->field_dimensions( $option );
			} elseif ( $type === 'wp_editor' ) {
				return $FormFieldsGenerator->field_wp_editor( $option );
			} elseif ( $type === 'select2' ) {
				return $FormFieldsGenerator->field_select2( $option );
			} elseif ( $type === 'faq' ) {
				return $FormFieldsGenerator->field_faq( $option );
			} elseif ( $type === 'grid' ) {
				return $FormFieldsGenerator->field_grid( $option );
			} elseif ( $type === 'color_palette' ) {
				return $FormFieldsGenerator->field_color_palette( $option );
			} elseif ( $type === 'color_palette_multi' ) {
				return $FormFieldsGenerator->field_color_palette_multi( $option );
			} elseif ( $type === 'media' ) {
				return $FormFieldsGenerator->field_media( $option );
			} elseif ( $type === 'media_multi' ) {
				return $FormFieldsGenerator->field_media_multi( $option );
			} elseif ( $type === 'repeatable' ) {
				return $FormFieldsGenerator->field_repeatable( $option );
			} elseif ( $type === 'user' ) {
				return $FormFieldsGenerator->field_user( $option );
			} elseif ( $type === 'margin' ) {
				return $FormFieldsGenerator->field_margin( $option );
			} elseif ( $type === 'padding' ) {
				return $FormFieldsGenerator->field_padding( $option );
			} elseif ( $type === 'border' ) {
				return $FormFieldsGenerator->field_border( $option );
			} elseif ( $type === 'switcher' ) {
				return $FormFieldsGenerator->field_switcher( $option );
			} elseif ( $type === 'password' ) {
				return $FormFieldsGenerator->field_password( $option );
			} elseif ( $type === 'post_objects' ) {
				return $FormFieldsGenerator->field_post_objects( $option );
			} elseif ( $type === 'google_map' ) {
				return $FormFieldsGenerator->field_google_map( $option );
			} elseif ( $type === 'image_link' ) {
				return $FormFieldsGenerator->field_image_link( $option );
			} elseif ( $type === 'number' ) {
				return $FormFieldsGenerator->field_number( $option );
			}elseif ( $type === 'time_slot' ) {
				return $FormFieldsGenerator->field_time_slot( $option );
			}elseif (  $type === 'add_to_cart_shortcode' ) {
				return $FormFieldsGenerator->field_add_to_cart_shortcode( $option );
			} else {
				return '';
			}
		}
	}
	if ( ! function_exists( 'mage_array_strip' ) ) {
		function mage_array_strip( $array_or_string ) {
			if ( is_string( $array_or_string ) ) {
				$array_or_string = sanitize_text_field( htmlentities( nl2br( $array_or_string ) ) );
			} elseif ( is_array( $array_or_string ) ) {
				foreach ( $array_or_string as $key => &$value ) {
					if ( is_array( $value ) ) {
						$value = mage_array_strip( $value );
					} else {
						$value = sanitize_text_field( htmlentities( nl2br( $value ) ) );
					}
				}
			}

			return $array_or_string;
		}
	}

/************************
* GET RENT FAQ's Content
*************************/
add_action('rbfw_the_faq_only','rbfw_get_faq_func');
function rbfw_get_faq_func($post_id){
	$rbfw_faq_arr 		= get_post_meta( $post_id, 'mep_event_faq', true );

	if(! empty($rbfw_faq_arr)){

	$rbfw_faq_title 	= array_column($rbfw_faq_arr, 'rbfw_faq_title');
	$rbfw_faq_img 		= array_column($rbfw_faq_arr, 'rbfw_faq_img');
	$rbfw_faq_content 	= array_column($rbfw_faq_arr, 'rbfw_faq_content');
	$count_faq_arr 		= count($rbfw_faq_arr);
?>
	<div id="rbfw_faq_accordion">
	<?php for ($x = 0; $x < $count_faq_arr; $x++) { ?>
	<?php if(! empty($rbfw_faq_title[$x])): ?>
	<h3 class="rbfw_faq_header"><?php echo esc_html($rbfw_faq_title[$x]); ?></h3>
	<?php endif; ?>
	<div class="rbfw_faq_content_wrapper">
		<div class="rbfw_faq_img"> 
		<?php
		if(! empty($rbfw_faq_img[$x])):
			$rbfw_img_id_arr = explode (",", $rbfw_faq_img[$x]);
			foreach ($rbfw_img_id_arr as $attachment_id) {
				$url = wp_get_attachment_url( $attachment_id );
				echo '<img src="'.esc_url($url).'"/>';
			}
		endif;
		?>
		</div>
		<p class="rbfw_faq_desc">
		<?php
		if(! empty($rbfw_faq_content[$x])):
			echo esc_html($rbfw_faq_content[$x]); 
		endif;
		?>
		</p>
	</div>
	<?php } ?>  
	</div>
	<script>
	jQuery( function() {
		jQuery( "#rbfw_faq_accordion" ).accordion({
			heightStyle: "content"
		});
	} );
	</script>
<?php
	}
}

// Post Share meta function
add_action('rbfw_product_meta','rbfw_post_share_meta');
function rbfw_post_share_meta($post_id){
	// Get current post URL 
	$rbfwURL = urlencode(get_permalink());

	// Get current post title
	$rbfwTitle = htmlspecialchars(urlencode(html_entity_decode(get_the_title(), ENT_COMPAT, 'UTF-8')), ENT_COMPAT, 'UTF-8');
	// $rbfwTitle = str_replace( ' ', '%20', get_the_title());
	
	// Get Post Thumbnail for pinterest
	$rbfwThumbnail = wp_get_attachment_image_src( get_post_thumbnail_id( $post_id ), 'full' );
	
	$twitter_username='';
	
	// sharing URL 
	$twitterURL = 'https://twitter.com/intent/tweet?text='.$rbfwTitle.'&amp;url='.$rbfwURL.'&amp;via='.$twitter_username;
	$facebookURL = 'https://www.facebook.com/sharer/sharer.php?u='.$rbfwURL;
	$linkedInURL = 'https://www.linkedin.com/shareArticle?mini=true&url='.$rbfwURL.'&amp;title='.$rbfwTitle;
	$pinterestURL = 'https://www.pinterest.com/pin/create/button/?url='.$rbfwURL.'&amp;media='; if($rbfwThumbnail){ $pinterestURL.= $rbfwThumbnail[0]; } $pinterestURL.='&amp;description='.$rbfwTitle;?>
		<div class="rbfw-post-sharing">
			<a href="<?php echo esc_url($facebookURL);?>">
				<i class="fab fa-facebook-f"></i>
			</a>
			<a href="<?php echo esc_url($twitterURL);?>">
				<i class="fab fa-twitter"></i>
			</a>
			<a href="<?php echo esc_url($pinterestURL);?>">
				<i class="fab fa-pinterest-p"></i>
			</a>
			<a href="<?php echo esc_url($linkedInURL);?>">
				<i class="fab fa-linkedin"></i>
			</a>		  
		</div>	
<?php	
}

// Related products function
add_action('rbfw_related_products','rbfw_related_products');
function rbfw_related_products($post_id){
	global $rbfw;
	$rbfw_related_post_arr = get_post_meta( $post_id, 'rbfw_releted_rbfw', true ) ? maybe_unserialize(get_post_meta( $post_id, 'rbfw_releted_rbfw', true )) : array();
	$hourly_rate_label = $rbfw->get_option('rbfw_text_hourly_rate', 'rbfw_basic_translation_settings', __('Hourly rate','booking-and-rental-manager-for-woocommerce'));
	$prices_start_at = $rbfw->get_option('rbfw_text_prices_start_at', 'rbfw_basic_translation_settings', __('Prices start at','booking-and-rental-manager-for-woocommerce'));

	if(isset($rbfw_related_post_arr) && ! empty($rbfw_related_post_arr)){
	?>
		<div class="rbfw-related-product-heading"><?php rbfw_string('rbfw_text_related_products',__('Related products','booking-and-rental-manager-for-woocommerce')); ?></div>
		<hr>
		<div class="owl-carousel rbfw-related-product">
			<?php foreach ($rbfw_related_post_arr as $rbfw_related_post_id) {
			$thumb_url  = !empty(get_the_post_thumbnail_url( $rbfw_related_post_id, 'full' )) ? get_the_post_thumbnail_url( $rbfw_related_post_id, 'full' ) : RBFW_PLUGIN_URL. '/assets/images/no_image.png';	
			$title = get_the_title($rbfw_related_post_id);

			$price = get_post_meta($rbfw_related_post_id, 'rbfw_hourly_rate', true) ? get_post_meta($rbfw_related_post_id, 'rbfw_hourly_rate', true) : 0;

			// sunday rate
			$price_sun = get_post_meta($rbfw_related_post_id, 'rbfw_sun_hourly_rate', true) ? get_post_meta($rbfw_related_post_id, 'rbfw_sun_hourly_rate', true) : 0;
			$enabled_sun = get_post_meta($rbfw_related_post_id, 'rbfw_enable_sun_day', true) ? get_post_meta($rbfw_related_post_id, 'rbfw_enable_sun_day', true) : 'yes';

			// monday rate
			$price_mon = get_post_meta($rbfw_related_post_id, 'rbfw_mon_hourly_rate', true) ? get_post_meta($rbfw_related_post_id, 'rbfw_mon_hourly_rate', true) : 0;
			$enabled_mon = get_post_meta($rbfw_related_post_id, 'rbfw_enable_mon_day', true) ? get_post_meta($rbfw_related_post_id, 'rbfw_enable_mon_day', true) : 'yes';

			// tuesday rate
			$price_tue = get_post_meta($rbfw_related_post_id, 'rbfw_tue_hourly_rate', true) ? get_post_meta($rbfw_related_post_id, 'rbfw_tue_hourly_rate', true) : 0;
			$enabled_tue = get_post_meta($rbfw_related_post_id, 'rbfw_enable_tue_day', true) ? get_post_meta($rbfw_related_post_id, 'rbfw_enable_tue_day', true) : 'yes';

			// wednesday rate
			$price_wed = get_post_meta($rbfw_related_post_id, 'rbfw_wed_hourly_rate', true) ? get_post_meta($rbfw_related_post_id, 'rbfw_wed_hourly_rate', true) : 0;
			$enabled_wed = get_post_meta($rbfw_related_post_id, 'rbfw_enable_wed_day', true) ? get_post_meta($rbfw_related_post_id, 'rbfw_enable_wed_day', true) : 'yes';

			// thursday rate
			$price_thu = get_post_meta($rbfw_related_post_id, 'rbfw_thu_hourly_rate', true) ? get_post_meta($rbfw_related_post_id, 'rbfw_thu_hourly_rate', true) : 0;
			$enabled_thu = get_post_meta($rbfw_related_post_id, 'rbfw_enable_thu_day', true) ? get_post_meta($rbfw_related_post_id, 'rbfw_enable_thu_day', true) : 'yes';

			// friday rate
			$price_fri = get_post_meta($rbfw_related_post_id, 'rbfw_fri_hourly_rate', true) ? get_post_meta($rbfw_related_post_id, 'rbfw_fri_hourly_rate', true) : 0;
			$enabled_fri = get_post_meta($rbfw_related_post_id, 'rbfw_enable_fri_day', true) ? get_post_meta($rbfw_related_post_id, 'rbfw_enable_fri_day', true) : 'yes';	

			// saturday rate
			$price_sat = get_post_meta($rbfw_related_post_id, 'rbfw_sat_hourly_rate', true) ? get_post_meta($rbfw_related_post_id, 'rbfw_sat_hourly_rate', true) : 0;
			$enabled_sat = get_post_meta($rbfw_related_post_id, 'rbfw_enable_sat_day', true) ? get_post_meta($rbfw_related_post_id, 'rbfw_enable_sat_day', true) : 'yes';

			$current_day = date('D');

			if($current_day == 'Sun' && $enabled_sun == 'yes'){
				$price = $price_sun;
			}elseif($current_day == 'Mon' && $enabled_mon == 'yes'){
				$price = $price_mon;
			}elseif($current_day == 'Tue' && $enabled_tue == 'yes'){
				$price = $price_tue;
			}elseif($current_day == 'Wed' && $enabled_wed == 'yes'){
				$price = $price_wed;
			}elseif($current_day == 'Thu' && $enabled_thu == 'yes'){
				$price = $price_thu;
			}elseif($current_day == 'Fri' && $enabled_fri == 'yes'){
				$price = $price_fri;
			}elseif($current_day == 'Sat' && $enabled_sat == 'yes'){
				$price = $price_sat;
			}else{
				$price = $price;	
			}

			$current_date = date('Y-m-d');
			$rbfw_sp_prices = get_post_meta( $rbfw_related_post_id, 'rbfw_seasonal_prices', true );
			if(!empty($rbfw_sp_prices)){
				$sp_array = [];
				$i = 0;
				foreach ($rbfw_sp_prices as $value) {
					$rbfw_sp_start_date = $value['rbfw_sp_start_date'];
					$rbfw_sp_end_date 	= $value['rbfw_sp_end_date'];
					$rbfw_sp_price_h 	= $value['rbfw_sp_price_h'];
					$rbfw_sp_price_d 	= $value['rbfw_sp_price_d'];
					$sp_array[$i]['sp_dates'] = rbfw_getBetweenDates($rbfw_sp_start_date, $rbfw_sp_end_date);
					$sp_array[$i]['sp_hourly_rate'] = $rbfw_sp_price_h;
					$sp_array[$i]['sp_daily_rate']  = $rbfw_sp_price_d;
					$i++;
				}

				foreach ($sp_array as $sp_arr) {
					if (in_array($current_date,$sp_arr['sp_dates'])){
						$price = $sp_arr['sp_hourly_rate'];
					}
				}
			}
			$permalink = get_the_permalink($rbfw_related_post_id);
			$rbfw_rent_type = get_post_meta( $rbfw_related_post_id, 'rbfw_item_type', true );

			$rbfw_room_data = get_post_meta( $rbfw_related_post_id, 'rbfw_resort_room_data', true );
			if(!empty($rbfw_room_data)):
			$rbfw_daylong_rate = array();
			$rbfw_daynight_rate = array();
			foreach ($rbfw_room_data as $key => $value) {
				$rbfw_daylong_rate[] = $value['rbfw_room_daylong_rate'];
				$rbfw_daynight_rate[] = $value['rbfw_room_daynight_rate'];
			}
			$merged_arr = array_merge($rbfw_daylong_rate,$rbfw_daynight_rate);
			$smallest_price = min($merged_arr);
			endif;
			?>

			<div class="item">
				<div class="rbfw-related-product-thumb-wrap"><a href="<?php echo esc_url($permalink); ?>"><div class="rbfw-related-product-thumb"><img src="<?php echo esc_url($thumb_url); ?>" alt="<?php esc_attr_e('Featured Image','booking-and-rental-manager-for-woocommerce'); ?>"></div></a></div>
				<div class="rbfw-related-product-title-wrap"><a href="<?php echo esc_url($permalink); ?>"><?php echo esc_html($title); ?></a></div>
				<?php if($rbfw_rent_type != 'resort' && $rbfw_rent_type != 'bike_car_sd' && $rbfw_item_type != 'appointment'): ?>
				<div class="rbfw-related-product-price-wrap"><?php echo esc_html($hourly_rate_label); ?>: <?php echo rbfw_mps_price($price); ?></div>
				<?php endif; ?>
				<?php if($rbfw_rent_type == 'resort' && !empty($rbfw_room_data)): ?>
				<div class="rbfw-related-product-price-wrap"><?php echo esc_html($prices_start_at); ?>: <?php echo rbfw_mps_price($smallest_price); ?></div>
				<?php endif; ?>				
				<div class="rbfw-related-product-btn-wrap"><a href="<?php echo esc_url($permalink); ?>" class="rbfw-related-product-btn"><?php rbfw_string('rbfw_text_read_more',__('Read More','booking-and-rental-manager-for-woocommerce')); ?></a></div>
			</div>
			<?php } ?>
		</div>
		<script>
			jQuery(document).ready(function(){
				jQuery(".owl-carousel.rbfw-related-product").owlCarousel({
					loop:true,
					margin:10,
					responsiveClass:true,
					responsive:{
						0:{
							items:1,
							nav:true
						},
						600:{
							items:3,
							nav:false
						},
						1000:{
							items:3,
							nav:true,
							loop:true
						}
					}
				});
			});		
		</script>
<?php
	}
}

add_action('wp_footer','rbfw_footer_scripts');
function rbfw_footer_scripts(){
	global $rbfw;
	global $post;

	$post_id = !empty($post->ID) ? $post->ID : '';

	if(empty($post_id)){
		return;
	}
	
	$post_type = get_post_type($post_id);

	if($post_type == 'rbfw_item'){
	?>
		<script>
		jQuery(document).ready(function(){

			// tab tooltip
			let highlighted_features 	= "<?php echo esc_html($rbfw->get_option('rbfw_text_hightlighted_features', 'rbfw_basic_translation_settings', __('Highlighted Features','booking-and-rental-manager-for-woocommerce'))); ?>";
			let description 			= "<?php echo esc_html($rbfw->get_option('rbfw_text_description', 'rbfw_basic_translation_settings', __('Description','booking-and-rental-manager-for-woocommerce'))); ?>";
			let faq 					= "<?php echo esc_html($rbfw->get_option('rbfw_text_faq', 'rbfw_basic_translation_settings', __('Frequently Asked Questions','booking-and-rental-manager-for-woocommerce'))); ?>";
			let reviews 				= "<?php echo esc_html($rbfw->get_option('rbfw_text_reviews', 'rbfw_basic_translation_settings', __('Reviews','booking-and-rental-manager-for-woocommerce'))); ?>";
			tippy('.rbfw-features', {content: highlighted_features,theme: 'blue',placement: 'right'});
			tippy('.rbfw-description', {content: description,theme: 'blue',placement: 'right'});
			tippy('.rbfw-faq', {content: faq,theme: 'blue',placement: 'right'});
			tippy('.rbfw-review', {content: reviews,theme: 'blue',placement: 'right'});
			// end tab tooltip
		});
		</script>	
	<?php
	}
}

add_action('admin_footer','rbfw_footer_admin_scripts');
function rbfw_footer_admin_scripts(){
    
	$icon_library = new rbfw_icon_library();
	$icon_library_list = $icon_library->rbfw_fontawesome_icons();
	?>
	<script>
		jQuery(document).ready(function(){

					// Features Icon Popup
					jQuery('.rbfw_feature_icon_btn').click(function (e) { 
						let remove_exist_data_key 	= jQuery("#rbfw_features_icon_list_wrapper").removeAttr('data-key');
						let remove_active_label 	= jQuery('#rbfw_features_icon_list_wrapper label').removeClass('selected');
						let data_key 				= jQuery(this).attr('data-key');
						jQuery('#rbfw_features_search_icon').val('');
						jQuery('.rbfw_features_icon_list_body label').show();
						jQuery("#rbfw_features_icon_list_wrapper").attr('data-key', data_key);
						jQuery("#rbfw_features_icon_list_wrapper").mage_modal({
							escapeClose: false,
							clickClose: false,
							showClose: false
						});

						// Selected Feature Icon Action
						jQuery('#rbfw_features_icon_list_wrapper label').click(function (e) {
							e.stopImmediatePropagation();
							let selected_label 		= jQuery(this);
							let selected_val 		= jQuery('input', this).val();
							let selected_data_key 	= jQuery("#rbfw_features_icon_list_wrapper").attr('data-key');
							
							jQuery('#rbfw_features_icon_list_wrapper label').removeClass('selected');
							jQuery('.rbfw_feature_icon_preview[data-key="'+selected_data_key+'"]').empty();
							jQuery(selected_label).addClass('selected');
							jQuery('.rbfw_feature_icon[data-key="'+selected_data_key+'"]').val(selected_val);
							jQuery('.rbfw_feature_icon_preview[data-key="'+selected_data_key+'"]').append('<i class="'+selected_val+'"></i>');
						});				
					});
					// End Features Icon Popup

					// Icon Filter 
					jQuery('#rbfw_features_search_icon').keyup(function (e) { 
						let value = jQuery(this).val().toLowerCase();
						jQuery(".rbfw_features_icon_list_body label[data-id]").show().filter(function() {
							jQuery(this).toggle(jQuery(this).attr('data-id').toLowerCase().indexOf(value) > -1)
						}).hide();
					});
					// End Icon Filter
		});	
	</script>
	<div id="rbfw_features_icon_list_wrapper" class="mage_modal">
	<div class="rbfw_features_icon_list_header">
		<div class="rbfw_features_icon_list_header_group">
			<a href="#rbfw_features_icon_list_wrapper" rel="mage_modal:close" class="rbfw_feature_icon_list_close_button"><?php esc_html_e('Close','booking-and-rental-manager-for-woocommerce'); ?></a>
		</div>    
		<div class="rbfw_features_icon_list_header_group">
			<input type="text" id="rbfw_features_search_icon" placeholder="<?php esc_attr_e('Search Icon...','booking-and-rental-manager-for-woocommerce'); ?>"> 
		</div>
	</div>
	<hr>
	<div class="rbfw_features_icon_list_body">	
		<?php 
		foreach ($icon_library_list as $key => $value) {
			$input_id = str_replace(' ', '', $key);
			?>
			<label for="<?php echo $input_id; ?>" data-id="<?php echo $value; ?>">
			<input type="radio" name="rbfw_icon" id="<?php echo $input_id; ?>" value="<?php echo $key; ?>">
			<i class="<?php echo $key; ?>"></i>
			</label> 
			<?php
		}
		?>
	</div>	
	</div>
	<style>
		#rbfw_features_icon_list_wrapper.mage_modal{
			display: none;
		}
	</style>
	<?php
}

/*******************************************
 * Remove the template meta box from sidebar
 *******************************************/
add_action( 'do_meta_boxes', 'rbfw_remove_template_meta_box' );
function rbfw_remove_template_meta_box()
{
    $custom_post_type = 'rbfw_item';
     
    remove_meta_box('rbfw_list_thumbnail_meta_boxes', $custom_post_type, 'side' );
}

/*******************************************
 * Get Between Dates function
 *******************************************/
function rbfw_getBetweenDates($startDate, $endDate)
{

    $rangArray = [];
    $startDate = strtotime($startDate);
    $endDate = strtotime($endDate);

    for ($currentDate = $startDate; $currentDate <= $endDate; 

        $currentDate += (86400)) {
                                      
        $date = date('Y-m-d', $currentDate);

        $rangArray[] = $date;

    }
    return $rangArray;
}

add_filter( 'rbfw_settings_sec_reg', 'rbfw_free_settings_sec', 100 );
function rbfw_free_settings_sec( $default_sec ) {
	$sections = array(	
		array(
			'id'    => 'rbfw_license_settings',
			'title' => '<i class="fa-solid fa-address-card"></i>' . __( 'License', 'booking-and-rental-manager-for-woocommerce' )
		),
	);
	return array_merge( $default_sec, $sections );
}

add_action('wsa_form_bottom_rbfw_license_settings', 'rbfw_licensing_page', 5);
function rbfw_licensing_page($form) {
    ?>
    <div class='mep-licensing-page'>
        <h3><?php esc_html_e( 'Booking and Rental Manager Licensing', 'booking-and-rental-manager-for-woocommerce' ); ?></h3>
        <p><?php esc_html_e( 'Thanks you for using our Booking and Rental Manager plugin. This plugin is free and no license is required. We have some additional addons to enhance features of this plugin functionality. If you have any addon you need to enter a valid license for that plugin below.', 'booking-and-rental-manager-for-woocommerce' ); ?></p>

        <div class="mep_licensae_info"></div>
        <table class='wp-list-table widefat striped posts mep-licensing-table'>
            <thead>
            <tr>
                <th><?php esc_html_e( 'Plugin Name', 'booking-and-rental-manager-for-woocommerce' ); ?></th>
                <th width=10%><?php esc_html_e( 'Order No', 'booking-and-rental-manager-for-woocommerce' ); ?></th>
                <th width=15%><?php esc_html_e( 'Expire on', 'booking-and-rental-manager-for-woocommerce' ); ?></th>
                <th width=30%><?php esc_html_e( 'License Key', 'booking-and-rental-manager-for-woocommerce' ); ?></th>
                <th width=10%><?php esc_html_e( 'Status', 'booking-and-rental-manager-for-woocommerce' ); ?></th>
                <th width=10%><?php esc_html_e( 'Action', 'booking-and-rental-manager-for-woocommerce' ); ?></th>
            </tr>
            </thead>
            <tbody>
            <?php do_action('rbfw_license_page_addon_list'); ?>
            </tbody>
        </table>
    </div>
    <?php
}

if (!function_exists('mep_license_expire_date')) {
	function mep_license_expire_date($date) {
		if (empty($date) || $date == 'lifetime') {
			echo esc_html($date);
		} else {
			if (strtotime(current_time('Y-m-d H:i')) < strtotime(date('Y-m-d H:i', strtotime($date)))) {
				echo rbfw_get_datetime($date, 'date-time-text');
			} else {
				esc_html_e('Expired', 'booking-and-rental-manager-for-woocommerce');
			}
		}
	}
	}

// Date Format Converter
function rbfw_date_format($date){
	if(empty($date)){
		return;
	}
	$date_to_string = new DateTime($date);
	$result = $date_to_string->format('F j, Y');
	return $result;
}

// Remove element for rbfw_order post type
add_action( 'admin_head', 'rbfw_post_type_css');
function rbfw_post_type_css(){
	$current_queried_post_type = get_post_type( get_queried_object_id() );
	if ('rbfw_order' == $current_queried_post_type ) {
		echo '<style>#minor-publishing{display:none;}</style>';
		echo '<script>jQuery(document).ready(function(){ jQuery("#minor-publishing").remove(); });</script>';
	}
}

// Get rent type label by slug
function rbfw_get_type_label($slug){
	switch ($slug) {
		case 'bike_car_sd':
		  	return 'Bike/Car for single day';
		  	break;
		case 'bike_car_md':
			return 'Bike/Car for multiple day';
		  	break;
		case 'resort':
			return 'Resort';
		  	break;
		case 'equipment':
			return 'Equipment';
			break;
		case 'dress':
			return 'Dress';
			break;
		case 'appointment':
			return 'Appointment';
			break;	
		case 'others':
			return 'Others';
			break;										
		default:
		  	return;
	  }
}

// Check WooCommerce Integration addon is installed
function rbfw_payment_systems(){

	$ps = array(
		'mps' => 'Mage Payment System'
	);

	return apply_filters('rbfw_payment_systems', $ps);
}

// get payment gateways
function rbfw_get_payment_gateways(){

	$pg = array(
		'offline' => 'Offline Payment',
	);

	return apply_filters('rbfw_payment_gateways', $pg);
}

// global settings payment system css
add_action( 'admin_head', 'rbfw_payment_systems_css');
function rbfw_payment_systems_css(){
	$current_payment_system = rbfw_get_option( 'rbfw_payment_system', 'rbfw_basic_payment_settings');
	if ('wps' == $current_payment_system ) {
		echo '<style>tr.rbfw_mps_currency,tr.rbfw_mps_currency_position,tr.rbfw_mps_currency_decimal_number,tr.rbfw_mps_checkout_account,tr.rbfw_mps_payment_gateway,tr.rbfw_mps_payment_gateway_environment,tr.rbfw_mps_paypal_heading,tr.rbfw_mps_paypal_account_email,tr.rbfw_mps_paypal_api_username,tr.rbfw_mps_paypal_api_password,tr.rbfw_mps_paypal_api_signature,tr.rbfw_mps_paypal_ipn_handler,tr.rbfw_mps_stripe_heading,tr.rbfw_mps_stripe_publishable_key,tr.rbfw_mps_stripe_secret_key,tr.rbfw_mps_stripe_webhook,tr.rbfw_mps_paypal_client_id,tr.rbfw_mps_paypal_secret_key,tr.rbfw_mps_stripe_postal_field,tr.rbfw_mps_tax_switch,tr.rbfw_mps_tax_format{display:none;}</style>';
	} else {
		echo '<style>tr.rbfw_wps_add_to_cart_redirect{display:none;}</style>';
	}
}

// get page list array
function rbfw_get_pages_arr() {
	$pages = get_pages(); 
	$arr   = array(
		'' => __( 'Please Select a Page', 'booking-and-rental-manager-for-woocommerce' )
	);
	foreach ( $pages as $page ) {
		$arr[ $page->ID ] = $page->post_title;
	}

	return $arr;
}

add_filter( 'rbfw_settings_field', 'rbfw_payment_settings_fields', 10 );
function rbfw_payment_settings_fields($settings_fields){
	$settings_fields['rbfw_basic_payment_settings'] = array(

			array(
				'name' => 'rbfw_payment_system',
				'label' => __( 'Payment System', 'booking-and-rental-manager-for-woocommerce' ),
				'desc' => __( '', 'booking-and-rental-manager-for-woocommerce' ),
				'class' => 'rbfw_payment_system',
				'type' => 'select',
				'default' => 'mps',
				'options' => rbfw_payment_systems(),
			),
			array(
				'name'    => 'rbfw_mps_currency',
				'label'   => __( 'Currency', 'booking-and-rental-manager-for-woocommerce' ),
				'desc'    => __( 'Please choose the currency if mage payment system is enabled.', 'booking-and-rental-manager-for-woocommerce' ),
				'type' => 'select',
				'default' => 'USD',
				'options' => rbfw_mps_currency_list(),
			),
			array(
				'name' => 'rbfw_mps_currency_position',
				'label' => __( 'Currency position', 'booking-and-rental-manager-for-woocommerce' ),
				'desc' => __( 'This controls the position of the currency symbol if mage payment system is enabled.', 'booking-and-rental-manager-for-woocommerce' ),
				'type' => 'select',
				'default' => 'left',
				'options' => array(
					'left' => 'Left',
					'right'  => 'Right',
					'left_space'  => 'Left with space',
					'right_space'  => 'Right with space'
				),
			),
			array(
				'name'    => 'rbfw_mps_currency_decimal_number',
				'label'   => __( 'Number of decimals', 'booking-and-rental-manager-for-woocommerce' ),
				'desc'    => __( 'This sets the number of decimal points shown in displayed prices. It will work if mage payment system is enabled.', 'booking-and-rental-manager-for-woocommerce' ),
				'type'    => 'number',				
				'default' => '2',
			),
			array(
				'name' => 'rbfw_mps_tax_switch',
				'label' => __( 'Enable taxes', 'booking-and-rental-manager-for-woocommerce' ),
				'desc' => __( ' Enable tax rates and calculations. Rates will be configurable and taxes will be calculated during checkout.', 'booking-and-rental-manager-for-woocommerce' ),
				'type' => 'checkbox',
				'default' => 'off',
			),
			array(
				'name'      => 'rbfw_mps_tax_format',
				'label'   => __( 'Display prices during checkout', 'booking-and-rental-manager-for-woocommerce' ),
				'desc' => __( 'Please select the tax format.', 'booking-and-rental-manager-for-woocommerce' ),
				'type'    => 'select',
				'default' => 'excluding_tax',
				'options'    => array(
					'excluding_tax' => __( 'Excluding tax', 'booking-and-rental-manager-for-woocommerce' ),
					'including_tax' => __( 'Including tax', 'booking-and-rental-manager-for-woocommerce' ),
				
				),
			),
			array(
				'name' => 'rbfw_mps_checkout_account',
				'label' => __( 'Account creation', 'booking-and-rental-manager-for-woocommerce' ),
				'desc' => __( 'Allow customers to create an account during checkout.', 'booking-and-rental-manager-for-woocommerce' ),
				'type' => 'checkbox',
				'default' => 'on',
			),					
			array(
				'name' => 'rbfw_mps_payment_gateway',
				'label' => __( 'Payment Gateway', 'booking-and-rental-manager-for-woocommerce' ),
				'desc' => __( '', 'booking-and-rental-manager-for-woocommerce' ),
				'type' => 'multicheck',
				'default' => 'offline',
				'options' => rbfw_get_payment_gateways()
			),
	);

	return apply_filters('rbfw_payment_settings_fields', $settings_fields);
}

// Update Settings On Register the Plugin
function rbfw_update_settings(){
	$payment_settings = maybe_unserialize('a:6:{s:19:"rbfw_payment_system";s:3:"mps";s:17:"rbfw_mps_currency";s:3:"USD";s:26:"rbfw_mps_currency_position";s:4:"left";s:32:"rbfw_mps_currency_decimal_number";s:1:"2";s:25:"rbfw_mps_checkout_account";s:2:"on";s:24:"rbfw_mps_payment_gateway";a:1:{s:7:"offline";s:7:"offline";}}');

	if (get_option('rbfw_basic_payment_settings') === false) {
 
		update_option('rbfw_basic_payment_settings', $payment_settings);
	
	}

	$pdf_settings = maybe_unserialize('a:9:{s:13:"rbfw_send_pdf";s:3:"yes";s:13:"rbfw_pdf_logo";s:0:"";s:11:"rbfw_pdf_bg";s:0:"";s:16:"rbfw_pdf_address";s:0:"";s:14:"rbfw_pdf_phone";s:0:"";s:17:"rbfw_pdf_tc_title";s:0:"";s:16:"rbfw_pdf_tc_text";s:0:"";s:17:"rbfw_pdf_bg_color";s:0:"";s:19:"rbfw_pdf_text_color";s:0:"";}');

	if (get_option('rbfw_basic_pdf_settings') === false) {
 
		update_option('rbfw_basic_pdf_settings', $pdf_settings);
	
	}
}


// Check pro plugin active
function rbfw_check_pro_active(){
	if (is_plugin_active( 'booking-and-rental-manager-for-woocommerce-pro/rent-pro.php' ) ) {
		return true;
	}
	else{
		return false;
	}
}


// Hide wc hidden products
add_action('admin_head', 'rbfw_hide_date_from_order_page');
if (!function_exists('rbfw_hide_date_from_order_page')) {
function rbfw_hide_date_from_order_page() {
    $product_id = [];
    $args = array(
        'post_type' => 'rbfw_item',
        'posts_per_page' => -1
    );
    $qr = new WP_Query($args);
    foreach ($qr->posts as $result) {
        $post_id = $result->ID;
        $product_id[] = get_post_meta($post_id, 'link_wc_product', true) ? '.woocommerce-page .post-' . get_post_meta($post_id, 'link_wc_product', true) . '.type-product' : '';
    }
    $product_id = array_filter($product_id);
    $parr = implode(', ', $product_id);
    echo '<style> ' . esc_html($parr) . '{display:none!important}' . ' </style>';
}
}

add_action( 'pre_get_posts', 'rbfw_search_query_exlude_hidden_wc_fix' );
function rbfw_search_query_exlude_hidden_wc_fix( $query ) {
    if ($query->is_search && !is_admin() ) {
        $query -> set( 'tax_query', array(
            array(
                'taxonomy' => 'product_visibility',
                'field'    => 'name',
                'terms'    => 'exclude-from-search',
                'operator' => 'NOT IN',
            )
        ));
  }
  return $query;
}

/*****************************
 * Create Inventory Meta
 *****************************/
function rbfw_create_inventory_meta($ticket_info, $i){
	$rbfw_id = !empty($ticket_info[$i]['rbfw_id']) ? $ticket_info[$i]['rbfw_id'] : '';

	if(empty($rbfw_id)){
		return;
	}
	
	$rbfw_item_type = !empty(get_post_meta($rbfw_id, 'rbfw_item_type', true)) ? get_post_meta($rbfw_id, 'rbfw_item_type', true) : '';
	$rbfw_inventory_info = !empty(get_post_meta($rbfw_id, 'rbfw_inventory', true)) ? get_post_meta($rbfw_id, 'rbfw_inventory', true) : [];

	$start_date = !empty($ticket_info[$i]['rbfw_start_date']) ? $ticket_info[$i]['rbfw_start_date'] : '';
	$end_date = !empty($ticket_info[$i]['rbfw_end_date']) ? $ticket_info[$i]['rbfw_end_date'] : '';
	$start_time = !empty($ticket_info[$i]['rbfw_start_time']) ? $ticket_info[$i]['rbfw_start_time'] : '';
	$end_time = !empty($ticket_info[$i]['rbfw_end_time']) ? $ticket_info[$i]['rbfw_end_time'] : '';
	$rbfw_item_quantity = !empty($ticket_info[$i]['rbfw_item_quantity']) ? $ticket_info[$i]['rbfw_item_quantity'] : 0;
	$rbfw_type_info = !empty($ticket_info[$i]['rbfw_type_info']) ? $ticket_info[$i]['rbfw_type_info'] : [];
	$rbfw_variation_info = !empty($ticket_info[$i]['rbfw_variation_info']) ? $ticket_info[$i]['rbfw_variation_info'] : [];
	$rbfw_service_info = !empty($ticket_info[$i]['rbfw_service_info']) ? $ticket_info[$i]['rbfw_service_info'] : [];
	$date_range = [];

	if($rbfw_item_type == 'bike_car_md'){

		// Start: Date Time Calculation
		$start_datetime  = date( 'Y-m-d H:i', strtotime( $start_date . ' ' . $start_time ) );
		$end_datetime = date( 'Y-m-d H:i', strtotime( $end_date . ' ' . $end_time ) );
		$start_datetime  = new DateTime( $start_datetime );
		$end_datetime = new DateTime( $end_datetime );

		$diff = date_diff( $start_datetime, $end_datetime );
		$days = 0;
		$hours = 0;

		$start_date = strtotime($start_date);
		$end_date = strtotime($end_date);

		if ( $diff ) {
			$days    = $diff->days;
			$hours   += $diff->h;
			
			if ( $hours > 0 ) {
				
				for ($currentDate = $start_date; $currentDate <= $end_date; 

					$currentDate += (86400)) {
													
					$date = date('d-m-Y', $currentDate);
			
					$date_range[] = $date;
			
				}

			} else {

				for ($currentDate = $start_date; $currentDate < $end_date; 

					$currentDate += (86400)) {
													
					$date = date('d-m-Y', $currentDate);
			
					$date_range[] = $date;
			
				}
			}

		}
		// End: Date Time Calculation

	} else {

		$start_date = strtotime($start_date);
		$end_date = strtotime($end_date);

		for ($currentDate = $start_date; $currentDate <= $end_date; 

			$currentDate += (86400)) {
											
			$date = date('d-m-Y', $currentDate);
	
			$date_range[] = $date;
	
		}
	}

	$order_array = [];
	$order_array['booked_dates'] = $date_range;
	$order_array['rbfw_start_time'] = $start_time;
	$order_array['rbfw_end_time'] = $end_time;
	$order_array['rbfw_type_info'] = $rbfw_type_info;
	$order_array['rbfw_variation_info'] = $rbfw_variation_info;
	$order_array['rbfw_service_info'] = $rbfw_service_info;
	$order_array['rbfw_item_quantity'] = $rbfw_item_quantity;

	$rbfw_inventory_info[] = $order_array;
	
	update_post_meta($rbfw_id, 'rbfw_inventory', $rbfw_inventory_info);

	return true;
}

/******************************************
 * Single Day Type: Get Available Quantity
 *****************************************/
function rbfw_get_bike_car_sd_available_qty($post_id, $selected_date, $type, $selected_time = null){
	
	if (empty($post_id) || empty($selected_date) || empty($type)) {
		return;
	}

	$selected_date = date('d-m-Y', strtotime($selected_date));
	$total_qty = 0;
	$type_stock = 0;
	$rbfw_inventory = get_post_meta($post_id, 'rbfw_inventory', true);
	$rbfw_bike_car_sd_data = get_post_meta($post_id, 'rbfw_bike_car_sd_data', true);
	
	$rbfw_rent_type = get_post_meta($post_id, 'rbfw_item_type', true);
    $appointment_max_qty_per_session = get_post_meta($post_id, 'rbfw_sd_appointment_max_qty_per_session', true);

	if (!empty($rbfw_inventory)) {
		
		foreach ($rbfw_inventory as $key => $inventory) {

			$booked_dates = !empty($inventory['booked_dates']) ? $inventory['booked_dates'] : [];
			$rbfw_type_info = !empty($inventory['rbfw_type_info']) ? $inventory['rbfw_type_info'] : [];
			$rbfw_start_time = !empty($inventory['rbfw_start_time']) ? $inventory['rbfw_start_time'] : '';

			if($rbfw_rent_type == 'appointment'){

				if ( in_array($selected_date, $booked_dates) && ($selected_time == $rbfw_start_time) ) {
					
					foreach ($rbfw_type_info as $type_name => $type_qty) {
						
						if ($type_name == $type) {
							$total_qty += $type_qty;
						}
					}
				}

			} else {

				if ( in_array($selected_date, $booked_dates) ) {
					
					foreach ($rbfw_type_info as $type_name => $type_qty) {
						
						if ($type_name == $type) {
							$total_qty += $type_qty;
						}
					}
				}				
			}
		}
	}

	if (!empty($rbfw_bike_car_sd_data)) {
		
		foreach ($rbfw_bike_car_sd_data as $key => $bike_car_sd_data) {
			
			if($bike_car_sd_data['rent_type'] == $type){

				if($rbfw_rent_type == 'appointment'){

					$type_stock = $appointment_max_qty_per_session;

				} else {

					$type_stock += !empty($bike_car_sd_data['qty']) ? $bike_car_sd_data['qty'] : 0;
				}
			}
		}
	}

	$remaining_stock = $type_stock - $total_qty;
	$remaining_stock = max(0, $remaining_stock);

	return $remaining_stock;
}

/******************************************
 * Extra Service: Get Available Quantity
 *****************************************/
function rbfw_get_bike_car_sd_es_available_qty($post_id, $selected_date, $name){
	
	if (empty($post_id) || empty($selected_date) || empty($name)) {
		return;
	}

	$selected_date = date('d-m-Y', strtotime($selected_date));
	$total_qty = 0;
	$service_stock = 0;

	$rbfw_inventory = get_post_meta($post_id, 'rbfw_inventory', true);
	$rbfw_extra_service_data = get_post_meta($post_id, 'rbfw_extra_service_data', true);

	if (!empty($rbfw_inventory)) {
		
		foreach ($rbfw_inventory as $key => $inventory) {

			$booked_dates = !empty($inventory['booked_dates']) ? $inventory['booked_dates'] : [];
			$rbfw_service_info = !empty($inventory['rbfw_service_info']) ? $inventory['rbfw_service_info'] : [];

			if ( in_array($selected_date, $booked_dates) ) {
				
				foreach ($rbfw_service_info as $service_name => $service_qty) {
					
					if ($service_name == $name) {

						$total_qty += $service_qty;
					}
				}
			}
		}
	}

	if (!empty($rbfw_extra_service_data)) {
		
		foreach ($rbfw_extra_service_data as $key => $extra_service_data) {
			
			if($extra_service_data['service_name'] == $name){
				
				$service_stock += !empty($extra_service_data['service_qty']) ? $extra_service_data['service_qty'] : 0;
			}
		}
	}

	$remaining_stock = $service_stock - $total_qty;
	$remaining_stock = max(0, $remaining_stock);

	return $remaining_stock;
}

/****************************************************
 * Resort/Multiple Rent: 
 * Get Types Available Quantity
 ****************************************************/
function rbfw_get_multiple_date_available_qty($post_id, $start_date, $end_date, $type = null){
	
	if (empty($post_id) || empty($start_date) || empty($end_date)) {
		return;
	}

	$rbfw_enable_variations = get_post_meta( $post_id, 'rbfw_enable_variations', true ) ? get_post_meta( $post_id, 'rbfw_enable_variations', true ) : 'no';
	$rbfw_variations_stock = rbfw_get_variations_stock($post_id); 

	$rent_type = get_post_meta($post_id, 'rbfw_item_type', true);
	$rbfw_inventory = get_post_meta($post_id, 'rbfw_inventory', true);
	$type_stock = 0;

	// Start: Get Date Range
	$date_range = [];
	$start_date = strtotime($start_date);
	$end_date = strtotime($end_date);

	for ($currentDate = $start_date; $currentDate <= $end_date; 

		$currentDate += (86400)) {
										
		$date = date('d-m-Y', $currentDate);

		$date_range[] = $date;

	}
	// End: Get Date Range

	if ($rent_type == 'resort') {
		
		// For Resort Type
		$rbfw_resort_room_data = get_post_meta($post_id, 'rbfw_resort_room_data', true);

		if (!empty($rbfw_resort_room_data)) {

			foreach ($rbfw_resort_room_data as $key => $resort_room_data) {
				
				if($resort_room_data['room_type'] == $type){

					$type_stock += !empty($resort_room_data['rbfw_room_available_qty']) ? $resort_room_data['rbfw_room_available_qty'] : 0;
				}
			}
		}
		// End Resort Type

	} else {

		// For Bike/car Multiple Day Type
		if($rbfw_enable_variations == 'yes'){

			$type_stock += $rbfw_variations_stock;
			
		} else {

			$type_stock += get_post_meta($post_id, 'rbfw_item_stock_quantity', true);
		}
		

		// End Bike/car Multiple Day Type
	}

	if (!empty($rbfw_inventory)) {

		$total_qty = 0;
		$qty_array = [];

		foreach ($date_range as $key => $range_date) {

			foreach ($rbfw_inventory as $key => $inventory) {

				$booked_dates = !empty($inventory['booked_dates']) ? $inventory['booked_dates'] : [];
				$rbfw_type_info = !empty($inventory['rbfw_type_info']) ? $inventory['rbfw_type_info'] : [];

				$rbfw_item_quantity = !empty($inventory['rbfw_item_quantity']) ? $inventory['rbfw_item_quantity'] : [];
				

				if ( in_array($range_date, $booked_dates) ) {

					if ($rent_type == 'resort') {

						// For Resort Type
						foreach ($rbfw_type_info as $type_name => $type_qty) {
							
							if ($type_name == $type) {

								$total_qty += $type_qty;
							}
						}
						// End Resort Type

					} else {

						// For Bike/car Multiple Day Type
						$total_qty += $rbfw_item_quantity;
						// End Bike/car Multiple Day Type
					}
				}
			}
			$remaining_stock = $type_stock - $total_qty;	
			$remaining_stock = max(0, $remaining_stock);
			$qty_array[] = $remaining_stock;
			$total_qty = 0;
		}

		
	}
	
	if (empty($qty_array)) {

		$remaining_stock = $type_stock;

	} else {

		$remaining_stock = min($qty_array);
	}

	return $remaining_stock;
}

/****************************************************
 * Resort/Multiple Rent: 
 * Get Extra Service Available Quantity
 ****************************************************/
function rbfw_get_multiple_date_es_available_qty($post_id, $start_date, $end_date, $service){
	
	if (empty($post_id) || empty($start_date) || empty($end_date) || empty($service)) {
		return;
	}

	$service_stock = 0;
	$rbfw_inventory = get_post_meta($post_id, 'rbfw_inventory', true);
	
	// Start: Get Date Range
	$date_range = [];
	$start_date = strtotime($start_date);
	$end_date = strtotime($end_date);

	for ($currentDate = $start_date; $currentDate <= $end_date; 

		$currentDate += (86400)) {
										
		$date = date('d-m-Y', $currentDate);

		$date_range[] = $date;

	}
	// End: Get Date Range

	// Loop For Extra Services
	$rbfw_extra_service_data = get_post_meta($post_id, 'rbfw_extra_service_data', true);

	if (!empty($rbfw_extra_service_data)) {

		foreach ($rbfw_extra_service_data as $key => $extra_service_data) {
			
			if($extra_service_data['service_name'] == $service){

				$service_stock += !empty($extra_service_data['service_qty']) ? $extra_service_data['service_qty'] : 0;
			}
		}
	}
	// End Loop For Extra Services

	if (!empty($rbfw_inventory)) {

		$total_qty = 0;
		$qty_array = [];

		foreach ($date_range as $key => $range_date) {

			foreach ($rbfw_inventory as $key => $inventory) {

				$booked_dates = !empty($inventory['booked_dates']) ? $inventory['booked_dates'] : [];
				$rbfw_service_info = !empty($inventory['rbfw_service_info']) ? $inventory['rbfw_service_info'] : [];

				if ( in_array($range_date, $booked_dates) ) {
				
					foreach ($rbfw_service_info as $service_name => $service_qty) {
						
						if ($service_name == $service) {

							$total_qty += $service_qty;
						}
					}
				}
			}
			$remaining_stock = $service_stock - $total_qty;	
			$remaining_stock = max(0, $remaining_stock);
			$qty_array[] = $remaining_stock;
			$total_qty = 0;
		}
	}
	
	if (empty($qty_array)) {

		$remaining_stock = $service_stock;
	
	} else {
		
		$remaining_stock = min($qty_array);
	}

	return $remaining_stock;
}

/****************************************************
 * Multiple Rent: 
 * Get Variation Available Quantity
 ****************************************************/
function rbfw_get_multiple_date_variations_available_qty($post_id, $start_date, $end_date, $variation){
	
	if (empty($post_id) || empty($start_date) || empty($end_date) || empty($variation)) {
		return;
	}

	$variation_stock = 0;
	$rbfw_inventory = get_post_meta($post_id, 'rbfw_inventory', true);
	
	// Start: Get Date Range
	$date_range = [];
	$start_date = strtotime($start_date);
	$end_date = strtotime($end_date);

	for ($currentDate = $start_date; $currentDate <= $end_date; 

		$currentDate += (86400)) {
										
		$date = date('d-m-Y', $currentDate);

		$date_range[] = $date;

	}
	// End: Get Date Range

	// Loop For Extra variations
	$rbfw_variations_data = get_post_meta($post_id, 'rbfw_variations_data', true);

	if (!empty($rbfw_variations_data)) {

		foreach ($rbfw_variations_data as $key => $data_arr_one) {
			
			foreach ($data_arr_one['value'] as $data_arr_two) {
				
				if ($data_arr_two['name'] == $variation) {
					
					$variation_stock += $data_arr_two['quantity'];
				}
			}
		}
	}
	// End Loop For Extra variations

	if (!empty($rbfw_inventory)) {

		$total_qty = 0;
		$qty_array = [];

		foreach ($date_range as $key => $range_date) {

			foreach ($rbfw_inventory as $key => $inventory) {

				$booked_dates = !empty($inventory['booked_dates']) ? $inventory['booked_dates'] : [];
				$rbfw_variation_info = !empty($inventory['rbfw_variation_info']) ? $inventory['rbfw_variation_info'] : [];

				if ( in_array($range_date, $booked_dates) ) {
				
					foreach ($rbfw_variation_info as $field_key => $field_value) {
						
						if ($field_value['field_value'] == $variation) {

							$total_qty += 1;
						}
					}
				}
			}
			$remaining_stock = $variation_stock - $total_qty;	
			$remaining_stock = max(0, $remaining_stock);
			$qty_array[] = $remaining_stock;
			$total_qty = 0;
		}
	}

	if (empty($qty_array)) {

		$remaining_stock = $variation_stock;
	
	} else {
		
		$remaining_stock = min($qty_array);
	}

	return $remaining_stock;
}

/****************************************************
 * Multiple Rent: 
 * Get Variation Total Stock
 ****************************************************/
function rbfw_get_variations_stock($post_id){

	$variation_stock = 0;

		// Loop For Extra variations
		$rbfw_variations_data = get_post_meta($post_id, 'rbfw_variations_data', true);

		$count = 1;

		if (!empty($rbfw_variations_data)) {
	
			$count = count($rbfw_variations_data);

			foreach ($rbfw_variations_data as $key => $data_arr_one) {
				
				foreach ($data_arr_one['value'] as $data_arr_two) {
					
					$variation_stock += (int)$data_arr_two['quantity'];
					
				}
			}
		}
		// End Loop For Extra variations

	$variation_stock = round($variation_stock / $count);

	return $variation_stock;	

}

	

/****************************************************
 * Add to cart redirect: 
 ****************************************************/
add_filter('woocommerce_add_to_cart_redirect', 'rbfw_add_to_cart_redirect');
function rbfw_add_to_cart_redirect() {
	$add_to_cart_redirect = rbfw_get_option( 'rbfw_wps_add_to_cart_redirect', 'rbfw_basic_payment_settings', 'checkout');

	if ($add_to_cart_redirect == 'checkout') {
		global $woocommerce;
		if ( class_exists( 'WooCommerce' ) ) {
			$rbfw_redirect_checkout = wc_get_checkout_url();
			return $rbfw_redirect_checkout;
		}
	}
}

add_filter('wc_add_to_cart_message_html', 'rbfw_remove_add_to_cart_message');
function rbfw_remove_add_to_cart_message($message){
	$add_to_cart_redirect = rbfw_get_option( 'rbfw_wps_add_to_cart_redirect', 'rbfw_basic_payment_settings', 'checkout');

	if ($add_to_cart_redirect == 'checkout') {

		return '';

	} else {
		
		return $message;
	}
}

/****************************************************
 * Available Time Slots: 
 ****************************************************/
function rbfw_get_available_time_slots(){

	$rbfw_time_slots = !empty(get_option('rbfw_time_slots')) ? get_option('rbfw_time_slots') : [];
	asort($rbfw_time_slots);

	return $rbfw_time_slots;
}

/****************************************************
 * Import Time Slots if option empty: 
 ****************************************************/
add_action('admin_init', 'rbfw_import_dummy_time_slots');
function rbfw_import_dummy_time_slots(){

	$import_time_slot_array = array(
	
		'12:00 AM' => __( '12:00 AM', 'booking-and-rental-manager-for-woocommerce' ),
		'12:30 AM' => __( '12:30 AM', 'booking-and-rental-manager-for-woocommerce' ),
		'1:00 AM'  => __( '1:00 AM', 'booking-and-rental-manager-for-woocommerce' ),
		'1:30 AM'  => __( '1:30 AM', 'booking-and-rental-manager-for-woocommerce' ),
		'2:00 AM'  => __( '2:00 AM', 'booking-and-rental-manager-for-woocommerce' ),
		'2:30 AM'  => __( '2:30 AM', 'booking-and-rental-manager-for-woocommerce' ),
		'3:00 AM'  => __( '3:00 AM', 'booking-and-rental-manager-for-woocommerce' ),
		'3:30 AM'  => __( '3:30 AM', 'booking-and-rental-manager-for-woocommerce' ),
		'4:00 AM'  => __( '4:00 AM', 'booking-and-rental-manager-for-woocommerce' ),
		'4:30 AM'  => __( '4:30 AM', 'booking-and-rental-manager-for-woocommerce' ),
		'5:00 AM'  => __( '5:00 AM', 'booking-and-rental-manager-for-woocommerce' ),
		'5:30 AM'  => __( '5:30 AM', 'booking-and-rental-manager-for-woocommerce' ),
		'6:00 AM'  => __( '6:00 AM', 'booking-and-rental-manager-for-woocommerce' ),
		'6:30 AM'  => __( '6:30 AM', 'booking-and-rental-manager-for-woocommerce' ),
		'7:00 AM'  => __( '7:00 AM', 'booking-and-rental-manager-for-woocommerce' ),
		'7:30 AM'  => __( '7:30 AM', 'booking-and-rental-manager-for-woocommerce' ),
		'8:00 AM'  => __( '8:00 AM', 'booking-and-rental-manager-for-woocommerce' ),
		'8:30 AM'  => __( '8:30 AM', 'booking-and-rental-manager-for-woocommerce' ),
		'9:00 AM'  => __( '9:00 AM', 'booking-and-rental-manager-for-woocommerce' ),
		'9:30 AM'  => __( '9:30 AM', 'booking-and-rental-manager-for-woocommerce' ),
		'10:00 AM' => __( '10:00 AM', 'booking-and-rental-manager-for-woocommerce' ),
		'10:30 AM' => __( '10:30 AM', 'booking-and-rental-manager-for-woocommerce' ),
		'11:00 AM' => __( '11:00 AM', 'booking-and-rental-manager-for-woocommerce' ),
		'11:30 AM' => __( '11:30 AM', 'booking-and-rental-manager-for-woocommerce' ),
		'12:00 PM' => __( '12:00 PM', 'booking-and-rental-manager-for-woocommerce' ),
		'12:30 PM' => __( '12:30 PM', 'booking-and-rental-manager-for-woocommerce' ),
		'1:00 PM'  => __( '1:00 PM', 'booking-and-rental-manager-for-woocommerce' ),
		'1:30 PM'  => __( '1:30 PM', 'booking-and-rental-manager-for-woocommerce' ),
		'2:00 PM'  => __( '2:00 PM', 'booking-and-rental-manager-for-woocommerce' ),
		'2:30 PM'  => __( '2:30 PM', 'booking-and-rental-manager-for-woocommerce' ),
		'3:00 PM'  => __( '3:00 PM', 'booking-and-rental-manager-for-woocommerce' ),
		'3:30 PM'  => __( '3:30 PM', 'booking-and-rental-manager-for-woocommerce' ),
		'4:00 PM'  => __( '4:00 PM', 'booking-and-rental-manager-for-woocommerce' ),
		'4:30 PM'  => __( '4:30 PM', 'booking-and-rental-manager-for-woocommerce' ),
		'5:00 PM'  => __( '5:00 PM', 'booking-and-rental-manager-for-woocommerce' ),
		'5:30 PM'  => __( '5:30 PM', 'booking-and-rental-manager-for-woocommerce' ),
		'6:00 PM'  => __( '6:00 PM', 'booking-and-rental-manager-for-woocommerce' ),
		'6:30 PM'  => __( '6:30 PM', 'booking-and-rental-manager-for-woocommerce' ),
		'7:00 PM'  => __( '7:00 PM', 'booking-and-rental-manager-for-woocommerce' ),
		'7:30 PM'  => __( '7:30 PM', 'booking-and-rental-manager-for-woocommerce' ),
		'8:00 PM'  => __( '8:00 PM', 'booking-and-rental-manager-for-woocommerce' ),
		'8:30 PM'  => __( '8:30 PM', 'booking-and-rental-manager-for-woocommerce' ),
		'9:00 PM'  => __( '9:00 PM', 'booking-and-rental-manager-for-woocommerce' ),
		'9:30 PM'  => __( '9:30 PM', 'booking-and-rental-manager-for-woocommerce' ),
		'10:00 PM' => __( '10:00 PM', 'booking-and-rental-manager-for-woocommerce' ),
		'10:30 PM' => __( '10:30 PM', 'booking-and-rental-manager-for-woocommerce' ),
		'11:00 PM' => __( '11:00 PM', 'booking-and-rental-manager-for-woocommerce' ),
		'11:30 PM' => __( '11:30 PM', 'booking-and-rental-manager-for-woocommerce' ),
	);

	if(get_option('rbfw_time_slots') === false){

		update_option('rbfw_time_slots', $import_time_slot_array);
	}
}

/****************************************************
 * Multiple Dates/Resort Rent: 
 * Discount function
 ****************************************************/
function rbfw_get_discount_array($post_id, $start_date, $end_date, $total_amount){

	if(empty($post_id) || empty($start_date) || empty($end_date) || empty($total_amount)){
		return;
	}
	
	/* Start Discount Calculations */
	$discount_switch = !empty(get_post_meta($post_id, 'rbfw_enable_discount', true)) ? get_post_meta($post_id, 'rbfw_enable_discount', true) : 'no';
	$rbfw_discount_array = !empty(get_post_meta($post_id, 'rbfw_discount_array', true)) ? get_post_meta($post_id, 'rbfw_discount_array', true) : [];

	if(!empty($rbfw_discount_array)){
		$arr1 = [];
		foreach ($rbfw_discount_array as $value) {
			$arr1[] = $value['rbfw_discount_over_days'];
		}
	}
	
	if(!empty($arr1)){
		asort( $arr1 );
	}
	/* End Discount Calculations */

	$array = [];

	/* get Date difference */
	$start_date  = date( 'Y-m-d', strtotime( $start_date ) );
	$end_date = date( 'Y-m-d', strtotime( $end_date ) );
	$start_date  = new DateTime( $start_date );
	$end_date = new DateTime( $end_date );
	$diff = date_diff( $start_date, $end_date );
	$days = 0;

	if ( $diff ) {
		$days = $diff->days;
	}
	/* End Date difference */

	$t_amount = $total_amount;
	
	if(!empty($arr1) && $discount_switch == 'yes'){

		foreach ($arr1 as $value) {
		
			if($days >= $value){
	
				foreach ($rbfw_discount_array as $discount_array) {
	
					if($value == $discount_array['rbfw_discount_over_days']){
	
						$discount_type = $discount_array['rbfw_discount_type'];
						$discount_number = $discount_array['rbfw_discount_number'];
	
					}
	
				}
	
				if($discount_type == 'percentage'){
	
					$discount_amount = ($t_amount * (float)$discount_number) / 100;
					$new_t_amount = $t_amount - $discount_amount;
					$discount_desc =  $discount_number.'%';
		
				} elseif($discount_type == 'fixed_amount') {
					$discount_amount = $discount_number;
					$new_t_amount = $t_amount - (float)$discount_amount;   
					$discount_desc = rbfw_mps_price($discount_number);          
				} 
				
				$array['discount_type'] = $discount_type;
				$array['discount_amount'] = $discount_amount;
				$array['total_amount'] = $new_t_amount;
				$array['discount_desc'] = $discount_desc;
			}
		}
	}

	
	return $array;
}

/****************************************************
 * Multiple Dates/Resort Rent: 
 * Discount Notice/Ad
 ****************************************************/

function rbfw_discount_ad($post_id){

	if(empty($post_id)){
		return;
	}

	$discount_switch = !empty(get_post_meta($post_id, 'rbfw_enable_discount', true)) ? get_post_meta($post_id, 'rbfw_enable_discount', true) : 'no';
	$rbfw_discount_array = !empty(get_post_meta($post_id, 'rbfw_discount_array', true)) ? get_post_meta($post_id, 'rbfw_discount_array', true) : [];

	if($discount_switch == 'yes'){
	?>
	<div class="rbfw-bikecarsd-calendar-header">
		<div class="rbfw-bikecarsd-calendar-header-title"><?php rbfw_string('rbfw_text_discount',__('Discount','booking-and-rental-manager-for-woocommerce')); ?></div>
		<?php 
		foreach ($rbfw_discount_array as $discount_array) {
			
			$rbfw_discount_over_days = $discount_array['rbfw_discount_over_days'];
			$rbfw_discount_type = $discount_array['rbfw_discount_type'];
			$rbfw_discount_number = $discount_array['rbfw_discount_number'];

			if($rbfw_discount_over_days == 1){

				$rbfw_discount_over_days = $rbfw_discount_over_days.' '.rbfw_string_return('rbfw_text_day',__('day','booking-and-rental-manager-for-woocommerce'));

			} elseif($rbfw_discount_over_days > 1) {

				$rbfw_discount_over_days = $rbfw_discount_over_days.' '.rbfw_string_return('rbfw_text_days',__('days','booking-and-rental-manager-for-woocommerce'));
			}

			if($rbfw_discount_type == 'percentage'){

				$rbfw_discount_desc = $rbfw_discount_number.'%';

			} else if($rbfw_discount_type == 'fixed_amount'){

				$rbfw_discount_desc = rbfw_mps_price($rbfw_discount_number);
			}

			if(!empty($rbfw_discount_over_days) && !empty($rbfw_discount_type) && !empty($rbfw_discount_number)){

			?>
			<div class="rbfw-bikecarsd-calendar-header-feature"><i class="fa-solid fa-circle-info"></i> <?php rbfw_string('rbfw_text_book_over',__('Book over','booking-and-rental-manager-for-woocommerce')); ?> <?php echo $rbfw_discount_over_days; ?> <?php rbfw_string('rbfw_text_and',__('and','booking-and-rental-manager-for-woocommerce')); ?> <strong><?php rbfw_string('rbfw_text_save',__('save','booking-and-rental-manager-for-woocommerce')); ?> <?php echo $rbfw_discount_desc; ?></strong> </div>
			<?php
			}
		} 
		?>
	</div>
	<?php
	}
}


/****************************************************
 * Check Min-Max Booking Day Plugin Active
 ****************************************************/
function rbfw_check_min_max_booking_day_active(){
	if (is_plugin_active( 'booking-and-rental-manager-min-max-booking-day/rent-min-max-booking-day.php' ) ) {
		return true;
	}
	else{
		return false;
	}
}