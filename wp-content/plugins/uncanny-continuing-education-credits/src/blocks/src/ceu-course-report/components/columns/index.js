/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { Draggable, Icon } from '@wordpress/components';
import { Fragment, useRef } from '@wordpress/element';


/**
 * Internal dependencies
 */
import CeuCourseReportColumnItem from '../column';
import CeuCourseReportAddColumnItem from '../add-column';
import './styles.scss';

/**
 * Draggable column list component.
 * 
 * @param {Object} props
 * @param {String} props.columnString
 * @param {Array} props.defaults
 * @param {Function} props.onUpdate
 * 
 * @returns {Component}
 */
const CeuCourseReportColumns = ( { columnString, defaults, onUpdate } ) => {
	
	// Drag references.
	const dragIndexRef = useRef(null);
	const dragGhostRef = useRef(null);

	// Bail early if no column string is provided.
	if ( null === columnString ) {
		return null;
	}

	/**
	 * Generate a CSV string of column key||label pairs.
	 * 
	 * @param {Array} columns
	 * 
	 */
	const columns = columnString.split( ',' ).map( column => {
		const [ value, label ] = column.split( '||' );
		return { value : value, label : label };
	});

	/**
	 * Handle drag start event.
	 * 
	 * @param {Object} e - Event object.
	 * @param {Number} index - Index of the dragged item.
	 * 
	 * @return {void}
	 */
	const _handleDragStart = (e, index, liElement) => {
		dragIndexRef.current = index;
		e.dataTransfer.effectAllowed = 'move';
		e.dataTransfer.setData('text/plain', index); // Optional compatibility
		
		// Create a custom drag image (ghost element)
		const ghostElement = liElement.cloneNode(true);
		// Apply the same dimensions as the original <li>
		const { width, height } = liElement.getBoundingClientRect();
		ghostElement.style.width = `${width}px`;
		ghostElement.style.height = `${height}px`;
		ghostElement.classList.add('ceu-course-report-column-item__drag-ghost');
		document.body.appendChild(ghostElement);
		dragGhostRef.current = ghostElement;
		e.dataTransfer.setDragImage(ghostElement, 0, 0);
		
		liElement.style.opacity = '0.2'; // Reduce opacity for visual feedback
	};

	/**
	 * Handle drop event.
	 * 
	 * @param {Object} e - Event object.
	 * @param {Number} dropIndex - Index of the drop target.
	 * 
	 * @return {void}
	 */
	const _handleDrop = (e, dropIndex) => {
		e.preventDefault();
		
		// Get the dragged index and bail if it's the same as the drop index.
		const draggedIndex = dragIndexRef.current;
		if (draggedIndex === dropIndex) return;
		
		// Reorder the columns array.
		const updatedOrder = [...columns];
		const [movedItem] = updatedOrder.splice(draggedIndex, 1);
		updatedOrder.splice(dropIndex, 0, movedItem);
		
		// Remove the ghost element from the DOM
		if (dragGhostRef.current) {
			document.body.removeChild(dragGhostRef.current);
			dragGhostRef.current = null;
		}

		// Update the column order.
		onUpdate(updatedOrder);
	};
	
	/**
	 * Render the component.
	 */
	return (
		<Fragment>
			<label>{ __( 'Columns', 'uncanny-ceu' ) }</label>
			<ul className="ceu-course-report-column-ul">
			{columns.map((item, index) => (
				<Draggable
				    key={'column-item-' + item.value} 
					draggableId={String(item.value)} 
					index={index}
				>
				{() => (
					<li
						ref={(el) => (item.liElement = el)} 
						className='ceu-course-report-column-item'
						onDragOver={(e) => e.preventDefault() }
						onDrop={(e) => _handleDrop(e, index)}
					>
						<span
							className="ceu-course-report-column-item__drag-handle"
							draggable="true"
							onDragStart={(e) => _handleDragStart(e, index, item.liElement)}
							onDragEnd={() => item.liElement.style.opacity = '1'}
						>
							<Icon icon="menu" />
						</span>
						
						<CeuCourseReportColumnItem
						    key={'column-item-inner-' + index}
							index={ index }
							value={ item.value }
							label={ item.label }
							isDefault={ defaults.find( c => c.value === item.value ) }
							columns={ columns }
							onUpdate={ onUpdate }
						/>
					</li>
				)}
				</Draggable>
			))}
			</ul>
			<CeuCourseReportAddColumnItem
				columns={ columns }
				defaults={ defaults }
				onUpdate={ onUpdate }
			/>
		</Fragment>
	);
};

export default CeuCourseReportColumns;
