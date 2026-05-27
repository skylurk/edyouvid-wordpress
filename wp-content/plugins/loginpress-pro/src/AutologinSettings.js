import React, { useState, useEffect, useCallback, useMemo, useRef } from 'react';
import { __, sprintf } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';
import './AutologinSettings.css';
import InfoBox from './InfoBox';

const AutoLogin = () => {
  const activeAddons = window.loginpressProData?.activeAddons || [];

  if (!activeAddons.includes('auto-login')) {
    return;
  }
  const [searchTerm, setSearchTerm] = useState('');
  const [debouncedSearchTerm, setDebouncedSearchTerm] = useState('');
  const [users, setUsers] = useState([]);
  const [searchSuggestions, setSearchSuggestions] = useState([]);
  const [showSuggestions, setShowSuggestions] = useState(false);
  const [isLoading, setIsLoading] = useState(false);
  const [isSearchLoading, setIsSearchLoading] = useState(false);
  const [rowsPerPage, setRowsPerPage] = useState(10);
  const [currentPage, setCurrentPage] = useState(1);
  const [activePopup, setActivePopup] = useState(null);
  const [activeUserId, setActiveUserId] = useState(null);
  const [popupData, setPopupData] = useState({});
  const [filterTerm, setFilterTerm] = useState('');
  const [debouncedFilterTerm, setDebouncedFilterTerm] = useState('');
  const [isDropdownOpen, setIsDropdownOpen] = useState(false);
  const [activeMenuId, setActiveMenuId] = useState(null); // Track which menu is currently open
  const [sortConfig, setSortConfig] = useState({ key: null, direction: 'asc' });
  const [newLinkMessage, setNewLinkMessage] = useState(null); // Track which user got a new link
  const [emailSentMessage, setEmailSentMessage] = useState(null); // Track which user got email sent
  const [message, setMessage] = useState(null); // State for success/error messages
  const [enabledStates, setEnabledStates] = useState({});
  const [isMainSearchFocused, setIsMainSearchFocused] = useState(false);
  const [isFilterSearchFocused, setIsFilterSearchFocused] = useState(false);
  const wrapperRef = useRef(null);

  // Close dropdown when clicked outside
  useEffect(() => {
    function handleClickOutside(event) {
      if (wrapperRef.current && !wrapperRef.current.contains(event.target)) {
        setShowSuggestions(false);
      }
    }

    document.addEventListener("mousedown", handleClickOutside);
    return () => {
      document.removeEventListener("mousedown", handleClickOutside);
    };
  }, []);
  // Ref for the dropdown
  const dropdownRef = useRef(null);
  
  // Refs for debouncing
  const searchTimeoutRef = useRef(null);
  const filterTimeoutRef = useRef(null);
  const searchApiTimeoutRef = useRef(null);
  
  // Cache for search results to avoid redundant API calls
  const searchCache = useRef(new Map());
  
  // Define available page sizes
  const pageSizes = [10, 25, 50, 100];
  
  // Debounce search term
  useEffect(() => {
    if (searchTimeoutRef.current) {
      clearTimeout(searchTimeoutRef.current);
    }
    
    searchTimeoutRef.current = setTimeout(() => {
      setDebouncedSearchTerm(searchTerm);
    }, 300);
    
    return () => {
      if (searchTimeoutRef.current) {
        clearTimeout(searchTimeoutRef.current);
      }
    };
  }, [searchTerm]);

  // Debounce filter term
  useEffect(() => {
    if (filterTimeoutRef.current) {
      clearTimeout(filterTimeoutRef.current);
    }
    
    filterTimeoutRef.current = setTimeout(() => {
      setDebouncedFilterTerm(filterTerm);
    }, 300);
    
    return () => {
      if (filterTimeoutRef.current) {
        clearTimeout(filterTimeoutRef.current);
      }
    };
  }, [filterTerm]);

  // Cleanup timeouts on unmount
  useEffect(() => {
    return () => {
      if (searchApiTimeoutRef.current) {
        clearTimeout(searchApiTimeoutRef.current);
      }
      // Clear search cache
      searchCache.current.clear();
    };
  }, []);

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
  
  // Function to handle option selection
  const handleOptionSelect = useCallback((value) => {
    setRowsPerPage(Number(value));
    setIsDropdownOpen(false);
  }, []);

  // Function to toggle dropdown
  const toggleDropdown = () => {
    setIsDropdownOpen(!isDropdownOpen);
  };

  // Clear main search function
  const clearMainSearch = () => {
    setSearchTerm('');
    setSearchSuggestions([]);
    setShowSuggestions(false);
  };

  // Clear filter search function
  const clearFilterSearch = () => {
    setFilterTerm('');
  };

  // Function to handle menu toggle - only one menu can be open at a time
  const handleMenuToggle = (userId) => {
    setActiveMenuId(activeMenuId === userId ? null : userId);
  };
  
  const handleSort = useCallback((key) => {
    setSortConfig((prev) => {
      if (prev.key === key) {
        return {
          key,
          direction: prev.direction === 'asc' ? 'desc' : 'asc',
        };
      } else {
        return { key, direction: 'asc' };
      }
    });
  }, []);

  // Fetch auto-login users
  const fetchAutoLoginUsers = useCallback(async () => {
    try {
      setIsLoading(true);
      const response = await apiFetch({
        path: 'loginpress/v1/autologin-users',
        method: 'GET',
      });
      setUsers(response.users || []);
    } catch (error) {
    } finally {
      setIsLoading(false);
    }
  }, []);

  useEffect(() => {
    fetchAutoLoginUsers();
  }, [fetchAutoLoginUsers]);

  // Handle search input changes with debouncing
  const handleSearchChange = useCallback(async (e) => {
    const value = e.target.value;
    setSearchTerm(value);
    
    // Clear previous timeout
    if (searchApiTimeoutRef.current) {
      clearTimeout(searchApiTimeoutRef.current);
    }
    
    // Show suggestions after 3 characters
    if (value.length >= 3) {
      // Show loading immediately for better UX
      setIsSearchLoading(true);
      setShowSuggestions(true);
      
      // Check cache first
      if (searchCache.current.has(value)) {
        setSearchSuggestions(searchCache.current.get(value));
        setIsSearchLoading(false);
        return;
      }
      
      // Debounce API call with shorter delay for better responsiveness
      searchApiTimeoutRef.current = setTimeout(async () => {
        try {
          const response = await apiFetch({
            path: 'loginpress/v1/search-users',
            method: 'POST',
            data: { search: value },
          });
          const users = response.users || [];
          setSearchSuggestions(users);
          // Cache the result
          searchCache.current.set(value, users);
        } catch (error) {
          setSearchSuggestions([]);
        } finally {
          setIsSearchLoading(false);
        }
      }, 200); // Reduced to 200ms for faster response
    } else {
      setShowSuggestions(false);
      setSearchSuggestions([]);
      setIsSearchLoading(false);
    }
  }, []);

  // Handle user selection from suggestions
  const handleUserSelect = useCallback(async (userId) => {
    // Check if user already exists
    const userExists = users.some((u) => u.ID == userId);
    if (userExists) {
      const element = document.getElementById(
        `loginpress_autologin_user_id_${userId}`
      );
      if (element) {
        element.classList.add("loginpress_user_highlighted");
        setTimeout(() => {
          element.classList.remove("loginpress_user_highlighted");
        }, 3000);
      }
      setSearchTerm('');
      setShowSuggestions(false);
      return;
    }
    try {
      await addUserToAutoLogin(userId);
      setSearchTerm('');
      setShowSuggestions(false);
      fetchAutoLoginUsers();
    } catch (error) {
    }
  }, [users,fetchAutoLoginUsers]);

  // Add user to auto-login
  const addUserToAutoLogin = useCallback(async (userId) => {
    await apiFetch({
      path: 'loginpress/v1/add-autologin-user',
      method: 'POST',
      data: { user_id: userId },
    });
  }, []);

  // Generate random code for new links
  const generateRandomCode = useCallback(() => {
    const chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    let result = '';
    while (result.length < 30) {
      result += chars.charAt(Math.floor(Math.random() * chars.length));
    }
    return result;
  }, []);

  // Handle actions (delete, new link, etc.)
  const handleAction = useCallback(async (action, userId, extraData = {}) => {
    try {
      setIsLoading(true);
      const response = await apiFetch({
        path: `loginpress/v1/autologin-${action}`,
        method: 'POST',
        data: { user_id: userId, ...extraData },
      });

      // Refresh user data after successful action
      await fetchAutoLoginUsers();

      // Show new link message if action is new-link
      if (action === 'new-link') {
        setNewLinkMessage(userId);
        setTimeout(() => setNewLinkMessage(null), 3000);
      }

      // Show email sent message if action is email-user
      if (action === 'email-user') {
        setEmailSentMessage(userId);
        setTimeout(() => setEmailSentMessage(null), 3000);
      }
      
      // Update local state for enable/disable actions
      if (action === 'change-state') {
        const newState = extraData.state;
        setEnabledStates(prev => ({ ...prev, [userId]: newState === 'enable' }));
      }
    } catch (error) {
    } finally {
      setIsLoading(false);
    }
  }, [fetchAutoLoginUsers]);

  // Copy to clipboard
  const copyToClipboard = (text) => {
    if (navigator.clipboard) {
      navigator.clipboard.writeText(text).then(() => {
        // Show success feedback
      }).catch(err => {
      });
    } else {
      // Fallback for browsers that don't support clipboard API
      const textArea = document.createElement('textarea');
      textArea.value = text;
      document.body.appendChild(textArea);
      textArea.select();
      try {
        document.execCommand('copy');
      } catch (err) {
      }
      document.body.removeChild(textArea);
    }
  };

  // Open popup for duration, email, etc.
  const openPopup = useCallback(async (type, userId) => {
    setActivePopup(type);
    setActiveUserId(userId);
    
    try {
      const response = await apiFetch({
        path: `loginpress/v1/populate-${type}`,
        method: 'POST',
        data: { id: userId },
      });
      setPopupData(response || {});
    } catch (error) {
    }
  }, []);

  // Close popup
  const closePopup = useCallback(() => {
    setActivePopup(null);
    setActiveUserId(null);
    setPopupData({});
  }, []);

  // Handle popup form submission
  const handlePopupSubmit = useCallback(async (e) => {
    const data = e.target;
    try {
      setIsLoading(true);
      await apiFetch({
        path: `loginpress/v1/update-${activePopup}`,
        method: 'POST',
        data: { id: activeUserId, ...data },
      });
      
      fetchAutoLoginUsers();
	 let timepopup = 0; 
	  if (data["emails"]){
		timepopup =  1000;
	  }
      setTimeout( function(){
		closePopup();
	  }, timepopup);
    } catch (error) {
      setMessage({
        text: __('Failed to send email. Please try again.', 'loginpress-pro'),
        type: 'error'
      });
      setTimeout(() => setMessage(null), 3000);
    } finally {
      setIsLoading(false);
    }
  }, [activePopup, activeUserId, fetchAutoLoginUsers, closePopup]);

  // Memoized sorted and filtered users
  const sortedUsers = useMemo(() => {
    let sortableUsers = [...users];
  
    if (sortConfig.key) {
      sortableUsers.sort((a, b) => {
        const aVal = a[sortConfig.key]?.toString().toLowerCase();
        const bVal = b[sortConfig.key]?.toString().toLowerCase();
  
        if (sortConfig.key === 'ID') {
          return sortConfig.direction === 'asc' ? aVal - bVal : bVal - aVal;
        }
  
        if (aVal < bVal) return sortConfig.direction === 'asc' ? -1 : 1;
        if (aVal > bVal) return sortConfig.direction === 'asc' ? 1 : -1;
        return 0;
      });
    }
  
    if (debouncedFilterTerm.length > 0) {
      const lowerFilter = debouncedFilterTerm.toLowerCase();
      sortableUsers = sortableUsers.filter(
        (user) =>
          user.user_login.toLowerCase().includes(lowerFilter) ||
          user.user_email.toLowerCase().includes(lowerFilter) ||
          user.ID.toString().includes(lowerFilter)
      );
    }
  
    return sortableUsers;
  }, [users, sortConfig, debouncedFilterTerm]);
  
  // Memoized pagination
  const totalPages = Math.ceil(sortedUsers.length / rowsPerPage);
  const paginatedUsers = useMemo(() => {
    return sortedUsers.slice(
      (currentPage - 1) * rowsPerPage,
      currentPage * rowsPerPage
    );
  }, [sortedUsers, currentPage, rowsPerPage]);

  // Calculate entries display text (similar to LoginRedirects)
  const getEntriesDisplay = (currentPage, perPage, totalItems, filteredItems) => {
    const start = (currentPage - 1) * perPage + 1;
    const end = Math.min(currentPage * perPage, filteredItems.length);
    const total = filteredItems.length;

    if (total === 0) {
      if (filterTerm && filterTerm.trim() !== '' && totalItems > 0) {
        return sprintf(__(`Showing 0 to 0 of %d entries (filtered from %d total entries)`, 'loginpress-pro'), 0, totalItems);
      }
      return __('Showing 0 to 0 of 0 entries', 'loginpress-pro');
    }

    // Show filtered count when there's a filter term (regardless of whether totalItems > total)
    if (filterTerm && filterTerm.trim() !== '') {
      return sprintf(__(`Showing %1$d to %2$d of %3$d entries (filtered from %4$d total entries)`, 'loginpress-pro'), start, end, total, totalItems);
    }

    return sprintf(__(`Showing %1$d to %2$d of %3$d entries`, 'loginpress-pro'), start, end, total);
  };

  // Memoized handlers
  const handleFilterChange = useCallback((e) => {
    setFilterTerm(e.target.value);
  }, []);

  const handleRowsPerPageChange = useCallback((e) => {
    setRowsPerPage(Number(e.target.value));
  }, []);

  const handlePageChange = useCallback((newPage) => {
    setCurrentPage(newPage);
  }, []);

  // Render message component
  const renderMessage = () => {
    if (!message) return null;
    return (
      <div className={`message autologin-message ${message.type}`}>
        <p style={{ margin: 0 }}>{message.text}</p>
		<span className='close-me' onClick={() => (setMessage({text: '', type: ''}))}><i className="dashicons dashicons-no-alt"></i></span>
      </div>
    );
  };

  return (
    <>
    <InfoBox
      heading={__('Auto Login', 'loginpress-pro')}
      description={__('The Auto Login add-on for LoginPress allows administrators to generate unique URLs for specific users who do not need to enter a password to access the site.', 'loginpress-pro')}
      videoUrl="https://www.youtube.com/watch?v=M2M3G2TB9Dk"
    />
    
    {/* Message Display */}
    {renderMessage()}
    
    <div className="loginpress-autologin-container">
      {/* Search Form */}
      <div className="loginpress-autologin-search">
        <div className="loginpress-search-container" ref={wrapperRef}>
          <div className='loginpress-username' style={{position: 'relative'}}>
			<label>{__('Search Username', 'loginpress-pro')}</label>
      <div className="search-input-wrapper">
			<input
				type="search"
				value={searchTerm}
				onChange={handleSearchChange}
				onFocus={() => setIsMainSearchFocused(true)}
				onBlur={() => setIsMainSearchFocused(false)}
				placeholder={__('Type Username or Email', 'loginpress-pro')}
				className="loginpress-autologin-search-input"
			/>
			
			{/* Cross icon (clear button) for main search */}
			{/* {searchTerm && (
				<button
					type="button"
					className={`clear-search-btn ${!isMainSearchFocused ? 'show-on-hover' : ''}`}
					onClick={clearMainSearch}
					title={__("Clear search", "loginpress-pro")}
				>
					×
				</button>
			)} */}
      {showSuggestions && searchSuggestions.length > 0 && (
          <div className="search-suggestions">
            {searchSuggestions.map(user => (
              <li className="ui-menu-item"
                key={user.ID}
                onClick={() => handleUserSelect(user.ID)}
              >
                {searchTerm.includes('@') ? user.user_email : user.user_login}
              </li>
            ))}
          </div>
        )}
        {/* Search Suggestions */}
        
        {searchTerm.length !== 0 && searchTerm.length < 3 && (
          <div style={{color: '#FF0000', paddingTop: '10px', fontSize: '14px'}}>{__('Enter at least 3 characters', 'loginpress-pro')}</div>
        )}
        {showSuggestions && searchSuggestions.length === 0 && (
          <div id="noUsersMessage" style={{color: '#FF0000', paddingTop: '10px', fontSize: '14px'}}>{__('No users found', 'loginpress-pro')}</div>
        )}
        <p className='description'>{__(
          "Username/email for making a login magic link for a specific user.",
          "loginpress-pro"
        )}</p>
        </div>
		  </div>
          {isSearchLoading && (
            <span className="search-spinner"></span>
          )}
        </div>
        
      </div>

      {/* New Link Message */}
      

      {/* Controls */}
      <div className="loginpress-autologin-controls-bar">
  <div className="rows-per-page">
    <span>{__('Show Entries', 'loginpress-pro')}</span>
    <div className="select selectbox" ref={dropdownRef}>
      <select 
        value={rowsPerPage}
        onChange={handleRowsPerPageChange}
        className="loginpress-rows-select s-hidden"
      >
        <option value="10">10</option>
        <option value="25">25</option>
        <option value="50">50</option>
        <option value="100">100</option>
      </select>
      <div className="styledSelect" onClick={toggleDropdown}>
        <span className="text-ellipses valueAdded">{rowsPerPage}</span>
      </div>
      <ul className="options" style={{ display: isDropdownOpen ? 'block' : 'none' }}>
        {pageSizes.map((size) => (
          <li 
            key={size}
            rel={size}
            className={rowsPerPage === size ? 'active' : ''}
            onClick={() => handleOptionSelect(size)}
          >
            {size}
          </li>
        ))}
      </ul>
    </div>
  </div>

  <div id="loginpress_autologin_users_filter" className="dataTables_filter">
    <label>
      
      <div id="loginpress_autologin_users_filter">
        <input
          type="search"
          className=""
          placeholder={__('Enter keyword', 'loginpress-pro')}
          value={filterTerm}
          onChange={handleFilterChange}
          onFocus={() => setIsFilterSearchFocused(true)}
          onBlur={() => setIsFilterSearchFocused(false)}
          aria-controls="loginpress_autologin_users"
        />
        
        {/* Cross icon (clear button) for filter search */}
        {/* {filterTerm && (
          <button
            type="button"
            className={`clear-search-btn ${!isFilterSearchFocused ? 'show-on-hover' : ''}`}
            onClick={clearFilterSearch}
            title={__("Clear search", "loginpress-pro")}
          >
            ×
          </button>
        )} */}
      </div>
      <p className="description">
        {__('Search user\'s data from below the list', 'loginpress-pro')}
      </p>
    </label>
  </div>
</div>


      {/* Loading State */}
      {isLoading ? (
        <div className={`loginpress-table-wrapper ${activeMenuId ? 'menu-open' : ''}`}>
		<table className="loginpress_autologin_users dataTable" id="loginpress_autologin_users">
		   <thead>
			 <tr>
			   <th>{__('User ID', 'loginpress-pro')}</th>
			   <th>{__('Username', 'loginpress-pro')}</th>
			   <th>{__('Email', 'loginpress-pro')}</th>
			   <th>{__('Auto login URL', 'loginpress-pro')}</th>
			   <th>{__('Status', 'loginpress-pro')}</th>
			   <th>{__('Action', 'loginpress-pro')}</th>
			 </tr>
		   </thead>
		   <tbody>
			{[...Array(5)].map((_, i) => (
			  <tr key={i}>
				<td><div className='lp-tbody-cell'><div className="skeleton-paragraph"></div></div></td>
				<td><div className='lp-tbody-cell'><div className="skeleton-paragraph"></div></div></td>
				<td><div className='lp-tbody-cell'><div className="skeleton-paragraph"></div></div></td>
				<td><div className='lp-tbody-cell'><div className="skeleton-paragraph"></div></div></td>
				<td><div className='lp-tbody-cell'><div className="skeleton-paragraph"></div></div></td>
				<td><div className='lp-tbody-cell'><div className="skeleton-paragraph"></div></div></td>
			  </tr>
			))}
		  </tbody>
		</table>
	  </div>
      ) : !isLoading && (users.length === 0 || sortedUsers.length === 0 ) ? (
        <>
          <div className={`loginpress-table-wrapper ${activeMenuId ? 'menu-open' : ''}`}>
           <table className="loginpress_autologin_users dataTable" id="loginpress_autologin_users">
              <thead>
                <tr>
                  <th>{__('User ID', 'loginpress-pro')}</th>
                  <th>{__('Username', 'loginpress-pro')}</th>
                  <th>{__('Email', 'loginpress-pro')}</th>
                  <th>{__('Auto login URL', 'loginpress-pro')}</th>
                  <th>{__('Status', 'loginpress-pro')}</th>
                  <th>{__('Action', 'loginpress-pro')}</th>
                </tr>
              </thead>
              <tbody>
                  <tr>
                      <td colSpan={6} className='loginpress-empty-data'>
                      {users.length === 0
                  ? __('No Data Available In Table', 'loginpress-pro')
                  : __('No matching records found', 'loginpress-pro')}

                      </td>
                  </tr>
              </tbody>
              </table>
              </div>
			  <div className='loginpress-pagination-wrapper'>
              <div className="showing-of-entries">
                {getEntriesDisplay(1, rowsPerPage, users.length, sortedUsers)}
              </div>
			  </div>
        </>
      ) : (
        <>
          {/* Users Table */}
		  <div className={`loginpress-table-wrapper ${activeMenuId ? 'menu-open' : ''}`}>
          <table className="loginpress_autologin_users dataTable" id="loginpress_autologin_users">
            <thead>
              <tr>
                {(!isLoading && users.length > 0) ? (
                  <>
                    <th onClick={() => handleSort('ID')} className={
                      'sorting' +
                      (sortConfig.key && sortConfig.key === 'ID'
                        ? sortConfig.direction === 'asc'
                          ? ' sorting_asc'
                          : ' sorting_desc'
                        : '')
                    } style={{ cursor: 'pointer' }}>
                      {__('User ID', 'loginpress-pro')}
                    </th>
                    <th onClick={() => handleSort('user_login')} className={
                      'sorting' +
                      (sortConfig.key && sortConfig.key === 'user_login'
                        ? sortConfig.direction === 'asc'
                          ? ' sorting_asc'
                          : ' sorting_desc'
                        : '')
                    } style={{ cursor: 'pointer' }}>
                      {__('Username', 'loginpress-pro')}
                    </th>
                    <th onClick={() => handleSort('user_email')} className={
                      'sorting' +
                      (sortConfig.key && sortConfig.key === 'user_email'
                        ? sortConfig.direction === 'asc'
                          ? ' sorting_asc'
                          : ' sorting_desc'
                        : '')
                    } style={{ cursor: 'pointer' }}>
                      {__('Email', 'loginpress-pro')}
                    </th>
                  </>
                ) : (
                  <>
                    <th>{__('User ID', 'loginpress-pro')}</th>
                    <th>{__('Username', 'loginpress-pro')}</th>
                    <th>{__('Email', 'loginpress-pro')}</th>
                  </>
                )}
                <th>{__('Auto login URL', 'loginpress-pro')}</th>
                <th>{__('Status', 'loginpress-pro')}</th>
                <th>{__('Action', 'loginpress-pro')}</th>
              </tr>
            </thead>
            <tbody>
              {paginatedUsers.map((user) => (
                <UserRow 
                  key={user.ID} 
                  user={user} 
                  onAction={handleAction}
                  onCopy={copyToClipboard}
                  onOpenPopup={openPopup}
                  generateRandomCode={generateRandomCode}
                  isMenuOpen={activeMenuId === user.ID}
                  onMenuToggle={handleMenuToggle}
                  newLinkMessage={newLinkMessage === user.ID}
                  emailSentMessage={emailSentMessage === user.ID}
                  isEnabledNow={enabledStates[user.ID] || false}
                  setIsEnabledNow={(value) => setEnabledStates(prev => ({ ...prev, [user.ID]: value }))}
                />
              ))}
            </tbody>
          </table>
		  </div>
		  <div className="loginpress-pagination-wrapper">
              <div className="showing-of-entries">
            {getEntriesDisplay(currentPage, rowsPerPage, users.length, sortedUsers)}
          </div>
          {/* Pagination */}
          {totalPages > 1 && (
            <div className="loginpress-pagination">
              <button
              disabled={currentPage === 1}
                onClick={() => handlePageChange(currentPage - 1)}
              >
              </button>
              <span>
                {__('Page', 'loginpress-pro')} {currentPage} {__('of', 'loginpress-pro')} {totalPages}
              </span>
              <button
                disabled={currentPage === totalPages}
                onClick={() => handlePageChange(currentPage + 1)}
              >
              </button>
            </div>
			
          )}
			</div>
        </>
      )}

      {/* Popup Modals */}
      {activePopup === 'duration' && (
        <DurationPopup 
          data={popupData}
          onSubmit={handlePopupSubmit}
          onClose={closePopup}
        />
      )}

      {activePopup === 'email' && (
        <EmailPopup 
          data={popupData}
          onSubmit={handlePopupSubmit}
          onClose={closePopup}
        />
      )}

      {activePopup === 'link-count' && (
        <LinkCountPopup 
          data={popupData}
          onSubmit={handlePopupSubmit}
          onClose={closePopup}
        />
      )}
    </div>
    </>
  );
};

const UserRow = ({ user, onAction, onCopy, onOpenPopup, generateRandomCode, isMenuOpen, onMenuToggle, newLinkMessage, emailSentMessage, isEnabledNow, setIsEnabledNow }) => {
  const [isCopied, setIsCopied] = useState(false);
  const actionMenuRef = useRef(null);

  // Hide action menu on outside click
  useEffect(() => {
    if (!isMenuOpen) return;
    const handleClickOutside = (event) => {
      if (actionMenuRef.current && !actionMenuRef.current.contains(event.target)) {
        onMenuToggle(user.ID);
      }
    };
    document.addEventListener("mousedown", handleClickOutside);
    return () => document.removeEventListener("mousedown", handleClickOutside);
  }, [isMenuOpen, onMenuToggle, user.ID]);
  const {
    ID,
    user_login,
    user_email,
    meta: {
      code,
      state,
      expire,
      duration,
      link_count,
      link_click,
      unlimited_click,
      emails
    }
  } = user;

  const isEnabled = state === 'enable';
  const isUnlimited = unlimited_click === 'checked' || typeof unlimited_click === 'undefined';
  const neverExpire = expire === '1' || expire === 'checked';
  const isExpired = checkIfExpired(duration, expire);
  const isApproved = checkIfApproved(user);
  const remainingClicks = link_count - link_click;
  const remainingDays = calculateRemainingDays(duration);
  const toggleMenu = () => onMenuToggle(user.ID);

  const handleCopy = useCallback(() => {
    onCopy(`${loginpress_pro_local.homeUrl}/?loginpress_code=${code}`);
    setIsCopied(true);
    setTimeout(() => setIsCopied(false), 1500);
  }, [onCopy, code]);

  const handleDelete = useCallback(() => {
    onAction('delete', ID);
  }, [onAction, ID]);

  const handleNewLink = useCallback(() => {
    onAction('new-link', ID, { code: generateRandomCode() });
  }, [onAction, ID, generateRandomCode]);
  

  const handleDuration = useCallback(() => {
    onOpenPopup('duration', ID);
  }, [onOpenPopup, ID]);

  const handleLinkCount = useCallback(() => {
    onOpenPopup('link-count', ID);
  }, [onOpenPopup, ID]);

  const handleChangeState = useCallback(() => {
    onAction('change-state', ID, { state: isEnabled ? 'disable' : 'enable' });
  }, [onAction, ID, isEnabled]);

  const handleEmailSettings = useCallback(() => {
    onOpenPopup('email', ID);
  }, [onOpenPopup, ID]);

  const handleEmailUser = useCallback(() => {
    onAction('email-user', ID);
  }, [onAction, ID]);

  return (
    <tr id={`loginpress_autologin_user_id_${ID}`} className={`${!isEnabled ? 'autologin-disabled' : ''} ${isExpired ? 'autologin-expired' : ''} ${isMenuOpen ? 'menu-open' : ''}`}>
      <td><div className="lp-tbody-cell">{ID}</div></td>
      <td><div className="lp-tbody-cell">{user_login}</div></td>
      <td><div className="lp-tbody-cell">{user_email}</div></td>
      <td className="loginpress_autologin_code">
        <div className="lp-tbody-cell">
          <div className="autologin-url-container">
            <input
              type="text"
              value={`${loginpress_pro_local.homeUrl}/?loginpress_code=${code}`}
              readOnly
              className="autologin-url-input"
			  dir='rlt'
            />
            <button 
              onClick={handleCopy}
              className="button autologin-copy-button"
              data-title={
                isCopied ? __('Copied!', 'loginpress-pro') : __('Copy', 'loginpress-pro')
              }
            >
              {!isCopied ? (
  <svg className="autologin-copy-svg" width="22" height="22" viewBox="0 0 22 22" fill="none" xmlns="http://www.w3.org/2000/svg">
    <g clipPath="url(#clip0_27_294)">
      <path
        d="M1.62913 0H13.79C14.6567 0 15.3617 0.7051 15.3617 1.57176V5.26077H13.9269V1.57176C13.9269 1.49624 13.8655 1.43478 13.79 1.43478H1.62913C1.55361 1.43478 1.49216 1.49624 1.49216 1.57176V13.7326C1.49216 13.8082 1.55361 13.8696 1.62913 13.8696H5.20313V15.3044H1.62913C0.762474 15.3044 0.057373 14.5993 0.057373 13.7326V1.57176C0.0574209 0.7051 0.762474 0 1.62913 0Z"
        fill="#869AC1"
      />
      <path
        d="M8.20978 6.69557H20.3706C21.2373 6.69557 21.9424 7.40067 21.9424 8.26737V20.4282C21.9423 21.2949 21.2373 22 20.3706 22H8.20973C7.34303 22 6.63793 21.2949 6.63793 20.4283V8.26737C6.63788 7.40067 7.34308 6.69557 8.20978 6.69557ZM8.20969 20.5652H20.3706C20.4461 20.5652 20.5076 20.5038 20.5076 20.4283V8.26737C20.5076 8.19181 20.4461 8.13035 20.3706 8.13035H8.20973C8.13417 8.13035 8.07271 8.19181 8.07271 8.26737V20.4283C8.07271 20.5038 8.13417 20.5652 8.20969 20.5652Z"
        fill="#869AC1"
      />
    </g>
    <defs>
      <clipPath id="clip0_27_294">
        <rect width="22" height="22" fill="white" transform="matrix(-1 0 0 1 22 0)" />
      </clipPath>
    </defs>
  </svg>
) : (
  <svg width="18px" height="13.5px" viewBox="0 0 18 13.5" xmlns="http://www.w3.org/2000/svg">
    <path
      className="st0"
      fill="#1F693F"
      d="M15.6,0L7,8.6L2.4,4L0,6.5l7,7l11-11L15.6,0z"
    />
  </svg>
)}

            </button>
          </div>
		  {emailSentMessage && (
              <div className="loginpress-email-sent-message" style={{display: 'block'}}>
                <span className="loginpress-autologin-remain-notice" style={{display: 'block'}}>Email Sent</span>
              </div>
            )}
        </div>
      </td>
      <td>
        <div className="lp-tbody-cell">
            { newLinkMessage ? (
              <div className="loginpress-new-link-message">
                <span className="loginpress-autologin-remain-notice">New Link Created</span>
              </div>
            ) : (
              <>
                {getStatusText(remainingDays, isExpired, isApproved, isEnabled, neverExpire, isEnabledNow)}
                <br />
                {getClicksText(link_count, link_click, isUnlimited)}
              </>
            )}
          </div>
        </td>
      <td className='loginpress_autologin_actions'>
        <div className="lp-tbody-cell">
          <div className="autologin-actions">
            <button 
              onClick={handleDelete}
              className="button loginpress-del-link"
              aria-label={__('Delete', 'loginpress-pro')}
            >
            </button>
            <div className='loginpress-action-list-menu-wrapper'>
              <div className={`action-menu ${isMenuOpen ? 'open' : ''}`} ref={actionMenuRef}>
                <div 
                  onClick={toggleMenu}
                  className="loginpress-action-menu-burger-wrapper"
                >
                  {isMenuOpen ? (<span className="loginpress-action-menu-burger-close-icon dashicons dashicons-no-alt" style={{display:'inline-block'}}></span>) : <span className="loginpress-action-menu-burger-open-icon dashicons dashicons-menu-alt2"></span>}
                </div>
                {isMenuOpen && (
                  <ul className="action-menu-list" style={{display: 'inline-block', visibility: 'visible', opacity: 1}}>
                    <li>
                      <button
					  type="button"
                        onClick={() => {onAction('new-link', ID, { code: generateRandomCode() });onMenuToggle(user.ID);}}
                        className="button loginpress-new-link"
                        disabled={!isEnabled}
                      >
                        {__('New Link', 'loginpress-pro')}
                      </button>
                    </li>
                    <li>
                    <button
                      type="button"
                      onClick={() => {onOpenPopup('duration', ID);onMenuToggle(user.ID);}}
                      className="button loginpress-autologin-duration"
                      disabled={!isEnabled}
                    >
                      {isExpired && !neverExpire ? __('Extend Link', 'loginpress-pro') : __('Link Duration', 'loginpress-pro')}
                    </button>
                    </li>
                    <li>
                      <button
                        type="button"
                        onClick={() => {onOpenPopup('link-count', ID);onMenuToggle(user.ID);}}
                        className="button loginpress-autologin-link-count"
                        disabled={!isEnabled || (isExpired && !neverExpire)}
                      >
                        { 0 === (link_count - link_click) ? __('Extend Count', 'loginpress-pro') : __('Link Count', 'loginpress-pro')}
                      </button>
                    </li>
                    <li>
                      <button
                        type="button"
                        onClick={() => {onAction('change-state', ID, { state: isEnabled ? 'disable' : 'enable' });onMenuToggle(user.ID);}}
                        className={`button loginpress-autologin-state  ${isEnabled ? 'disable' : 'enable'}`}
                        disabled={!isApproved}
                      >
                        {isEnabled ? __('Disable', 'loginpress-pro') : __('Enable', 'loginpress-pro')}
                      </button>
                    </li>
                    <li>
                      <button
					  type="button"
                        onClick={() => {onOpenPopup('email', ID);onMenuToggle(user.ID);}}
                        className="button loginpress-autologin-email-settings"
                        disabled={!isEnabled || (isExpired && !neverExpire)}
                      >
                        {__('Email To Multiple', 'loginpress-pro')}
                      </button>
                    </li>
                    <li>
                      <button
					  type="button"
                        onClick={() => {onAction('email-user', ID);onMenuToggle(user.ID);}}
                        className="button loginpress-autologin-email"
                        disabled={!isEnabled || (isExpired && !neverExpire)}
                      >
                        {__('Email User', 'loginpress-pro')}
                      </button>
                    </li>
                  </ul>
                )}
              </div>
            </div>
          </div>
        </div>
      </td>
    </tr>
  );
};

// Memoized Popup Components
const DurationPopup = React.memo(({ data, onSubmit, onClose }) => {
  const [neverExpire, setNeverExpire] = useState(data.never_expire === 'checked');
  const [expireDate, setExpireDate] = useState(data.expire_duration || '');
  const [error, setError] = useState('');
  const minDate = new Date().toISOString().split('T')[0];
  const maxDate = '2050-01-01';

  useEffect(() => {
    setNeverExpire(data.never_expire === 'checked');
    setExpireDate(data.expire_duration || '');
    setError('');
  }, [data]);

  const handleSubmit = useCallback((e) => {
    e.preventDefault();
    
    // Validation
    if (!neverExpire && !expireDate) {
      setError('Please select an expiration date or check "Never Expire"');
      return;
    }
    
    if (!neverExpire && expireDate) {
      const selectedDate = new Date(expireDate);
      const today = new Date();
      today.setHours(0, 0, 0, 0);
      
      if (selectedDate <= today) {
        setError('Expiration date must be in the future');
        return;
      }
    }
    
    setError('');
    onSubmit({
      target: {
        never_expire: neverExpire,
        expire_duration: expireDate
      }
    });
  }, [onSubmit, neverExpire, expireDate]);

  const handleDateChange = useCallback((e) => {
    setExpireDate(e.target.value);
    setError('');
  }, []);

  const handleNeverExpireChange = useCallback((e) => {
    setNeverExpire(e.target.checked);
    setError('');
  }, []);

  return (
    <div className="loginpress-edit-popup-container-2" style={{ display: 'flex' }} data-for="1">
      <div className="loginpress-edit-popup loginpress-link-duration-popup">
        <form onSubmit={handleSubmit}>
			{!neverExpire &&(
          <div className="autologin-popup-fields autologin-expire-date-container" style={{ display: 'block' }}>
            <label htmlFor="autologin-expire-date">{__('Expiration Date', 'loginpress-pro')}</label>
            <p className="autologin-expire-date_desc">
              {__('This Auto Login link will expire on', 'loginpress-pro')} {expireDate}
            </p>
            <input
              type="date"
              id="autologin-expire-date"
              className="autologin-expire-date"
              min={minDate}
              max={maxDate}
              value={expireDate}
              onChange={handleDateChange}
              disabled={neverExpire}
              required={!neverExpire}
            />
            {error && <p className="loginpress-error-message" style={{color: '#dc3232', fontSize: '14px', marginTop: '5px'}}>{error}</p>}
          </div>
			)}
          <div className="autologin-popup-fields">
            <label htmlFor="autologin-never-expire">{__('Never Expire', 'loginpress-pro')}</label>
            <input
              id="autologin-never-expire"
              className="autologin-never-expire"
              type="checkbox"
              value="void"
              checked={neverExpire}
              onChange={handleNeverExpireChange}
            />
          </div>
          <div className="loginpress-auto-login-duration-buttons">
            <button
              type="button"
              className="button button-primary autologin-close-popup"
              onClick={onClose}
            >
              {__('Close', 'loginpress-pro')}
            </button>
            <button
              type="submit"
              className="button button-primary autologin-save-duration"
            >
              {__('Done', 'loginpress-pro')}
            </button>
          </div>
        </form>
      </div>
    </div>
  );
});

const EmailPopup = React.memo(({ data, onSubmit, onClose }) => {
  const [emailList, setEmailList] = useState(data.emails || '');
  const [error, setError] = useState('');
  const [sent, setSent] = useState(false);

  // Reset sent state when popup data changes (new user opens popup)
  useEffect(() => {
    setSent(false);
    setError('');
  }, [data]);

  const handleSubmit = useCallback((e) => {
    e.preventDefault();
    
    // Trim and clean email list
    const emails = emailList.trim();
    
    if (!emails) {
      setError('Please enter at least one email address');
      return;
    }
    
    // Split emails and validate each one
    const emailArray = emails.split(',').map(email => email.trim()).filter(Boolean);
    
    if (emailArray.length === 0) {
      setError('Please enter at least one valid email address');
      return;
    }
    
    if (emailArray.length > 10) {
      setError('Maximum 10 email addresses allowed');
      return;
    }
    
    // Validate each email format
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    const invalidEmails = emailArray.filter(email => !emailRegex.test(email));
    
    if (invalidEmails.length > 0) {
      setError(`Invalid email format: ${invalidEmails.slice(0, 3).join(', ')}${invalidEmails.length > 3 ? '...' : ''}`);
      return;
    }
    
    // Check for duplicate emails
    const uniqueEmails = [...new Set(emailArray)];
    if (uniqueEmails.length !== emailArray.length) {
      setError('Duplicate email addresses are not allowed');
      return;
    }
    
    setError('');
    setSent(true); // Set sent state to true to show success message
    setError('An Email Has Been Sent');
    // Submit the form data
    onSubmit({
      target: {
        emails: { value: emailArray.join(', ') }
      }
    });
    
    // Keep the success message visible for a few seconds before closing
    setTimeout(() => {
      setSent(false);
    }, 3000);
  }, [emailList, onSubmit]);

  const handleEmailChange = useCallback((e) => {
    const value = e.target.value;
    setEmailList(value);
    setError('');
  }, []);

  return (
    <div className="loginpress-edit-popup-container-2" style={{ display: 'flex' }} data-for="1">
      <div className="loginpress-edit-popup loginpress-multiple-users-popup">
        <form onSubmit={handleSubmit}>
          <div className="autologin-popup-fields">
            <label htmlFor="autologin-emails">{__('Email Addresses', 'loginpress-pro')}</label>
            <p className="autologin-multi-emails-desc">{__('Send Auto Login To Multiple Users', 'loginpress-pro')}</p>
            <input type='text'
              id="autologin-emails"
              value={emailList}
              placeholder={__('Email Address with Commas', 'loginpress-pro')}
              onChange={handleEmailChange}
            />
            <div data-lastpass-icon-root="" style={{ position: 'relative', height: 0, width: 0, float: 'left' }}></div>
            <p className="autologin-multi-emails-desc">{__('Use comma ( , ) to add more than 1 recipients. Maximum 10 emails.', 'loginpress-pro')}</p>
            {sent && <p className="autologin_emails_sent" style={{color: '#46b450', fontWeight: 'bold'}}>{__('An Email Has Been Sent', 'loginpress-pro')}</p>}
            {error && (<p className="loginpress-error-message" aria-label={error} style={{color: '#dc3232', fontSize: '14px', marginTop: '5px'}}>{error}</p>)}
          </div>
          <div className="loginpress-auto-login-duration-buttons">
            <button
              type="button"
              className="button button-primary autologin-close-popup"
              onClick={onClose}
            >
              {__('Close', 'loginpress-pro')}
            </button>
            <button
              type="submit"
              className="button button-primary autologin-save-emails"
            >
              {__('Done', 'loginpress-pro')}
            </button>
          </div>
        </form>
      </div>
    </div>
  );
});

const LinkCountPopup = React.memo(({ data, onSubmit, onClose }) => {
  const [unlimited, setUnlimited] = useState(data.unlimited === 'checked');
  const [linkCount, setLinkCount] = useState(data.link_count || '');
  const [error, setError] = useState('');
  
  useEffect(() => {
    setUnlimited(data.unlimited === 'checked');
    setLinkCount(data.link_count || '');
    setError('');
  }, [data]);
  
  const handleSubmit = useCallback((e) => {
    e.preventDefault();
    
    if (!unlimited) {
      if (!linkCount || linkCount.trim() === '') {
        setError('Please enter a link count number');
        return;
      }
      
      const count = parseInt(linkCount);
      if (isNaN(count) || count <= 0) {
        setError('Link count must be a positive number greater than 0');
        return;
      }
      
      if (count > 1000) {
        setError('Link count cannot exceed 1000');
        return;
      }
    }
    
    setError('');
    onSubmit({
      target: {
        unlimited: { checked: unlimited },
        link_count: { value: unlimited ? '' : linkCount }
      }
    });
  }, [unlimited, linkCount, onSubmit]);

  const handleLinkCountChange = useCallback((e) => {
    const value = e.target.value.replace(/\D/g, '');
    setLinkCount(value);
    setError('');
  }, []);

  const handleUnlimitedChange = useCallback((e) => {
    setUnlimited(e.target.checked);
    if (e.target.checked) {
      setLinkCount('');
    }
    setError('');
  }, []);

  return (
    <div className="loginpress-edit-popup-container-2" style={{ display: 'flex' }} data-for="1">
      <div className="loginpress-edit-popup loginpress-link-count-popup">
        <form onSubmit={handleSubmit}>
          <div className="autologin-popup-fields loginpress-link-count-container" style={{ display: unlimited ? 'none' : 'block' }}>
            <label htmlFor="autologin-linkcount">{__('Link Count', 'loginpress-pro')}</label>
            <p className="autologin-link-count-desc">{__('This is the number of times the link can be used.', 'loginpress-pro')}</p>
            <input
              type="number"
              id="autologin-linkcount"
              value={linkCount}
              placeholder={__('Enter Link Count', 'loginpress-pro')}
              onChange={handleLinkCountChange}
              min="1"
              max="1000"
              style={{ width: '100%' }}
            />
            <p className="autologin-link-count-desc">{__('Enter a number between 1 and 1000.', 'loginpress-pro')}</p>
            {error && <p className="loginpress-error-message" style={{color: '#dc3232', fontSize: '14px', marginTop: '5px'}}>{error}</p>}
          </div>
          <div className="autologin-popup-fields">
            <label htmlFor="autologin-unlimited-click">{__('Unlimited Clicks', 'loginpress-pro')}</label>
            <input
              id="autologin-unlimited-click"
              className="autologin-unlimited-click"
              type="checkbox"
              value="void"
              checked={unlimited}
              onChange={handleUnlimitedChange}
            />
          </div>
          <div className="loginpress-auto-login-duration-buttons">
            <button
              type="button"
              className="button button-primary autologin-close-popup"
              onClick={onClose}
            >
              {__('Close', 'loginpress-pro')}
            </button>
            <button
              type="submit"
              className="button button-primary autologin-save-link-count"
            >
              {__('Done', 'loginpress-pro')}
            </button>
          </div>
        </form>
      </div>
    </div>
  );
});

// Helper functions
function checkIfExpired(duration, expire) {
  if (expire === 'checked') return false;
  const now = new Date();
  let expirationDate = new Date(duration);
  expirationDate.setHours(23, 59, 59, 999);
  return now > expirationDate;
}

function checkIfApproved(user) {
  // Implementation depends on your user verification system
  return true;
}

function calculateRemainingDays(duration) {
  const now = new Date();
  const expirationDate = new Date(duration);
  const diffTime = expirationDate - now;
  return Math.ceil(diffTime / (1000 * 60 * 60 * 24));
}

function getStatusText(remainingDays, isExpired, isApproved, isEnabled, neverExpire, isEnabledNow) {
  if (!isApproved) {
    return <span className="status-not-approved">{__('User Not Approved', 'loginpress-pro')}</span>;
  }
  
  if (isExpired && !neverExpire) {
    return <span className="status-disabled">{__('Link Expired', 'loginpress-pro')}</span>;
  }
  
  if (!isEnabled) {
    return <span className="status-disabled">{__('Disabled', 'loginpress-pro')}</span>;
  }
  
  if (neverExpire){
	if(isEnabledNow){
		return <span className="loginpress-autologin-remain-notice">{__('Enabled', 'loginpress-pro')}</span>;
	}else{
    	return <span className="loginpress-autologin-remain-notice lg-warning">{__('Lifetime', 'loginpress-pro')}</span>
	}
  }
  if (remainingDays === 0) {
    return <span className="status-disabled">{__('Last Day', 'loginpress-pro')}</span>;
  }
  
  if (remainingDays > 0) {
	if(isEnabledNow){
		return <span className="loginpress-autologin-remain-notice">{__('Enabled', 'loginpress-pro')}</span>;
	}else{
	return <span className={`loginpress-autologin-remain-notice ${remainingDays === 1 ? 'loginpress-autologin-remain-notice-last-day' : ''}`}>
      {remainingDays === 1 
        ? __('Last Day Left', 'loginpress-pro') 
        : sprintf(__('%d Days Left', 'loginpress-pro'), remainingDays)}
    </span>;
	}
  }
  
  return <span className="loginpress-autologin-remain-notice lg-warning">{__('Lifetime', 'loginpress-pro')}</span>;
}

function getClicksText(total, used, isUnlimited) {
  if (isUnlimited) {
    return <span className="clicks-unlimited message">{__('Unlimited Clicks', 'loginpress-pro')}</span>;
  }
  
  if (total === 0) {
    return null;
  }
  
  const remaining = total - used;
  const className = remaining === 0 ? 'clicks-used-red' : 'clicks-used';
  return <span className={className}>
    {sprintf(__('%d/%d Clicks Used', 'loginpress-pro'), used?used:0, total?total:0)}
  </span>;
}

export default AutoLogin;