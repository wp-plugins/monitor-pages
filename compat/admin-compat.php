<?php
if ( !defined( 'ABSPATH' ) ) die();

if ( !function_exists( 'submit_button' ) ) {
	function submit_button( $text = NULL, $type = 'primary', $name = 'submit', $wrap = true, $other_attributes = NULL ) {
		echo get_submit_button( $text, $type, $name, $wrap, $other_attributes );
	}
}

if ( !function_exists( 'get_submit_button' ) ) {
	function get_submit_button( $text = NULL, $type = 'primary', $name = 'submit', $wrap = true, $other_attributes = NULL ) {
		switch ( $type ) :
			case 'primary' :
			case 'secondary' :
				$class = 'button-' . $type;
				break;
			case 'delete' :
				$class = 'button-secondary delete';
				break;
			default :
				$class = $type; // Custom cases can just pass in the classes they want to be used
		endswitch;
		$text = ( NULL == $text ) ? __( 'Save Changes' ) : $text;

		// Default the id attribute to $name unless an id was specifically provided in $other_attributes
		$id = $name;
		if ( is_array( $other_attributes ) && isset( $other_attributes['id'] ) ) {
			$id = $other_attributes['id'];
			unset( $other_attributes['id'] );
		}

		$attributes = '';
		if ( is_array( $other_attributes ) ) {
			foreach ( $other_attributes as $attribute => $value ) {
				$attributes .= $attribute . '="' . esc_attr( $value ) . '" '; // Trailing space is important
			}
		} else if ( !empty( $other_attributes ) ) { // Attributes provided as a string
			$attributes = $other_attributes;
		}

		$button = '<input type="submit" name="' . esc_attr( $name ) . '" id="' . esc_attr( $id ) . '" class="' . esc_attr( $class );
		$button	.= '" value="' . esc_attr( $text ) . '" ' . $attributes . ' />';

		if ( $wrap ) {
			$button = '<p class="submit">' . $button . '</p>';
		}

		return $button;
	}
}