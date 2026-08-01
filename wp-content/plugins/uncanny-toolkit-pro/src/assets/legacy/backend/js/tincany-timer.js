function tc_table_data_pro_timer(tableData, tableType, ID) {
    // Prefer the new window.tincannyDataObject, fallback to legacy dataObject
    const dataObj = (typeof window !== 'undefined' && window.tincannyDataObject)
        ? window.tincannyDataObject
        : (typeof dataObject !== 'undefined' ? dataObject : null);

    if (!dataObj || !dataObj.additionalData || !dataObj.additionalData.coursesOverview) {
        // Optionally handle the error or return tableData as is
        return tableData;
    }

    if ('coursesOverviewTable' === tableType) {
        for (let data in tableData) {
            tableData[data].avgTimeComplete = dataObj.additionalData.coursesOverview[tableData[data].ID].avgTimeComplete;
            tableData[data].avgTimeSpent = dataObj.additionalData.coursesOverview[tableData[data].ID].avgTimeSpent;
        }
    }

    if ('courseSingleOverviewSummaryTable' === tableType) {
        tableData[0].avgTimeComplete = dataObj.additionalData.coursesOverview[ID].avgTimeComplete;
    }

    return tableData;
}

function tc_table_headings_pro_timer(headings, tableType) {

    if ('coursesOverviewTable' === tableType) {
		let header_index = headings.length;
		let is_already_exiting = false;
		for (let i = 0; i < header_index; i++) {
			if (headings[i].data === 'avgTimeComplete') {
				is_already_exiting = true;
			}
		}
		if( ! is_already_exiting ) {
			headings[header_index] = {data: 'avgTimeComplete', title: uoPro.ls('Avg Time To Complete')};
			header_index++;
			headings[header_index] = {data: 'avgTimeSpent', title: uoPro.ls('Avg Time Spent')};
		}
    }

    if ('courseSingleOverviewSummaryTable' === tableType) {
		let header_index = headings.length;
		let is_already_exiting = false;
		for ( let i = 0; i < header_index; i++) {
			if (headings[i].data === 'avgTimeComplete') {
				is_already_exiting = true;
			}
		}
		if( ! is_already_exiting ) {
			headings[header_index] = {data: 'avgTimeComplete', title: uoPro.ls('Avg Time To Complete')};
		}
    }

    if ('courseSingleTable' === tableType) {
		let header_index = headings.length;
		let is_already_exiting = false;
		for (let i = 0; i < header_index; i++) {
			if (headings[i].data === 'timeComplete') {
				is_already_exiting = true;
			}
		}
		if( ! is_already_exiting ) {
			headings[header_index] = {data: 'timeComplete', title: uoPro.ls('Time To Complete')};
			header_index++;
			headings[header_index] = {data: 'timeSpent', title: uoPro.ls('Time Spent')};
		}
    }

    if ('userSingleCoursesOverviewTable' === tableType) {
		let header_index = headings.length;
		let is_already_exiting = false;
		for (let i = 0; i < header_index; i++) {
			if (headings[i].data === 'timeComplete') {
				is_already_exiting = true;
			}
		}
		if( ! is_already_exiting ) {
			headings[header_index] = {data: 'timeComplete', title: uoPro.ls('Time To Complete')};
			header_index++;
			headings[header_index] = {data: 'timeSpent', title: uoPro.ls('Time Spent')};
		}
    }

    if ('userSingleCourseProgressSummaryTable' === tableType) {
		let header_index = headings.length;
		let is_already_exiting = false;
		for ( let i = 0; i < header_index; i++) {
			if (headings[i].data === 'timeComplete') {
				is_already_exiting = true;
			}
		}
		if( ! is_already_exiting ) {
			headings[header_index] = {data: 'timeComplete', title: uoPro.ls('Time To Complete')};
			header_index++;
			headings[header_index] = {data: 'timeSpent', title: uoPro.ls('Time Spent')};
		}
    }

    return headings;
}

function tc_table_data_pro_timer_drawUserSingleCoursesOverviewTable(rowData, row) {

    if (typeof row !== 'undefined') {
        rowData.timeComplete = row.timeComplete;
        rowData.timeSpent = row.timeSpent;
    } else {
        rowData.timeComplete = '---';
        rowData.timeSpent = '---';
    }

    return rowData;

}

function tc_table_data_pro_timer_drawcourseSingleTableData(rowData, row, response) {

    if (typeof response !== 'undefined') {
        rowData.timeComplete = response.data[row].timeComplete;
        rowData.timeSpent = response.data[row].timeSpent;
    } else {
        rowData.timeComplete = '---';
        rowData.timeSpent = '---';
    }

    return rowData;

}

function tc_table_data_pro_timer_userSingleCourseProgressSummaryTable(rowData, row, response) {

    if (typeof response !== 'undefined') {
        rowData.timeComplete = response.data.timeComplete;
        rowData.timeSpent = response.data.timeSpent;
    } else {
        rowData.timeComplete = '';
        rowData.timeSpent = '';
    }

    return rowData;

}

if (typeof wp.hooks !== 'undefined') {

    wp.hooks.addFilter('tc_table_headings', 'tc', tc_table_headings_pro_timer, 10);
    wp.hooks.addFilter('tc_table_data', 'tc', tc_table_data_pro_timer, 10);
    wp.hooks.addFilter('tc_table_data_drawUserSingleCoursesOverviewTable', 'tc', tc_table_data_pro_timer_drawUserSingleCoursesOverviewTable, 10, 2);
    wp.hooks.addFilter('tc_table_data_drawcourseSingleTableData', 'tc', tc_table_data_pro_timer_drawcourseSingleTableData, 10, 3);
    wp.hooks.addFilter('tc_table_data_userSingleCourseProgressSummaryTable', 'tc', tc_table_data_pro_timer_userSingleCourseProgressSummaryTable, 10, 3);
}

let uoPro = {
    ls: function (string) {

        if (
            typeof tincannyTimer !== 'undefined' &&
            typeof tincannyTimer.localizedStrings !== 'undefined' &&
            typeof tincannyTimer.localizedStrings[string] !== 'undefined'
        ) {
            string = tincannyTimer.localizedStrings[string];
        }

        return string
    }
}


