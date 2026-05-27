const {__} = wp.i18n;

const {
	addFilter
} = wp.hooks;

const {
	SelectControl,
	PanelBody,
	TextControl,
	TextareaControl,
	ToggleControl,
} = wp.components;

const {
	Fragment
} = wp.element;

const {
	createHigherOrderComponent
} = wp.compose;

const {
	InspectorControls
} = wp.editor;

export const addEditGroupsUOSettings = createHigherOrderComponent((BlockEdit) => {
	return (props) => {
		// Check if we have to do something
		if (props.name == 'uncanny-learndash-groups/uo-groups-edit-group' && props.isSelected) {

			return (
				<Fragment>
					<BlockEdit {...props} />
					<InspectorControls>

						<PanelBody title={__('Edit Group Settings', 'uncanny-learndash-groups')}>

							<SelectControl
								label={__('Group Parent Selector', 'uncanny-learndash-groups')}
								value={props.attributes.groupParent}
								options={ [
									{ label: 'Show', value: 'show' },
									{ label: 'Hide', value: 'hide' },
								] }
								onChange={(value) => {
									props.setAttributes({groupParent: value});
								}}
							/>

							<SelectControl
								label={__('Group Name Selector', 'uncanny-learndash-groups')}
								value={props.attributes.groupName}
								options={ [
									{ label: 'Show', value: 'show' },
									{ label: 'Hide', value: 'hide' },
								] }
								onChange={(value) => {
									props.setAttributes({groupName: value});
								}}
							/>

							<SelectControl
								label={__('Total Seats Selector', 'uncanny-learndash-groups')}
								value={props.attributes.totalSeats}
								options={ [
									{ label: 'Show', value: 'show' },
									{ label: 'Hide', value: 'hide' },
								] }
								onChange={(value) => {
									props.setAttributes({totalSeats: value});
								}}
							/>

							<SelectControl
								label={__('Group Courses Selector', 'uncanny-learndash-groups')}
								value={props.attributes.groupCourses}
								options={ [
									{ label: 'Show', value: 'show' },
									{ label: 'Hide', value: 'hide' },
								] }
								onChange={(value) => {
									props.setAttributes({groupCourses: value});
								}}
							/>

							<SelectControl
								label={__('Group Image Selector', 'uncanny-learndash-groups')}
								value={props.attributes.groupImage}
								options={ [
									{ label: 'Show', value: 'show' },
									{ label: 'Hide', value: 'hide' },
								] }
								onChange={(value) => {
									props.setAttributes({groupImage: value});
								}}
							/>

							<TextControl
								label={ __( 'Category (Slug(s) seperated by commas)', 'uncanny-learndash-groups' ) }
								value={ props.attributes.category }
								type="string"
								onChange={ ( value ) => {
									props.setAttributes({
										category: value
									});
								}}
							/>

							<TextControl
								label={ __( 'Course Category (Slug(s) seperated by commas)', 'uncanny-learndash-groups' ) }
								value={ props.attributes.courseCategory }
								type="string"
								onChange={ ( value ) => {
									props.setAttributes({
										courseCategory: value
									});
								}}
							/>

						</PanelBody>

					</InspectorControls>
				</Fragment>
			);
		}

		return <BlockEdit {...props} />;
	};
}, 'addEditGroupsUOSettings');

addFilter('editor.BlockEdit', 'uncanny-learndash-groups/uo-groups-edit-group', addEditGroupsUOSettings);

