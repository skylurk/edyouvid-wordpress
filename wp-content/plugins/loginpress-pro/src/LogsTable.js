import React, { useState, useRef, useEffect } from "react";
import { __ } from "@wordpress/i18n";

const LoginAttemptsTable = ({
  data,
  onSort,
  sortBy,
  sortOrder,
  isLoading,
  selectedRows,
  onSelectRow,
  onSelectAll,
  onBulkAction,
  onClearAll,
  onAction,
  perPage,
  onPerPageChange,
  searchTerm,
  onSearchChange,
  statusFilter,
  onStatusFilterChange,
  message
}) => {
  const [isDropdownOpen, setIsDropdownOpen] = useState(false);
  const [isBulkDropdownOpen, setIsBulkDropdownOpen] = useState(false);
  const [selectedBulkAction, setSelectedBulkAction] = useState("");
	const [isSearchFocused, setIsSearchFocused] = useState(false);
  const dropdownRef = useRef(null);
  const bulkDropdownRef = useRef(null);
  const [submitError, setSubmitError] = useState('');
  const [loadingRowIp, setLoadingRowIp] = useState(null);
  // Hide dropdown on outside click
  useEffect(() => {
    if (!isDropdownOpen) return;
    const handleClickOutside = (event) => {
      if (dropdownRef.current && !dropdownRef.current.contains(event.target)) {
        setIsDropdownOpen(false);
      }
    };
    document.addEventListener("mousedown", handleClickOutside);
    return () => document.removeEventListener("mousedown", handleClickOutside);
  }, [isDropdownOpen]);

  // Hide bulk dropdown on outside click
  useEffect(() => {
    if (!isBulkDropdownOpen) return;
    const handleClickOutside = (event) => {
      if (bulkDropdownRef.current && !bulkDropdownRef.current.contains(event.target)) {
        setIsBulkDropdownOpen(false);
      }
    };
    document.addEventListener("mousedown", handleClickOutside);
    return () => document.removeEventListener("mousedown", handleClickOutside);
  }, [isBulkDropdownOpen]);

  // Define available page sizes
  const pageSizes = [10, 25, 50, 100];
  
  // Define bulk action options
  const bulkActions = [
    { value: "", label: __("Bulk Action", "loginpress-pro") },
    { value: "unlock", label: __("Unlock", "loginpress-pro") },
    { value: "whitelist", label: __("Whitelist", "loginpress-pro") },
    { value: "blacklist", label: __("Blacklist", "loginpress-pro") }
  ];
  
  // Function to handle option selection
  const handleOptionSelect = (value) => {
    onPerPageChange(Number(value));
    setIsDropdownOpen(false);
  };

  // Function to toggle dropdown
  const toggleDropdown = () => {
    setIsDropdownOpen(!isDropdownOpen);
  };

  // Function to handle bulk action selection
  const handleBulkActionSelect = (value) => {
    setSelectedBulkAction(value);
  setIsBulkDropdownOpen(false);
  };

  // Function to toggle bulk dropdown
  const toggleBulkDropdown = () => {
    setIsBulkDropdownOpen(!isBulkDropdownOpen);
  };

  // Clear search function
	const _clearSearch = () => {
    onSearchChange('');
  };

  // Filter data by status
  const filteredData =
    statusFilter === "all"
      ? data
      : data.filter((row) => {
          if (!row.login_status) return false;
          if (statusFilter === "failed")
            return row.login_status.toLowerCase() === "failed";
          if (statusFilter === "blocked")
            return row.login_status.toLowerCase() === "locked";
          if (statusFilter === "success")
            return row.login_status.toLowerCase() === "success";
          return true;
        });

  return (
    <div id="loginpress_limit_logs" style={{ display: "block" }}>
      
      {/* Legend */}
      <div className="loginpress_limit_login_log_def">
        <div className="loginpress_limit_login_log_definition">
          <span className="loginpress-attempts-unlock"></span>
          <p>
            {__(
              "Unlock/Deletes certain IP address from the database.",
              "loginpress-pro"
            )}
          </p>
        </div>
        <div className="loginpress_limit_login_log_definition">
          <span className="loginpress-attempts-whitelist"></span>
          <p>
            {__(
              "Move certain IP address to whitelist so Login Attempts are not applied on them",
              "loginpress-pro"
            )}
          </p>
        </div>
        <div className="loginpress_limit_login_log_definition">
          <span className="loginpress-attempts-blacklist"></span>
          <p>
            {__(
              "Move certain IP address to blacklist so a certain IP address couldn't access your login page.",
              "loginpress-pro"
            )}
          </p>
        </div>
      </div>

      {/* Bulk actions */}
      <div className="bulk_option_wrapper">
        <div className="bulk_option">
          <div className="select selectbox" ref={bulkDropdownRef}>
            <select
              className="loginpress_limit_bulk_blacklist s-hidden"
              onChange={(e) => onBulkAction(e.target.value)}
              disabled={selectedRows.length === 0}
              value={selectedBulkAction}
            >
              <option value="">{__("Bulk Action", "loginpress-pro")}</option>
              <option value="unlock">{__("Unlock", "loginpress-pro")}</option>
              <option value="whitelist">
                {__("Whitelist", "loginpress-pro")}
              </option>
              <option value="blacklist">
                {__("Blacklist", "loginpress-pro")}
              </option>
            </select>
            <div className="styledSelect" onClick={toggleBulkDropdown}>
              <span className="text-ellipses valueAdded">
                {selectedBulkAction ? bulkActions.find(action => action.value === selectedBulkAction)?.label : __("Bulk Action", "loginpress-pro")}
              </span>
            </div>
            <ul className="options" style={{ display: isBulkDropdownOpen ? 'block' : 'none' }}>
              {bulkActions.map((action) => (
                <li 
                  key={action.value}
                  rel={action.value}
                  className={selectedBulkAction === action.value ? 'active' : ''}
                  onClick={() => handleBulkActionSelect(action.value)}
                >
                  {action.label}
                </li>
              ))}
            </ul>
          </div>
          <button
            id="loginpress_limit_bulk_blacklist_submit"
            className="button"
            onClick={() => {
              if (!selectedBulkAction) {
                setSubmitError(__('Please select at least one item to perform this action on.', 'loginpress-pro'));
                // Set a timer to clear the error after 3 seconds (3000 milliseconds)
                setTimeout(() => {
                  setSubmitError('');
                }, 3000);
                return;
              }
              if (selectedRows.length === 0) {
                setSubmitError(__('Please select at least one item to perform this action on.', 'loginpress-pro'));
                // Set a timer to clear the error after 3 seconds (3000 milliseconds)
                setTimeout(() => {
                  setSubmitError('');
                }, 3000);
                return;
              }
              setSubmitError(''); // Clear any previous errors
              onBulkAction(selectedBulkAction);
            }}
          >
            {__("Submit", "loginpress-pro")}
          </button>
          {submitError && (
            <div className="message error loginpress-llla-bulk-no-item llla-bulk-attempts">
              {submitError}
			  <span className='close-me' onClick={() => (setSubmitError(''))}><i className="dashicons dashicons-no-alt"></i></span>
            </div>
          )}
        </div>
		<div className="loginpress-row-per-page-search loginpress-limit-bw-table-search">
          <span>{__("Search:", "loginpress-pro")}</span>
          <div className="search-input-wrapper">
            <input
              type="search"
              placeholder={__("Enter Keyword", "loginpress-pro")}
              value={searchTerm}
              onChange={(e) => onSearchChange(e.target.value)}
              onFocus={() => setIsSearchFocused(true)}
              onBlur={() => setIsSearchFocused(false)}
            />
            
            {/* Cross icon (clear button) */}
            {/* {searchTerm && (
              <button
                type="button"
                className={`clear-search-btn ${!isSearchFocused ? 'show-on-hover' : ''}`}
                onClick={clearSearch}
                title={__("Clear search", "loginpress-pro")}
              >
                ×
              </button>
            )} */}
          </div>
        </div>
        <div className="dt-buttons">
          <button
            className="dt-button buttons-csv buttons-html5 button dt-buttons"
            onClick={(e) => {
              const nonce = window.loginpress_llla?.csv_nonce;
              const url = `${ajaxurl}?action=loginpress_lla_export_csv&security=${nonce}`;
              window.location.href = url;
              // Blur the button to remove focus state after click
              e.currentTarget.blur();
            }}
          >
            {__("Download CSV", "loginpress-pro")}
          </button>
        </div>
        <div className="bulk_clear">
          <button
            id="loginpress_limit_bulk_attempts_submit"
            className="button"
            onClick={onClearAll}
          >
            {__("Clear All", "loginpress-pro")}
          </button>
        </div>
      </div>
      {message.text && (
        <div className={`message ${message.type}`}>
          <p>{message.text}</p>
		  <i className="dashicons dashicons-no-alt"></i>
        </div>
      )}

      {/* Per page and search */}
      <div className="loginpress-row-per-page">
        <div className="loginpress-show-entries">
          <span>{__("Show Entries", "loginpress-pro")}</span>
          <div className="select selectbox" ref={dropdownRef}>
            <select 
              id="loginpress_limit_login_log_select" 
              className="selectbox s-hidden"
              value={perPage}
              onChange={(e) => onPerPageChange(Number(e.target.value))}
            >
              <option value="10">10</option>
              <option value="25">25</option>
              <option value="50">50</option>
              <option value="100">100</option>
            </select>
            <div className="styledSelect" onClick={toggleDropdown}>
              <span className="text-ellipses valueAdded">{perPage}</span>
            </div>
            <ul className="options" style={{ display: isDropdownOpen ? 'block' : 'none' }}>
              {pageSizes.map((size) => (
                <li 
                  key={size}
                  rel={size}
                  className={perPage === size ? 'active' : ''}
                  onClick={() => handleOptionSelect(size)}
                >
                  {size}
                </li>
              ))}
            </ul>
          </div>
        </div>
		<div
        className="loginpress-log-status-filters"
        // style={{ marginBottom: "16px", display: "flex", gap: "8px" }}
      >
        <button
          className={
            statusFilter === "all" ? "button button-primary" : "button"
          }
          onClick={() => onStatusFilterChange("all")}
        >
          {__("All", "loginpress-pro")}
        </button>
        <button
          className={
            statusFilter === "failed" ? "button button-primary" : "button"
          }
          onClick={() => onStatusFilterChange("failed")}
        >
          {__("Failed", "loginpress-pro")}
        </button>
        <button
          className={
            statusFilter === "blocked" ? "button button-primary" : "button"
          }
          onClick={() => onStatusFilterChange("blocked")}
        >
          {__("Blocked", "loginpress-pro")}
        </button>
        <button
          className={
            statusFilter === "success" ? "button button-primary" : "button"
          }
          onClick={() => onStatusFilterChange("success")}
        >
          {__("Successful", "loginpress-pro")}
        </button>
      </div>
        
      </div>
      {/* Filter Buttons */}
      

      {/* Table */}
	  <div className="loginpress-table-wrapper">
      <table
        id="loginpress_limit_login_log"
        className="loginpress-limit-login-table dataTable"
        cellSpacing="0"
        width="100%"
        style={{ display: "table" }}
      >
        <caption className="screen-reader-text">
          {__("Login Attempts Log", "loginpress-pro")}
        </caption>
        <thead>
          <tr>
            <th>
            <input
              type="checkbox"
              name="select_all"
              value="1"
              className="lla-select-all"
              checked={
                selectedRows.length === filteredData.length &&
                filteredData.length > 0
              }
              onChange={(e) => onSelectAll(e.target.checked)}
            />
            </th>
            {/* <th data-priority="1">{__('IP', 'loginpress-pro')}</th> */}
            <th onClick={() => onSort("ip")}  className={
              "sorting" +
              (sortBy && sortBy === "ip"
                ? sortOrder === "asc"
                  ? " sorting_asc"
                  : " sorting_desc"
                : "")
            }>
              {__("IP", "loginpress-pro")}
            </th>
            <th onClick={() => onSort("datentime")}  className={
              "sorting" +
              (sortBy && sortBy === "datentime"
                ? sortOrder === "asc"
                  ? " sorting_asc"
                  : " sorting_desc"
                : "")
            }>
              {__("Date & Time", "loginpress-pro")}
            </th>
            <th onClick={() => onSort("username")}  className={
              "sorting" +
              (sortBy && sortBy === "username"
                ? sortOrder === "asc"
                  ? " sorting_asc"
                  : " sorting_desc"
                : "")
            }>
              {__("Username", "loginpress-pro")}
            </th>
            <th>{__("Gateway", "loginpress-pro")}</th>
            <th>{__("Status", "loginpress-pro")}</th>
            <th data-priority="2">{__("Action", "loginpress-pro")}</th>
          </tr>
        </thead>
        <tbody>
		{isLoading ? (
			[...Array(10)].map((_, index) => (
			<tr key={`skeleton-${index}`}>
				<td><div className="lp-tbody-cell"><div className="skeleton-paragraph" style={{width: '20px', height: '20px'}} /></div></td>
				<td><div className="lp-tbody-cell"><div className="skeleton-paragraph" style={{width: '120px', height: '16px'}} /></div></td>
				<td><div className="lp-tbody-cell"><div className="skeleton-paragraph" style={{width: '150px', height: '16px'}} /></div></td>
				<td><div className="lp-tbody-cell"><div className="skeleton-paragraph" style={{width: '100px', height: '16px'}} /></div></td>
				<td><div className="lp-tbody-cell"><div className="skeleton-paragraph" style={{width: '80px', height: '16px'}} /></div></td>
				<td><div className="lp-tbody-cell"><div className="skeleton-paragraph" style={{width: '60px', height: '16px'}} /></div></td>
				<td><div className="lp-tbody-cell">
					<div style={{display: 'flex', gap: '5px'}}>
						<div className="skeleton-button" style={{width: '60px', height: '30px'}} />
						<div className="skeleton-button" style={{width: '60px', height: '30px'}} />
						<div className="skeleton-button" style={{width: '60px', height: '30px'}} />
					</div>
				</div></td>
			</tr>
			))
		) : filteredData.length > 0 ? (
            filteredData.map((row) => (
              <tr
                key={row.id}
                id={`loginpress_attempts_id_${row.id}`}
                data-login-attempt-user={row.id}
                data-ip={row.ip}
              >
                {loadingRowIp === row.ip ? (
                  <>
                    <td><div className="lp-tbody-cell"><div className="skeleton-paragraph" style={{width: '20px', height: '20px'}} /></div></td>
                    <td><div className="lp-tbody-cell"><div className="skeleton-paragraph" style={{width: '120px', height: '16px'}} /></div></td>
                    <td><div className="lp-tbody-cell"><div className="skeleton-paragraph" style={{width: '150px', height: '16px'}} /></div></td>
                    <td><div className="lp-tbody-cell"><div className="skeleton-paragraph" style={{width: '100px', height: '16px'}} /></div></td>
                    <td><div className="lp-tbody-cell"><div className="skeleton-paragraph" style={{width: '80px', height: '16px'}} /></div></td>
                    <td><div className="lp-tbody-cell"><div className="skeleton-paragraph" style={{width: '60px', height: '16px'}} /></div></td>
                    <td><div className="lp-tbody-cell">
                      <div style={{display: 'flex', gap: '5px'}}>
                        <div className="skeleton-button" style={{width: '60px', height: '30px'}} />
                        <div className="skeleton-button" style={{width: '60px', height: '30px'}} />
                        <div className="skeleton-button" style={{width: '60px', height: '30px'}} />
                      </div>
                    </div></td>
                  </>
                ) : (
                  <>
                    <td>
                      <input
                        type="checkbox"
                        name="id[]"
                        className="llla_inside_check"
                        checked={selectedRows.includes(row.id)}
                        onChange={(e) => onSelectRow(row.id, e.target.checked)}
                      />
                    </td>
                    <td className="lg_attempts_ip">
                      <div className="lp-tbody-cell">{row.ip}</div>
                    </td>
                    <td className="loginpress_limit_login_log_dates">
                      <div className="lp-tbody-cell">{row.datentime}</div>
                    </td>
                    <td className="loginpress_limit_login_log_usernames">
                      <div className="lp-tbody-cell">
                        <span className="attempts-sniper">
                          <img
                            src={`${loginpress_llla.img_url}`}
                            alt="Loading"
                          />
                        </span>
                        {row.username}
                      </div>
                    </td>
                    <td className="loginpress_limit_login_log_gateways">
                      <div className="lp-tbody-cell">{row.gateway}</div>
                    </td>
                    <td className="loginpress_limit_login_log_status">
                      <div className="lp-tbody-cell" style={{textAlign: 'center'}}>
                        {(() => {
                          if (!row.login_status) return '   -   ';
                          
                          switch (row.login_status.toLowerCase()) {
                            case 'success':
                              return __('Success', 'loginpress-pro');
                            case 'failed':
                              return __('Failed', 'loginpress-pro');
                            case 'locked':
                              return __('Locked', 'loginpress-pro');
                            default:
                              return row.login_status.charAt(0).toUpperCase() + row.login_status.slice(1);
                          }
                        })()}
                      </div>
                    </td>
                    <td className="loginpress_limit_login_log_actions">
                      <div className="lp-tbody-cell">
                        <div className="loginpress-attempts-unlock-wrapper">
                          <button
                            disabled={loadingRowIp === row.ip}
                            className="loginpress-attempts-unlock button button-primary"
                        onClick={async () => {
                          setLoadingRowIp(row.ip);
                          await onAction(row.ip, "unlock");
                          setLoadingRowIp(null);
                        }}
                      >
                        {__("Unlock", "loginpress-pro")}
                      </button>
                    </div>
                    <div className="loginpress-attempts-whitelist-wrapper">
                      <button
                        disabled={loadingRowIp === row.ip}
                        className="loginpress-attempts-whitelist button"
                        onClick={async () => {
                          setLoadingRowIp(row.ip);
                          await onAction(row.ip, "whitelist");
                          setLoadingRowIp(null);
                        }}
                      >
                        {__("Whitelist", "loginpress-pro")}
                      </button>
                    </div>
                    <div className="loginpress-attempts-blacklist-wrapper">
                      <button
                        disabled={loadingRowIp === row.ip}
                        className="loginpress-attempts-blacklist button"
                        onClick={async () => {
                          setLoadingRowIp(row.ip);
                          await onAction(row.ip, "blacklist");
                          setLoadingRowIp(null);
                        }}
                      >
                        {__("Blacklist", "loginpress-pro")}
                      </button>
                    </div>
                  </div>
                </td>
              </>
            )}
          </tr>
        ))
      ) : (
        <tr>
          <td colSpan="7" className="loginpress-empty-data">
            {searchTerm ? __("No matching records found", "loginpress-pro") : __("No Data Available In Table", "loginpress-pro")}
          </td>
        </tr>
      )}
    </tbody>
  </table>
</div>
	  
</div>
);
};

export default LoginAttemptsTable;
