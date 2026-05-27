/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { TextControl, Button, Icon } from '@wordpress/components';
import { useState, useEffect, useCallback } from '@wordpress/element';

/**
 * Internal dependencies
 */
import './styles.scss';

/**
 * Render a single column item.
 * 
 * @param {Object} props
 * @param {String} props.index
 * @param {String} props.value
 * @param {String} props.label
 * @param {Boolean} props.isDefault
 * @param {Array} props.columns
 * @param {Function} props.onUpdate
 * 
 * @return {Component}
 */
const CeuCourseReportColumnItem = ({ index, value, label, isDefault, columns, onUpdate }) => {
	
	const [localValue, setLocalValue] = useState(value);
	const [localLabel, setLocalLabel] = useState(label);
	
	/**
	 * Debounce the update function.
	 */
	const debouncedUpdate = useCallback(
		(() => {
			let timeoutId;
			return (func, delay) => {
				return (...args) => {
					if (timeoutId) {
						clearTimeout(timeoutId);
					}
					timeoutId = setTimeout(() => {
						func(...args);
					}, delay);
				};
			};
		})()(newColumns => {
			onUpdate(newColumns);
		}, 100),
		[onUpdate]
	);
	
	/**
	 * Update local state when props change.
	 */
	useEffect(() => {
		setLocalValue(value);
		setLocalLabel(label);
	}, [value, label]);

	/**
	 * Handle column update.
	 *
	 * @param {String} key
	 * @param {String} value
	 *
	 * @return {void}
	 */
	const handleUpdate = ( key, value ) => {
		const newColumns = columns.map((c, i) => {
			if (i === index) {
				return { ...c, [key]: value };
			}
			return c;
		});
		debouncedUpdate(newColumns);
	}

	/**
	 * Render the component.
	 */
	return (
		<div className="ceu-course-report-inner-column-item">
			<TextControl
				key={'column-item-label-' + index}
				label={__('Label', 'uncanny-ceu')}
				value={label}
				type="text"
				onChange={(result) => {
					setLocalLabel(result);
					handleUpdate('label', result);
				}}
			/>
			<TextControl
				key={'column-item-value-' + index}
				label={__('Data key', 'uncanny-ceu')}
				value={value}
				type="text"
				readOnly={isDefault}
				onChange={(result) => {
					setLocalValue(result);
					handleUpdate('value', result);
				}}
			/>
			<Button
				className='ceu-course-report-inner-column-item__remove'
				key={'column-item-remove-button-' + index}
				isDestructive
				onClick={() => {
					const newColumns = columns.filter((c, i) => i !== index);
					onUpdate(newColumns);
				}}
				title={__('Remove column', 'uncanny-ceu')}
				aria-label={__('Remove column', 'uncanny-ceu')}
			>
				<Icon icon="no-alt" />
			</Button>
		</div>
	);
};

export default CeuCourseReportColumnItem;