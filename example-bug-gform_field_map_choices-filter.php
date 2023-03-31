<?php

namespace Cordaid\GravityForms\Fields\GenericMap;

use GFAPI;
use GFCommon;

class AllowMultipleInputsFieldMapping {

	public function register_hooks(): void {
		add_action( 'gform_field_map_choices', array( $this, 'add_field_for_multiple_inputs' ), 10, 4 );
	}

	public function add_field_for_multiple_inputs( array $map_choices, int $form_id, array|string|null $required_types, array|string|null $excluded_types ) {

		// Dont do anything if form does not exist, or there are no fields in the map choices
		$form = GFAPI::get_form( $form_id );
		if ( ! is_array( $form ) || empty( $map_choices['fields'] ) ) {
			return $map_choices;
		}

		// Force required, excluded types to arrays.
		$required_types = is_array( $required_types ) ? $required_types : array();
		$excluded_types = is_array( $excluded_types ) ? $excluded_types : array();

		foreach ( $form['fields'] as $field ) {

			// Get input type and available inputs.
			$input_type = $field->get_input_type();
			$inputs     = $field->get_entry_inputs();

			// If field type is excluded, skip.
			if ( ! empty( $excluded_types ) && in_array( $input_type, $excluded_types ) ) {
				continue;
			}

			// If field type is not whitelisted, skip.
			if ( ! empty( $required_types ) && ! in_array( $input_type, $required_types ) ) {
				continue;
			}

			// Don't do anything for fields that have no multiple inputs, or already exists
			if (
				! is_array( $inputs )
				|| array_key_exists( (string) $field->id, $map_choices['fields'] )
				|| array_key_exists( (int) $field->id, $map_choices['fields'] )
			) {
				continue;
			}

			// Add the field as a whole to be selected.
			// Attempt to insert item before first choice of inputs if possible.

			// Find where the first choice is located
			$first_index_of_inputs = null;
			foreach ( $map_choices['fields']['choices'] as $map_choice_index => $map_choice ) {
				if ( strpos( (string) $map_choice['value'], $field->id . '.' ) === 0 ) {
					$first_index_of_inputs = $map_choice_index;
					break;
				}
			}

			// If there is no first choice, don't do anything.
			if ( $first_index_of_inputs === null ) {
				continue;
			}

			// Add item just before it's choices
			$item_to_add = array(
				'label' => strip_tags( GFCommon::get_label( $field ) . ' (' . esc_html__( 'Selected', 'gravityforms' ) . ')' ),
				'value' => $field->id,
				'type' => 'checkbox'
			);

			$map_choices_field_choices = $map_choices['fields']['choices'];
			$map_choices_field_choices = array_merge(array_slice($map_choices_field_choices, 0, $first_index_of_inputs), array($item_to_add), array_slice($map_choices_field_choices, $first_index_of_inputs));
			$map_choices['fields']['choices'] = $map_choices_field_choices;
		}

		return $map_choices;
	}
}
