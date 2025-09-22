<?php

namespace MEC\SingleBuilder\Widgets\EventRelated;

use MEC\Base;
use MEC\SingleBuilder\Widgets\WidgetBase;

class EventRelated extends WidgetBase {

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

		$settings = $this->settings;
		$event_detail = $this->get_event_detail($event_id);

		$html = '';
		if ( true === $this->is_editor_mode && ( !isset($settings['related_events']) || !$settings['related_events'] ) ) {

			$html = '<div class="mec-content-notification"><p>'
					.'<span>'. esc_html__('To show this widget, you need to enable "Related Events" module.', 'mec').'</span>'
					. '<a href="https://webnus.net/dox/modern-events-calendar/event-modules/#Related_Events" target="_blank">' . esc_html__('Read More', 'mec') . ' </a>'
				.'</p></div>';
		} else {

			$single         = new \MEC_skin_single();
			ob_start();
				$single->display_related_posts_widget( $event_id, 'thumbrelated' );
			$html = ob_get_clean();
		}

		return $html;
	}
}
