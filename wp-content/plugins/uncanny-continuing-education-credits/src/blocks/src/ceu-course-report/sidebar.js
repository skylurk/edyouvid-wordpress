/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { Fragment } from '@wordpress/element';
import { InspectorControls } from '@wordpress/block-editor';
import { 
	PanelBody, 
	ToggleControl, 
	SelectControl 
} from '@wordpress/components';

/**
 * Internal dependencies
 */
import CeuCourseReportColumns from './components/columns';

/**
 * Default columns for the report.
 * 
 * @type {Array}
 *
 */
const defaultColumns = [
	{ value: 'user', label: __( 'User', 'uncanny-ceu' ) },
	{ value: 'course', label: __( 'Title', 'uncanny-ceu' ) },
	{ value: 'date', label: __( 'Date', 'uncanny-ceu' ) },
	{ value: 'ceus_earned', label: __( 'CEUs', 'uncanny-ceu' ) },
	{ value: 'total', label: __( 'Total', 'uncanny-ceu' ) },
];

/**
 * Sidebar component for the CEU Course Report block.
 * 
 * @param {Object} props
 * @param {Object} props.attributes
 * @param {Function} props.setAttributes
 * 
 * @return {Component}
 */
const CeuCourseReportSidebar = ( { attributes, setAttributes } ) => {
	const isEnhanced = attributes.enhanced === 'on';
	const isCustom   = isEnhanced && attributes.columns !== null;
	
	/**
	 * Update columns attribute.
	 * 
	 * @param {Array||null} columns - Array of column objects or null.
	 */
	const onUpdateColumns = ( columns ) => {
		// Check if empty array is passed
		if ( null === columns || !columns.length ) {
			setAttributes({ columns: null });
			return;
		}
		// Generate a CSV string of column key||label pairs
		const columnString = columns.map( column => `${column.value}||${column.label}` ).join( ',' );
		setAttributes({ columns: columnString });
	};

	return (
		<Fragment>
			<InspectorControls>
				<PanelBody title={ 'Continuing Education Report Settings' }>
					<ToggleControl
						label={ __( 'Enhanced', 'uncanny-ceu' ) }
						value='on'
						checked={ isEnhanced }
						onChange={(value) => {
							const enhanced = value ? 'on' : null;
							setAttributes({ enhanced: enhanced });
						}}
						help={ __( 'Enabling the enhanced report will show the full Continuing Education Report table.', 'uncanny-ceu' ) }
					/>
				</PanelBody>
				{ isEnhanced &&
					<Fragment>
						<PanelBody title={ 'Enhanced Report Columns' } initialOpen={ isCustom }>
						<ToggleControl
							label={ __( 'Customize report columns', 'uncanny-ceu' ) }
							checked={ isCustom }
							onChange={(value) => {
								onUpdateColumns( value ? defaultColumns : null );
							}}
							help={ 
								isCustom 
								? (
								<Fragment>
									{ __( 'Disabling custom columns will revert to the default columns. Note: custom columns will not be populated by default; you will need to use a filter. ', 'uncanny-ceu' ) }
									<a target="_blank" href="https://www.uncannyowl.com/knowledge-base/continuing-education-report#populating-custom-column-data">
										{ __( 'More info', 'uncanny-ceu' ) }
									</a>
								</Fragment> 
								) 
								: (
									__( 'Enabling custom columns will allow you to specify which columns to display in the report and control their order.', 'uncanny-ceu' )
								)
							}
						/>
						{ isCustom &&
							<CeuCourseReportColumns
								columnString={attributes.columns || null}
								defaults={defaultColumns}
								onUpdate={onUpdateColumns}
							/>
						}
						</PanelBody>
						<PanelBody title={ 'Enhanced Report Settings' } initialOpen={false}>
							<SelectControl
								label={ 'Mode' }
								value={ attributes.mode }
								options={ [
									{ 
										label: __( 'Default - Use global setting', 'uncanny-ceu' ), 
										value: 'global' 
									},
									{ 
										label: __( 'Performance (Recommended)', 'uncanny-ceu' ),
										value: 'meta' 
									},
									{ 
										label: __( 'Ludicrous (Fastest, report updates hourly)', 'uncanny-ceu' ), 
										value: 'json' 
									},
									{ 
										label: __( 'Legacy', 'uncanny-ceu' ),
										value: 'legacy' 
									},
								] }
								onChange={(value) => {
									setAttributes({ mode: value });
								}}
							/>
							<div style={{ marginBottom: '14px' }}>
								<label>
									{ __( 'Action buttons', 'uncanny-ceu' ) }
								</label>
							</div>
							<ToggleControl
								label={ __( 'Allow adding CEUs', 'uncanny-ceu' ) }
								checked={ attributes.add === 'on' }
								onChange={(value) => {
									const add = value ? 'on' : null;
									setAttributes({ add: add });
								}}
								help={ __( 'Enable to allow users to add CEUs.', 'uncanny-ceu' ) }
							/>
							<ToggleControl
								label={ __( 'Allow editing CEUs', 'uncanny-ceu' ) }
								checked={ attributes.edit === 'on' }
								onChange={(value) => {
									const edit = value ? 'on' : null;
									setAttributes({ edit: edit });
								}}
								help={ __( 'Enable to allow users to edit CEUs.', 'uncanny-ceu' ) }
							/>
							<ToggleControl
								label={ __( 'Allow deleting CEUs', 'uncanny-ceu' ) }
								checked={ attributes.delete === 'on' }
								onChange={(value) => {
									const del = value ? 'on' : null;
									setAttributes({ delete: del });
								}}
								help={ __( 'Enable to allow users to delete CEUs.', 'uncanny-ceu' ) }
							/>
						</PanelBody>
					</Fragment>
				}
			</InspectorControls>
		</Fragment>
	);
};

export default CeuCourseReportSidebar;