import React from 'react';
import { useSortable } from '@dnd-kit/sortable';
import { CSS } from '@dnd-kit/utilities';

export const SortableItem = ({ id, children, disabled }) => {
  const {
    attributes,
    listeners,
    setNodeRef,
    transform,
    transition,
    isDragging,
  } = useSortable({ id, disabled });

  const style = {
    transform: CSS.Transform.toString(transform),
    transition: transition || undefined,
    opacity: isDragging ? 0.5 : 1,
    zIndex: isDragging ? 100 : 'auto',
    position: 'relative',
  };

  return (
    <div 
      ref={setNodeRef} 
      style={style}
      {...attributes}
    >
      <div className="drag-handle" {...listeners}><span className="dashicons dashicons-screenoptions"></span></div>
      
      {children}
    </div>
  );
};