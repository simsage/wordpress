/**
 * BLOCK: search
 *
 * Registering a basic block with Gutenberg.
 * Simple block, renders and saves the same content without any interactivity.
 */

//  Import CSS.
import './editor.scss';
import './style.scss';

import React, { Fragment } from 'react';
import { InspectorControls, } from '@wordpress/block-editor';
import { Panel, PanelBody, PanelRow, ToggleControl } from '@wordpress/components';

const { __ } = wp.i18n; // Import __() from wp.i18n
const { registerBlockType } = wp.blocks; // Import registerBlockType() from wp.blocks

/**
 * Register: aa Gutenberg Block.
 *
 * Registers a new block provided a unique name and an object defining its
 * behavior. Once registered, the block is made editor as an option to any
 * editor interface where blocks are implemented.
 *
 * @link https://wordpress.org/gutenberg/handbook/block-api/
 * @param  {string}   name     Block name.
 * @param  {Object}   settings Block settings.
 * @return {?WPBlock}          The block, if it has been successfully
 *                             registered; otherwise `undefined`.
 */
registerBlockType( 'simsage/search', {
	// Block name. Block names must be string that contains a namespace prefix. Example: my-plugin/my-custom-block.
	title: __( 'Simsage Search' ), // Block title.
	icon: 'search', // Block icon from Dashicons → https://developer.wordpress.org/resource/dashicons/.
	category: 'widgets', // Block category — Group blocks together based on common traits E.g. common, formatting, layout widgets, embed.
	attributes: {
		main_search: {
			type: 'boolean',
			default: false,
		},
		disable_styles: {
			type: 'boolean',
			default: false,
		},
	},
	keywords: [
		__( 'search' ),
		__( 'simsage' ),
		__( 'form' ),
	],

	/**
	 * The edit function describes the structure of your block in the context of the editor.
	 * This represents what the editor will render when the block is used.
	 *
	 * The "edit" property must be a valid function.
	 *
	 * @link https://wordpress.org/gutenberg/handbook/block-api/block-edit-save/
	 *
	 * @param {Object} props Props.
	 * @returns {Mixed} JSX Component.
	 */
	edit: ( props ) => {
		// Creates a <p class='wp-block-cgb-block-search'></p>.
		const { attributes, setAttributes } = props
		return (
			<Fragment>
                <InspectorControls>
					<Panel title="Search options">
						<PanelBody>
							<PanelRow title="Disable styles" initialOpen={true}>
								<ToggleControl
									label="Disable default styles"
									checked={attributes.disable_styles}
									onChange={(disableStyles) => setAttributes({ disable_styles: disableStyles })}
								/>
							</PanelRow>
						</PanelBody>
					</Panel>
				</InspectorControls>
				<div className={props.className}>
					<div className="simsage-search">
						<div className="search-bar">
							<div className="search-box-container">
								<div className="search-form search-form-static" title="Search">
									<input type="search" name="simsage_search" autoComplete="off"
										   className="search-text search-text-static search-text-2" maxLength="100"
										   placeholder="Search ..." />
								</div>
							</div>
						</div>
						<br clear="all" />
					</div>
				</div>
			</Fragment>
		);
	},

	/**
	 * The save function defines the way in which the different attributes should be combined
	 * into the final markup, which is then serialized by Gutenberg into post_content.
	 *
	 * The "save" property must be specified and must be a valid function.
	 *
	 * @link https://wordpress.org/gutenberg/handbook/block-api/block-edit-save/
	 *
	 * @param {Object} props Props.
	 * @returns {Mixed} JSX Frontend HTML.
	 */
	save: ( props ) => {
		return (
			<div className={ props.className }>
                Frontend
			</div>
		);
	},
} );
