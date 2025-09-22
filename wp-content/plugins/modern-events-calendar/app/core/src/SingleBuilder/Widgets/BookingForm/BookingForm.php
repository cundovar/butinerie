<?php


namespace MEC\SingleBuilder\Widgets\BookingForm;

use MEC\Base;
use MEC\SingleBuilder\Widgets\WidgetBase;

class BookingForm extends WidgetBase {

	public function get_display_booking_form($event_id){

		ob_start();
			\MEC\Books\BookingForm::getInstance()->display_form($event_id);
		return ob_get_clean();
	}

	/**
	 *  Get HTML Output
	 *
	 * @param int $event_id
	 * @param array $atts
	 *
	 * @return string
	 */
	public function output( $event_id = 0, $atts = array() ){

		if( !$event_id ){

			$event_id = $this->get_event_id();
		}

		if(!$event_id){
			return '';
		}

		if( isset( $atts['display_price_label'] ) && $atts['display_price_label'] ){
			update_option('mec_filter_price_label','yes');
			update_option('mec_filter_ticket_price_label','yes');

		}else{

			update_option('mec_filter_price_label','no');
			update_option('mec_filter_ticket_price_label','no');
		}

		$settings = $this->settings;
		$event_detail = $this->get_event_detail($event_id);
		$html = '';

		if ( true === $this->is_editor_mode && ( !isset($settings['booking_status']) || !$settings['booking_status'] ) ) {
			$html = '<div class="mec-content-notification">
					<p>'
						.'<span>'
							. esc_html__('To show this widget, you need to set "Tickets" for your latest event.', 'mec')
						.'</span>'
						.'<a href="https://webnus.net/dox/modern-events-calendar/add-event/#Tickets" target="_blank">' . esc_html__('Read More', 'mec') . ' </a>'
					.'</p>'
				.'</div>';
		} else {

			$html = $this->get_display_booking_form($event_id);

			if ( true === $this->is_editor_mode && \MEC\Base::get_main()->can_show_booking_module($event_detail) && isset($settings['single_booking_style']) && $settings['single_booking_style'] == 'modal'){

				$html .= '<style>
					.lity-container {
						max-width: 480px;
						width: 480px;
					}
				</style>';

				$html .= '<div class="mec-content-notification"><p><span>'
					.__('It seems that you have set "Booking" to modal from Single Event MEC Settings. You need to know that for this mode to work you must add Register Button Widget to this page, then in the front-end by clicking the Register button in your events you can then see the modal mode of the Booking.', 'mec')
				.'</span></p></div>';
			}elseif( true === $this->is_editor_mode && empty($html) ){

				$html .= '<div class="mec-content-notification"><p>'
						.'<span>'
							. esc_html__('To show this widget, you need to set "Tickets" for your latest event.', 'mec')
						.'</span>'
						.'<a href="https://webnus.net/dox/modern-events-calendar/add-event/#Tickets" target="_blank">' . esc_html__('Read More', 'mec') . ' </a>'
					.'</p></div>';
			}elseif ( true === $this->is_editor_mode ){

				$html .= '<script>jQuery("#mec-book-form-btn-step-1").prop("disabled",true);</script>';
			}
		}

		return $html;
	}
}
