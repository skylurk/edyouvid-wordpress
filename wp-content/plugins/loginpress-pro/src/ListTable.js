import React, { useState, useRef, useEffect } from 'react';
import { __ } from '@wordpress/i18n';

const ListTable = ({
  data,
  isLoading,
  listType,
  onRemove,
  onClearAll,
  perPage,
  onPerPageChange,
  searchTerm,
  onSearchChange,
  onSort,
  sortBy,
  sortOrder,
  totalEntries = 0,
  totalUnfilteredEntries = 0,
  currentPage = 1,
  message
}) => {
  const [isDropdownOpen, setIsDropdownOpen] = useState(false);
	const [isSearchFocused, setIsSearchFocused] = useState(false);
  const dropdownRef = useRef(null);
  
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
  
  // Define available page sizes
  const pageSizes = [10, 25, 50, 100];
  
  // Function to handle option selection
  const handleOptionSelect = (value) => {
    onPerPageChange(Number(value));
    setIsDropdownOpen(false);
  };

  // Function to toggle dropdown
  const toggleDropdown = () => {
    setIsDropdownOpen(!isDropdownOpen);
  };

  // Clear search function
	const _clearSearch = () => {
    onSearchChange('');
  };

  return (
    <div className="loginpress-data-table">
      {/* Top Controls */}
      <div className='loginpress-table-filter-wrapper'>
	  <div className="loginpress-row-per-page">
        <div className="row-per-page">
          <span>{__('Show Entries', 'loginpress-pro')}</span>
          <div className="select selectbox" ref={dropdownRef}>
            <select 
              id={`loginpress_limit_login_${listType}_select`} 
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
        <div className="bulk_option_wrapper">
          <button
            type="button"
            id={`loginpress_limit_bulk_${listType}s_submit`}
            className="button"
            onClick={onClearAll}
          >
            {__('Clear All', 'loginpress-pro')}
          </button>
        </div>
        
        
      </div>
      {message.text && (
        <div className={`message ${message.type}`}>
          <p>{message.text}</p>
		  <i className="dashicons dashicons-no-alt"></i>
        </div>
      )}
	  
	  <div className="loginpress-row-per-page-search loginpress-limit-bw-table-search">
          <span style={{color: '#5C7697'}}>{__('Search:', 'loginpress-pro')}</span>
          <div className="search-input-wrapper">
            <input
              type="search"
              placeholder={__('Enter Keyword', 'loginpress-pro')}
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
	  </div>

      {/* Table */}
      {isLoading ? (
        <div className="loginpress-table-wrapper dataTable">
        <table
          id={`loginpress_limit_login_${listType}`}
          className={`loginpress-limit-login-table dataTable loginpress_limit_login_${listType}`}
          cellSpacing="0"
          width="100%"
        >
          <thead>
            <tr>
            <th onClick={() => onSort('ip')} className={`sorting ${sortBy === 'ip' ? 'sorting_asc' : 'sorting_desc'}`} style={{ cursor: 'pointer' }}>
              {__('IP', 'loginpress-pro')}
            </th>
              <th>{__('Action', 'loginpress-pro')}</th>
            </tr>
          </thead>
          <tbody>
            {data.length > 0 ? (
              data.map((row) => (
                <tr key={row.ip}>
                  <td className={`loginpress_limit_login_${listType}_ips`} data-ip={row.ip}>
                    <div className="lp-tbody-cell">{row.ip}</div>
                  </td>
                  <td className={`loginpress_limit_login_${listType}_actions`}>
                    <div className="lp-tbody-cell">
                      <button
                        className={`loginpress-${listType}-clear button button-primary`}
                        onClick={() => onRemove(row.ip)}
                      >
                        {__('Remove', 'loginpress-pro')}
                      </button>
                    </div>
                  </td>
                </tr>
              ))
            ) : (
              <tr>
                <td>
				<div className='lp-tbody-cell'>
                  <div className='skeleton-paragraph'></div>
				  </div>
                </td>
				<td>
				<div className='lp-tbody-cell'>
                  <div className='skeleton-paragraph'></div>
				  </div>
                </td>
              </tr>
            )}
          </tbody>
        </table>
		</div>
      ) : (
		<div className="loginpress-table-wrapper">
        <table
          id={`loginpress_limit_login_${listType}`}
          className={`loginpress-limit-login-table dataTable loginpress_limit_login_${listType}`}
          cellSpacing="0"
          width="100%"
        >
          <thead>
            <tr>
            {(!isLoading && data.length > 0) ? (
              <th onClick={() => onSort('ip')} className={
                'sorting' +
                (sortBy && sortBy === 'ip'
                  ? sortOrder === 'asc'
                    ? ' sorting_asc'
                    : ' sorting_desc'
                  : '')
              } style={{ cursor: 'pointer' }}>
                {__('IP', 'loginpress-pro')}
              </th>
            ) : (
              <th>{__('IP', 'loginpress-pro')}</th>
            )}
              <th>{__('Action', 'loginpress-pro')}</th>
            </tr>
          </thead>
          <tbody>
            {data.length > 0 ? (
              data.map((row) => (
                <tr key={row.ip}>
                  <td className={`loginpress_limit_login_${listType}_ips`} data-ip={row.ip}>
                    <div className="lp-tbody-cell">{row.ip}</div>
                  </td>
                  <td className={`loginpress_limit_login_${listType}_actions`}>
                    <div className="lp-tbody-cell">
                      <button
                        className={`loginpress-${listType}-clear button button-primary`}
                        onClick={() => onRemove(row.ip)}
                      >
                        {__('Remove', 'loginpress-pro')}
                      </button>
                    </div>
                  </td>
                </tr>
              ))
            ) : (
              <tr>
                <td colSpan="2" className="loginpress-empty-data">
                  {__('No Data Available In Table', 'loginpress-pro')}
                </td>
              </tr>
            )}
          </tbody>
        </table>
		</div>
      )}
      
      {/* Entries Info */}
      <div className="showing-of-entries" style={{ marginTop: '20px' }}>
        <span>
          {searchTerm && searchTerm.trim() !== '' && totalUnfilteredEntries > 0
            ? __(`Showing ${totalEntries > 0 ? (currentPage - 1) * perPage + 1 : 0} to ${totalEntries > 0 ? Math.min(currentPage * perPage, totalEntries) : 0} of ${totalEntries} entries (filtered from ${totalUnfilteredEntries} total entries)`, 'loginpress-pro')
            : __(`Showing ${totalEntries > 0 ? (currentPage - 1) * perPage + 1 : 0} to ${totalEntries > 0 ? Math.min(currentPage * perPage, totalEntries) : 0} of ${totalEntries} entries`, 'loginpress-pro')
          }
        </span>
      </div>
    </div>
  );
};

export default ListTable;
