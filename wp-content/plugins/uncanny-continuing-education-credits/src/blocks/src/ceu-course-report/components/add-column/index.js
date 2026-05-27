/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';

import { 
	SelectControl, 
	TextControl, 
	Button, 
	Icon 
} from '@wordpress/components';

import { 
	useState, 
	useEffect 
} from '@wordpress/element';

/**
 * Internal dependencies
 */
import './styles.scss';

/**
 * Add column item component.
 * 
 * @param {Object} props
 * @param {Array} props.columns
 * @param {Array} props.defaults
 * @param {Function} props.onUpdate
 * 
 * @return {Component}
 */
const CeuCourseReportAddColumnItem = ({ columns, defaults, onUpdate }) => {

	// Add new column constants.
	const addNewKey = 'addNewCEUColumn';
	const newLabelDefault = __('New Column', 'uncanny-ceu');
	const newKeyDefault = 'new_column';

	// Local state.
	const [selected, setSelected] = useState( null );
	const [options, setOptions] = useState( [] );
	const [isReady, setIsReady] = useState( false );
	const [newLabel, setNewLabel] = useState( newLabelDefault );
	const [newDataKey, setNewDataKey] = useState( newKeyDefault );
	const [error, setError] = useState('');
	
	/**
	 * On mount, set required state variables.
	 */
	useEffect(() => {
		// Generate options from defaults that are not already in the columns array
		const options = defaults.filter(column => {
			return !columns.find(c => c.value === column.value);
		});
		
		// Add - Add New Column option.
		options.push({ value: addNewKey, label: __('Add Custom Column', 'uncanny-ceu') });
		
		// Set states.
		setOptions(options);
		setSelected(options[0].value);
		setIsReady(true);
		setError('');
	}, [columns, defaults]);

	/**
	 * Handle add new column click.
	 * 
	 * @returns {void}
	 */
	const _handleAddColumn = () => {
		// Handle default column.
		if ( selected !== addNewKey ) { 
			// Use the selected column from the options.
			_addColumn( { ...options.find(o => o.value === selected) } );
			return;
		}
				
		// Validate custom column label and data key
		if (!newLabel.trim() || !newDataKey.trim()) {
			setError(__('Both label and data key are required.', 'uncanny-ceu'));
			return;
		}
		
		// Check if the new data key is unique
		if (columns.find(c => c.value === newDataKey)) {
			setError(__('Data key must be unique.', 'uncanny-ceu'));
			return;
		}

		// Add the new column.
		_addColumn({ value: newDataKey, label: newLabel });
						
		// Reset the form and any errors.
		setNewLabel(newLabelDefault);
		setNewDataKey(newKeyDefault);
		setError('');
	};

	/**
	 * Add a new column to the columns array.
	 * 
	 * @param {Object} column - Column object.
	 * 
	 * @returns {void}
	 */
	const _addColumn = ( column ) => {
		const newColumns = [ ...columns, column ];
		onUpdate(newColumns);
	}

	// Bail if not ready.
	if (!isReady) {
		return null;
	}

	/**
	 * Render the component.
	 */
	return (
		<fieldset className="ceu-course-report-add-column">
			<SelectControl
				label={__('Add Column', 'uncanny-ceu')}
				value={selected}
				options={options}
				onChange={(value) => {
					setSelected(value);
					setError('');
				}}
			/>
			{ selected === 'addNewCEUColumn' &&
			<div className="ceu-course-report-add-column__form">
				<TextControl
					key="new-label"
					label={__('Label', 'uncanny-ceu')}
					value={newLabel}
					type="text"
					onChange={(value) => {
						setNewLabel(value);
						setError('');
					}}
				/>
				<TextControl
					key="new-data-key"
					label={__('Data key', 'uncanny-ceu')}
					value={newDataKey}
					type="text"
					onChange={(value) => {
						setNewDataKey(value);
						setError('');
					}}
				/>
				{error &&
					<p className="ceu-course-report-add-column__error">{error}</p>
				}
			</div>
			}
			<Button
			    className='ceu-course-report-add-column__button'
				variant='primary'
				onClick={_handleAddColumn}
				icon='plus'
			>
				{ selected === 'addNewCEUColumn' 
					? __('Add custom column', 'uncanny-ceu') 
					: __('Add column', 'uncanny-ceu' )
				}
			</Button>
		</fieldset>
	);
}

export default CeuCourseReportAddColumnItem;