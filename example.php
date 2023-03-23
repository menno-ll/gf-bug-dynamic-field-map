<?php

namespace Cordaid\GravityForms\Invenna;

use GFFeedAddOn;

class Feed extends GFFeedAddOn {

	public const FEED_SLUG           = 'invenna';

	public const META_ENABLED = 'cordaid_invenna_enabled';
	public const META_AUTOFILL_ENABLED = 'cordaid_invenna_autofill_enabled';

	public const META_FIELD_MAP_PREFIX = 'cordaid_invenna_field_map_';
	public const META_FIELD_MAP_DIRECT = 'cordaid_invenna_field_map_direct';

	// phpcs:disable PSR2.Classes.PropertyDeclaration.Underscore
	protected $_multiple_feeds           = false;
	protected $_version                  = '1.0';
	protected $_min_gravityforms_version = '2.6';
	protected $_slug                     = self::FEED_SLUG;
	protected $_full_path                = __FILE__;
	// phpcs:enable PSR2.Classes.PropertyDeclaration.Underscore

	/**
	 * @var Feed $_instance
	 */
	private static $_instance = null;

	public function __construct()
	{
		$this->_title = __( 'Invenna', 'cordaid-nl-admin' );
		$this->_short_title = __( 'Invenna', 'cordaid-nl-admin' );
		parent::__construct();
	}

	public static function get_instance(): self {
		if ( self::$_instance === null ) {
			self::$_instance = new Feed();
		}

		return self::$_instance;
	}

	/**
	 * Configures the settings which should be rendered on the feed edit page
	 *
	 * @return array<mixed>
	 */
	public function feed_settings_fields() {
		$mapping = new Mapping();
		$fields = array(
			array(
				'name' => self::META_ENABLED,
				'type'    => 'toggle',
				'label'   => __( 'Maak inzendingen beschikbaar in Invenna', 'cordaid-nl-admin' ),
				'default_value' => 1,
			),
			array(
				'name' => self::META_AUTOFILL_ENABLED,
				'type'    => 'toggle',
				'label'   => __( 'Vul velden automatisch in', 'cordaid-nl-admin' ),
				'tooltip'   => __( 'Velden automatsch proberen in te vullen met gegevens die al eerder in de browser van de gebruiker zijn opgeslagen', 'cordaid-nl-admin' ),
				'default_value' => 1,
				'dependency' => array(
					'live'   => true,
					'fields' => array(
						array(
							'field' => self::META_ENABLED,
						),
					),
				),
			),
			array(
				'name'      => self::META_FIELD_MAP_DIRECT,
				'type'      => 'field_map',
				'label'   => __( 'Directe velden koppelen', 'cordaid-nl-admin' ),
				'tooltip'   => __( 'Koppel velden die een 1-op-1 relatie hebben in Invenna', 'cordaid-nl-admin' ),
				'field_map' => $mapping->get_direct_field_map(),
				'dependency' => array(
					'live'   => true,
					'fields' => array(
						array(
							'field' => self::META_ENABLED,
						),
					),
				),
			),
		);

		// Here starts the problem
		foreach ( array(
			'message',
			'products',
			'files',
		) as $dynamic_field_map_setting ) {
			$fields[] = array(
				'name'      => self::META_FIELD_MAP_PREFIX . $dynamic_field_map_setting,
				'type'      => 'dynamic_field_map',
				'label'   => sprintf( __( 'Koppel %s veld', 'cordaid-nl-admin' ), $dynamic_field_map_setting),
				'tooltip'   => sprintf( __( 'Koppel het %s veld zodat ingevulde waardes beschikbaar worden gemaakt voor Invenna', 'cordaid-nl-admin' ), $dynamic_field_map_setting),
				'enable_custom_key' => false, // Or this needs to be set to true for it to be allowed to be added multiple times
				'allow_duplicates' => true,
				'field_map' => array(
					// This one is required to allow adding multiple. This just represents an empty option now, and has no use at all other than bypassing the bug.
					array(
						'name' => 'none',
						'label'   => __( 'Geen (niet gekoppeld)', 'cordaid-nl-admin' ),
					),

					// This is the only key I would like to have.
					array(
						'name' => $dynamic_field_map_setting,
						'label'   => $dynamic_field_map_setting,
					)
				),
				'dependency' => array(
					'live'   => true,
					'fields' => array(
						array(
							'field' => self::META_ENABLED,
						),
					),
				),
			);
		}

		return array(
			array(
				'title'  => __( 'Invenna velden', 'cordaid-nl-admin' ),
				'fields' => $fields,
			),
		);
	}

	/**
	 * Save extra data in the entry.
	 * When adding meta data, we need to make sure to add it to the entry array, and also call gform_update_meta.
	 * @see https://docs.gravityforms.com/using-entry-meta-with-add-on-framework/#saving-a-value-to-the-entry-meta-key
	 *
	 * @param array<string|mixed> $feed The feed object to be processed.
	 * @param array<mixed> $entry The entry object currently being processed.
	 * @param array<string,mixed> $form The form object currently being processed.
	 *
	 * @return null|array Modified entry what's going to be updated by GravityForms
	 */
	public function process_feed( $feed, $entry, $form ) {

		// Determine if an entry should be showin in the Invenna API.
		gform_update_meta( $entry['id'], 'cordaid_is_invenna_entry', true );
    	$entry['cordaid_is_invenna_entry'] = true;

		// Saves the form state, so old entries in the Invenna API won't break if the form is changed after the entry.
		gform_update_meta( $entry['id'], 'cordaid_invenna_form_state', $form );
    	$entry['cordaid_is_invenna_entry'] = $form;

		return $entry;
	}
}
