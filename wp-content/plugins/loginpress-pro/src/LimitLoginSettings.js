import React, { useState, useEffect, useMemo } from 'react';
import { __, sprintf } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';
import { TextControl, CheckboxControl, SelectControl } from '@wordpress/components';
import SettingField from './SettingField';
import LoginAttemptsTable from './LogsTable';
import ListTable from './ListTable';
import './LimitLogingSettings.css';
import InfoBox from './InfoBox';

const LimitLoginSettings = ({ userRoles }) => {
  const activeAddons = window.loginpressProData?.activeAddons || [];

  if (!activeAddons.includes('limit-login-attempts')) {
    return;
  }
  const [settings, setSettings] = useState({
    attempts_allowed: 4,
    minutes_lockout: 20,
    disable_xml_rpc_request: false,
    blacklist_message: '',
    attempts_left_message: '',
    lockout_message: '',
    enable_lockout_notification: 'off',
    notification_email:'',
    notification_subject:'',
    notification_body:'',
    limit_concurrent_sessions: 'off',
    exclude_roles: '',
    max_session_count:''
  });
  const [activeTab, setActiveTab] = useState('settings');
  const [message, setMessage] = useState({ type: '', text: '' });
  const [isLoading, setIsLoading] = useState(false);
  const [logs, setLogs] = useState([]);
  const [whitelist, setWhitelist] = useState([]);
  const [blacklist, setBlacklist] = useState([]);
  const [selectedLogs, setSelectedLogs] = useState([]);
  const [ipAddress, setIpAddress] = useState('');
  const [showClearAllPopup, setShowClearAllPopup] = useState(false);
  const [showClearAllWhitelistPopup, setShowClearAllWhitelistPopup] = useState(false);
  const [showClearAllBlacklistPopup, setShowClearAllBlacklistPopup] = useState(false);
  const [perPage, setPerPage] = useState(10);
  const [searchTerm, setSearchTerm] = useState('');
  const [currentPage, setCurrentPage] = useState(1);
  const [currentWhitelistPage, setCurrentWhitelistPage] = useState(1);
  const [currentBlacklistPage, setCurrentBlacklistPage] = useState(1);
  const [userMessage, setUserMessage] = useState({ text: '', type: 'success' });

  const [sortBy, setSortBy] = useState('datentime'); // default sorting
  const [sortOrder, setSortOrder] = useState('desc'); // or 'asc'
  const [whitelistSortBy, setWhitelistSortBy] = useState('ip');
  const [whitelistSortOrder, setWhitelistSortOrder] = useState('asc');

  const [blacklistSortBy, setBlacklistSortBy] = useState('ip');
  const [blacklistSortOrder, setBlacklistSortOrder] = useState('asc');
  const [statusFilter, setStatusFilter] = useState('all');
	const [errors, setErrors] = useState({});
  const sortedFilteredLogs = useMemo(() => {
      // First filter by search term
      const searchFiltered = !searchTerm
        ? logs
        : logs.filter(item => {
            const term = searchTerm.toLowerCase();
            return (
              item.ip.toLowerCase().includes(term) ||
              (item.username && item.username.toLowerCase().includes(term))
            );
          });
      
      // Then filter by status
      const statusFiltered = statusFilter === 'all' 
        ? searchFiltered 
        : searchFiltered.filter(item => {
            if (!item.login_status) return false;
            if (statusFilter === 'failed') 
              return item.login_status.toLowerCase() === 'failed';
            if (statusFilter === 'blocked') 
              return item.login_status.toLowerCase() === 'locked';
            if (statusFilter === 'success') 
              return item.login_status.toLowerCase() === 'success';
            return true;
          });
    
      // Finally sort
      return [...statusFiltered].sort((a, b) => {
        const valA = a[sortBy]?.toString().toLowerCase();
        const valB = b[sortBy]?.toString().toLowerCase();
        if (valA < valB) return sortOrder === 'asc' ? -1 : 1;
        if (valA > valB) return sortOrder === 'asc' ? 1 : -1;
        return 0;
      });
  }, [logs, searchTerm, sortBy, sortOrder, statusFilter]);


  const handleSort = (column) => {
    if (sortBy === column) {
      setSortOrder(sortOrder === 'asc' ? 'desc' : 'asc');
    } else {
      setSortBy(column);
      setSortOrder('asc');
    }
  };

  const handleWhitelistSort = (column) => {
    if (whitelistSortBy === column) {
      setWhitelistSortOrder(whitelistSortOrder === 'asc' ? 'desc' : 'asc');
    } else {
      setWhitelistSortBy(column);
      setWhitelistSortOrder('asc');
    }
  };

  const handleBlacklistSort = (column) => {
    if (blacklistSortBy === column) {
      setBlacklistSortOrder(blacklistSortOrder === 'asc' ? 'desc' : 'asc');
    } else {
      setBlacklistSortBy(column);
      setBlacklistSortOrder('asc');
    }
  };
  

  // const sortedFilteredLogs = useMemo(() => {
  //   const filtered = !searchTerm
  //     ? logs
  //     : logs.filter(item => {
  //         const term = searchTerm.toLowerCase();
  //         return (
  //           item.ip.toLowerCase().includes(term) ||
  //           (item.username && item.username.toLowerCase().includes(term))
  //         );
  //       });
  
  //   return [...filtered].sort((a, b) => {
  //     const valA = a[sortBy]?.toString().toLowerCase();
  //     const valB = b[sortBy]?.toString().toLowerCase();
  //     if (valA < valB) return sortOrder === 'asc' ? -1 : 1;
  //     if (valA > valB) return sortOrder === 'asc' ? 1 : -1;
  //     return 0;
  //   });
  // }, [logs, searchTerm, sortBy, sortOrder]);

  const sortedFilteredWhitelist = useMemo(() => {
    const filtered = !searchTerm
      ? whitelist
      : whitelist.filter(item => item.ip.toLowerCase().includes(searchTerm.toLowerCase()));
  
    return [...filtered].sort((a, b) => {
      const ipA = a.ip.toLowerCase();
      const ipB = b.ip.toLowerCase();
      if (ipA < ipB) return whitelistSortOrder === 'asc' ? -1 : 1;
      if (ipA > ipB) return whitelistSortOrder === 'asc' ? 1 : -1;
      return 0;
    });
  }, [whitelist, searchTerm, whitelistSortBy, whitelistSortOrder]);

  const sortedFilteredBlacklist = useMemo(() => {
    const filtered = !searchTerm
      ? blacklist
      : blacklist.filter(item => item.ip.toLowerCase().includes(searchTerm.toLowerCase()));
  
    return [...filtered].sort((a, b) => {
      const ipA = a.ip.toLowerCase();
      const ipB = b.ip.toLowerCase();
      if (ipA < ipB) return blacklistSortOrder === 'asc' ? -1 : 1;
      if (ipA > ipB) return blacklistSortOrder === 'asc' ? 1 : -1;
      return 0;
    });
  }, [blacklist, searchTerm, blacklistSortBy, blacklistSortOrder]);
  
  // Calculate paginated data for all tables
  // Filter data based on search term (client-side)
  // const filteredLogs = useMemo(() => {
  //   if (!searchTerm) return logs;
  //   const term = searchTerm.toLowerCase();
  //   return logs.filter(item => 
  //     item.ip.toLowerCase().includes(term) || 
  //     (item.username && item.username.toLowerCase().includes(term))
  //   );
  // }, [logs, searchTerm]);

  const filteredWhitelist = useMemo(() => {
    if (!searchTerm) return whitelist;
    const term = searchTerm.toLowerCase();
    return whitelist.filter(item => 
      item.ip.toLowerCase().includes(term)
    );
  }, [whitelist, searchTerm]);

  const filteredBlacklist = useMemo(() => {
    if (!searchTerm) return blacklist;
    const term = searchTerm.toLowerCase();
    return blacklist.filter(item => 
      item.ip.toLowerCase().includes(term)
    );
  }, [blacklist, searchTerm]);

  // Calculate paginated data using the filtered results
  const paginatedLogs = useMemo(() => {
    const start = (currentPage - 1) * perPage;
    const end = start + perPage;
    return sortedFilteredLogs.slice(start, end);
  }, [sortedFilteredLogs, currentPage, perPage]);

  const paginatedWhitelist = useMemo(() => {
    const start = (currentWhitelistPage - 1) * perPage;
    const end = start + perPage;
    return sortedFilteredWhitelist.slice(start, end);
  }, [sortedFilteredWhitelist, currentWhitelistPage, perPage]);
  
  const paginatedBlacklist = useMemo(() => {
    const start = (currentBlacklistPage - 1) * perPage;
    const end = start + perPage;
    return sortedFilteredBlacklist.slice(start, end);
  }, [sortedFilteredBlacklist, currentBlacklistPage, perPage]);
  

  // Calculate total pages based on filtered results
  const totalLogPages = useMemo(() => Math.ceil(sortedFilteredLogs.length / perPage), [sortedFilteredLogs, perPage]);
  const totalWhitelistPages = useMemo(() => Math.ceil(sortedFilteredWhitelist.length / perPage), [sortedFilteredWhitelist, perPage]);
  const totalBlacklistPages = useMemo(() => Math.ceil(sortedFilteredBlacklist.length / perPage), [sortedFilteredBlacklist, perPage]);
  // Load settings on component mount
  useEffect(() => {
    loadSettings();
  }, []);

  // useEffect(() => {
  //   if (activeTab === 'logs') {
  //     fetchLogs();
  //   }
  // }, [searchTerm]);

  // useEffect(() => {
  //   if (activeTab === 'whitelist') {
  //     fetchWhitelist();
  //   }
  // }, [searchTerm]);

  // useEffect(() => {
  //   if (activeTab === 'blacklist') {
  //     fetchBlacklist();
  //   }
  // }, [searchTerm]);

  useEffect(() => {
    setCurrentPage(1);
  }, [statusFilter]);

  // Create reusable pagination component
  const PaginationControls = ({ currentPage, totalPages, onPageChange }) => (
    totalPages > 1 && (
      <div className="loginpress-pagination">
        <button
        disabled={currentPage === 1}
          onClick={() => onPageChange(currentPage - 1)}
        >
          
        </button>
        
        <span>
          {__('Page', 'loginpress-pro')} {currentPage} {__('of', 'loginpress-pro')} {totalPages}
        </span>
        
        <button 
          disabled={currentPage === totalPages}
          onClick={() => onPageChange(currentPage + 1)}
        >
          
        </button>
      </div>
    )
  );

  const loadSettings = async () => {
    setIsLoading(true);
    try {
      const response = await apiFetch({
        path: '/loginpress/v1/limit-login-settings',
      });
      setSettings(response);
    } catch (error) {
    }
    setIsLoading(false);
  };

  const validateNumberFields = (settings) => {
    const newErrors = {};
    
    // Check if attempts_allowed or minutes_lockout is empty
    if (!settings.attempts_allowed || settings.attempts_allowed === '' || !settings.minutes_lockout || settings.minutes_lockout === '') {
      newErrors.empty_fields = __('Attempts Allowed and Lockout Minutes cannot be empty.', 'loginpress-pro');
    }
    
    // Add validation checks for each numeric field
    if (settings.max_session_count < 0 || settings.attempts_allowed < 0 || settings.minutes_lockout < 0) {
      newErrors.max_session_count = __('Numbers input cannot be negative.', 'loginpress-pro');
    }
  
    return newErrors;
  };

  const saveSettings = async () => {
    const validationErrors = validateNumberFields(settings);
    if (Object.keys(validationErrors).length > 0) {
      const errorMessage = Object.values(validationErrors)[0];
      setMessage({ type: 'error', text: errorMessage });
      return;
    }

    setIsLoading(true);
    try {
      const response = await apiFetch({
        path: '/loginpress/v1/limit-login-settings',
        method: 'POST',
        data: settings,
      });

      if (response.success) {
        setMessage({ type: 'success', text: __('Settings saved successfully!', 'loginpress-pro') });
      } else {
        setMessage({ type: 'error', text: response.message || __('Failed to save settings', 'loginpress-pro') });
      }
      // Show success message
    } catch (error) {
      // Show error message
    }
    setIsLoading(false);
	setTimeout(function(){
	  setMessage({text: '', type: ''})
	}, 2000)
  };

  const handleSettingChange = (name, value) => {
    setSettings(prev => ({ ...prev, [name]: value }));
  };

  const handleTabChange = async (tab) => {
    setActiveTab(tab);
    // Clear search term when switching tabs
    setSearchTerm('');
    if (tab === 'logs' && logs.length === 0) {
      await fetchLogs();
    } else if (tab === 'whitelist' && whitelist.length === 0) {
      await fetchWhitelist();
    } else if (tab === 'blacklist' && blacklist.length === 0) {
      await fetchBlacklist();
    }
  };

  const fetchLogs = async () => {
    setIsLoading(true);
    try {
      const response = await apiFetch({
        path: '/loginpress/v1/login-attempts-logs',
        method: 'GET',
        // data: { search: searchTerm }
      });
      setLogs(response);
      setCurrentPage(1); // Reset to first page when data changes
    } catch (error) {
    }
    setIsLoading(false);
  };

  const fetchWhitelist = async () => {
    setIsLoading(true);
    try {
      const response = await apiFetch({
        path: '/loginpress/v1/whitelist-list',
        method: 'GET',
        // data: { search: searchTerm }
      });
      setWhitelist(response);
      setCurrentWhitelistPage(1);
    } catch (error) {
    }
    setIsLoading(false);
  };

  const fetchBlacklist = async () => {
    setIsLoading(true);
    try {
      const response = await apiFetch({
        path: '/loginpress/v1/blacklist-list',
        method: 'GET',
        // data: { search: searchTerm }
      });
      setBlacklist(response);
      setCurrentBlacklistPage(1);
    } catch (error) {
    }
    setIsLoading(false);
  };

  const handleAddToList = async (listType) => {
	if (!ipAddress) return;
  
	const ipRegex = /^(25[0-5]|2[0-4]\d|1\d{2}|[1-9]?\d)(\.(25[0-5]|2[0-4]\d|1\d{2}|[1-9]?\d)){3}$/;
  
	if (!ipRegex.test(ipAddress)) {
	  setUserMessage({ text: __('Your  IP Format is not correct', 'loginpress-pro'), type: 'error' });
	  setTimeout(() => setUserMessage({ text: '', type: 'success' }), 2000); // Hide message after 2s
	  return;
	}
  
	setIsLoading(true);
	setUserMessage('');
	try {
	  const response = await apiFetch({
		path: '/loginpress/v1/limit-login-add-ip',
		method: 'POST',
		data: {
		  ip: ipAddress,
		  list_type: listType,
		},
	  });
  
	  if (response.message) {
		setUserMessage({ text: response.message, type: 'success' });
		setTimeout(() => setUserMessage({ text: '', type: 'success' }), 2000); // Hide message after 2s
	  }
  
	  setIpAddress('');
	  await fetchWhitelist();
	  await fetchBlacklist();
	} catch (error) {
	  const errorMessage = error?.message || __('Error adding IP address.', 'loginpress-pro');
	  setUserMessage({ text: errorMessage, type: 'error' });
	  setTimeout(() => setUserMessage({ text: '', type: 'success' }), 2000); // Hide message after 2s
	}
	setIsLoading(false);
  };
  
  

  const handleBulkAction = async (action) => {
    if (selectedLogs.length === 0) return;
    
    setIsLoading(true);
    try {
      await apiFetch({
        path: '/loginpress/v1/login-attempts/bulk-action',
        method: 'POST',
        data: {
          ids: selectedLogs,
          action: action,
        },
      });
      setSelectedLogs([]);
      await fetchLogs();
      if (action === 'whitelist') await fetchWhitelist();
      if (action === 'blacklist') await fetchBlacklist();
    } catch (error) {
    }
    setIsLoading(false);
  };

  const handleClearAll = async (listType) => {
    setIsLoading(true);
    try {
      await apiFetch({
        path: `/loginpress/v1/${listType === 'logs' ? 'login-attempts' : listType + '-list'}/clear-all`,
        method: 'POST'
      });
      if (listType === 'logs') {
        await fetchLogs();
      } else if (listType === 'whitelist') {
        await fetchWhitelist();
      } else if (listType === 'blacklist') {
        await fetchBlacklist();
      }
    } catch (error) {
    }
    setIsLoading(false);
  };

  const handleRemoveFromList = async (listType, ip) => {
    setIsLoading(true);
    try {
      await apiFetch({
        path: `/loginpress/v1/${listType}-list/remove`,
        method: 'POST',
        data: { ip }
      });
      if (listType === 'whitelist') {
        await fetchWhitelist();
      } else {
        await fetchBlacklist();
      }
    } catch (error) {
    }
    setIsLoading(false);
  };

  const handleAction = async (ip, action) => {
    setIsLoading(true);
    try {
      await apiFetch({
        path: '/loginpress/v1/login-attempts/action',
        method: 'POST',
        data: { ip, action }
      });
      await fetchLogs();
      if (action === 'whitelist') await fetchWhitelist();
      if (action === 'blacklist') await fetchBlacklist();
    } catch (error) {
    }
    setIsLoading(false);
  };

  const handleSelectRow = (id, checked) => {
    setSelectedLogs(prev => 
      checked ? [...prev, id] : prev.filter(rowId => rowId !== id)
    );
  };

  const handleSelectAll = (shouldSelectAll) => {
    if (shouldSelectAll) {
      // Select all IDs on current page
      setSelectedLogs(paginatedLogs.map(item => item.id));
    } else {
      // Clear selection
      setSelectedLogs([]);
    }
  };

  const userRolesOptions = Object.entries(userRoles).map(([key, role]) => ({
    value: role.name,
    label: role.name
  }));

  // Update the onClearAll handlers in your table components:

  const showClearAllPopupHandler = () => {
    if (logs.length === 0) {
      setMessage({
        type: 'error',
        text: __('The logs table is already empty.', 'loginpress-pro')
      });
      setTimeout(function(){
        setMessage({text: "", type: ""})
      }, 2000);
      return;
    }
    setShowClearAllPopup(true);
  };

  const showClearAllWhitelistPopupHandler = () => {
    if (whitelist.length === 0) {
      setMessage({
        type: 'error',
        text: __('The whitelist table is already empty.', 'loginpress-pro')
      });
      setTimeout(function(){
        setMessage({text: "", type: ""})
      }, 2000);
      return;
    }
    setShowClearAllWhitelistPopup(true);
  };

  const showClearAllBlacklistPopupHandler = () => {
    if (blacklist.length === 0) {
      setTimeout(() => setMessage({ text: '', type: 'success' }), 2000);
      setMessage({
        type: 'error',
        text: __('The blacklist table is already empty.', 'loginpress-pro')
      });
      setTimeout(function(){
        setMessage({text: "", type: ""})
      }, 2000);
      return;
    }
    setShowClearAllBlacklistPopup(true);
  };

  const renderSettingsTab = () => (
    <div className="loginpress-limit-login-settings">
      <SettingField
        name="attempts_allowed"
        label={__('Attempts Allowed', 'loginpress-pro')}
        type="number"
        value={settings.attempts_allowed}
        onChange={handleSettingChange}
        min={1}
        desc2={__('Allowed Attempts In Numbers (How Many)', 'loginpress-pro')}
      />
  
      <SettingField
        name="minutes_lockout"
        label={__('Lockout Minutes', 'loginpress-pro')}
        type="number"
        value={settings.minutes_lockout}
        onChange={handleSettingChange}
        min={1}
        desc2={__('Lockout Minutes In Numbers (How Many)', 'loginpress-pro')}
      />
  
      {/* <SettingField
        name="lockout_message"
        label={__('Lockout Message', 'loginpress-pro')}
        type="text"
        value={settings.lockout_message}
        onChange={handleSettingChange}
        desc2={__('Message for user(s) after reaching maximum login attempts.', 'loginpress-pro')}
      /> */}
  
      <div className="loginpress-setting-field loginpress-setting-field-text">
        <label className="loginpress-setting-label">
          {__('IP Address', 'loginpress-pro')}
        </label>
		<div className="loginpress-ip-input-wrapper">
			
          <input
            type="text"
            value={ipAddress}
            onChange={(e) => setIpAddress(e.target.value)}
          />
		  <div className="loginpress-ip-buttons">
			<button
				className='add_white_list whitelist-btn'
				onClick={() => handleAddToList('whitelist')}
				disabled={!ipAddress || isLoading}
			>
				{__('Whitelist', 'loginpress-pro')}
			</button>
			<button
				className='blacklist-btn add_black_list'
				onClick={() => handleAddToList('blacklist')}
				disabled={!ipAddress || isLoading}
			>
				{__('Blacklist', 'loginpress-pro')}
			</button>
			</div>
          {userMessage.text && (
			<div className={`message ${userMessage.type}`}>
				<p>{userMessage.text}</p>
				<span className='close-me' onClick={() => (setMessage({text: '', type: ''}))}><i className="dashicons dashicons-no-alt"></i></span>
			</div>
        )}
		</div>
        
      </div>
      <SettingField
        name="username_blacklisted"
        label={__('Username Auto Blacklist', 'loginpress-pro')}
        type="tags"
        value={settings.username_blacklisted || ''}
        ignoreValue={true}
        onChange={(name, value) => handleSettingChange(name, value)}
        desc2={__(
          'Enter usernames you want to blacklist. It will add the IP address of the user to the blacklist. (*) can be used as a wildcard. e.g: admin*',
          'loginpress-pro'
        )}
      />
      
      <SettingField
        name="disable_xml_rpc_request"
        label={__('Disable XML RPC Request', 'loginpress-pro')}
        type="checkbox"
        value={settings.disable_xml_rpc_request === 'on'}
        onChange={(name, value) => handleSettingChange(name, value ? 'on' : 'off')}
        desc={__('The XMLRPC is a system that allows remote updates to WordPress from other applications.', 'loginpress-pro')}
      />
      {/* <SettingField
        name="ip_intelligence"
        label={__('IP Intelligence', 'loginpress-pro')}
        type="checkbox"
        value={settings.ip_intelligence === 'on'}
        onChange={(name, value) => handleSettingChange(name, value ? 'on' : 'off')}
        desc={__('Enable IP Intelligence to detect brute force attacks and block suspicious IP addresses.', 'loginpress-pro')}
      /> */}

      <SettingField
        type="checkbox"
        name="limit_concurrent_sessions"
        label={__('Limit Concurrent Sessions', 'loginpress-pro')}
        value={settings.limit_concurrent_sessions === 'on'}
        onChange={(name, value) => handleSettingChange(name, value ? 'on' : 'off')}
        desc={__('Restrict users to a maximum number of logins at the same time.', 'loginpress-pro')}
      />

    {settings.limit_concurrent_sessions === 'on' && (
        <>
            <SettingField
                type="number"
                name="max_session_count"
                label={__('Max Session Count', 'loginpress-pro')}
                value={settings.max_session_count}
                onChange={handleSettingChange}
                min="0"
                max="10"
                step="1"
                desc2={__('Specify the maximum number of allowed concurrent sessions per user.', 'loginpress-pro')}
                placeholder="5"
            />

            <SettingField
                type="multicheck"
                name="exclude_roles"
                label={__('Exclude Roles', 'loginpress-pro')}
                value={
                    settings.exclude_roles
                        ? Object.keys(settings.exclude_roles)
                        : []
                }
                onChange={(name, value) => {
                    const objValue = value.reduce((acc, role) => {
                        acc[role] = userRoles[role]?.name || role;
                        return acc;
                    }, {});
                    handleSettingChange(name, objValue);
                }}
                options={userRolesOptions}
                desc2={__('Choose the roles for exclusion from limit concurrent sessions.', 'loginpress-pro')}
            />
        </>
    )}
  

      <SettingField
        name="disable_user_rest_endpoint"
        label={__('Disable User Endpoints', 'loginpress-pro')}
        type="checkbox"
        value={settings.disable_user_rest_endpoint === 'on'}
        onChange={(name, value) => handleSettingChange(name, value ? 'on' : 'off')}
        desc={__('Blocks public access to WordPress REST API endpoints that expose user information.', 'loginpress-pro')}
      />
      <SettingField
        name="disable_app_rest_endpoint"
        label={__('Disable App Login Endpoints', 'loginpress-pro')}
        type="checkbox"
        value={settings.disable_app_rest_endpoint === 'on'}
        onChange={(name, value) => handleSettingChange(name, value ? 'on' : 'off')}
        desc={__('Blocks unauthorized logins through app login REST API endpoints.', 'loginpress-pro')}
      />
      <SettingField
        name="disable_app_pass_rest_endpoint"
        label={__('Disable Application Password', 'loginpress-pro')}
        type="checkbox"
        value={settings.disable_app_pass_rest_endpoint === 'on'}
        onChange={(name, value) => handleSettingChange(name, value ? 'on' : 'off')}
        desc={__('Disable authentication using application passwords via XML-RPC or REST API.', 'loginpress-pro')}
      />
  
      <div className="loginpress-form-group">
        <button
            type="submit"
            className="button button-primary"
          onClick={saveSettings}
          disabled={isLoading}
        >
          {isLoading ? __('Saving...', 'loginpress-pro') : __('Save Changes', 'loginpress-pro')}
        </button>
		{message.text && (
			<div className={`message ${message.type}`}>
			<p>{message.text}</p>
			<span className='close-me' onClick={() => (setMessage({text: '', type: ''}))}><i className="dashicons dashicons-no-alt"></i></span>
			</div>
		)}
      </div>
    </div>
  );
  
  const renderLogsTab = () => (
    <>
      <LoginAttemptsTable 
        data={paginatedLogs}
        onSort={handleSort}
        sortBy={sortBy}
        sortOrder={sortOrder}
        isLoading={isLoading}
        selectedRows={selectedLogs}
        onSelectRow={handleSelectRow}
        onSelectAll={handleSelectAll}
        onBulkAction={handleBulkAction}
        onClearAll={showClearAllPopupHandler}
        onAction={handleAction}
        perPage={perPage}
        onPerPageChange={(value) => {
          setPerPage(value);
          setCurrentPage(1);
        }}
        searchTerm={searchTerm}
        onSearchChange={setSearchTerm}
        statusFilter={statusFilter}
        onStatusFilterChange={setStatusFilter}
        message={message}
      />
	  <div className="loginpress-pagination-wrapper">
	  <div className="showing-of-entries">
		{searchTerm && searchTerm.trim() !== '' 
			? sprintf(__('Showing %1$d to %2$d of %3$d entries (filtered from %4$d total entries)', 'loginpress-pro'), paginatedLogs.length, paginatedLogs.length, paginatedLogs.length, logs.length)
			: sprintf(__('Showing %1$d to %2$d of %3$d entries', 'loginpress-pro'), paginatedLogs.length, paginatedLogs.length, logs.length)
		}
</div>

      <PaginationControls
        currentPage={currentPage}
        totalPages={totalLogPages}
        onPageChange={setCurrentPage}
      />
	  </div>
    </>
		
  );

  const renderNotificationsTab = () => (
    <div className="loginpress-limit-login-notifications">
      <h3>{__('Error Messages', 'loginpress-pro')}</h3>
      
      <SettingField
        name="blacklist_message"
        label={__('Blacklisted IP Message', 'loginpress-pro')}
        type="text"
        value={settings.blacklist_message}
        onChange={handleSettingChange}
        placeholder={__('Your IP has been blacklisted', 'loginpress-pro')}
        desc2={__('Message shown when a blacklisted IP tries to login', 'loginpress-pro')}
      />
  
      <SettingField
        name="attempts_left_message"
        label={__('Attempts Left Message', 'loginpress-pro')}
        type="text"
        value={settings.attempts_left_message}
        onChange={handleSettingChange}
        placeholder={__('You have only %count% attempts', 'loginpress-pro')}
        desc2={__('Message shown when login attempts remain. Use %count% for attempts count.', 'loginpress-pro')}
      />
  
      <SettingField
        name="lockout_message"
        label={__('Lockout Message', 'loginpress-pro')}
        type="text"
        value={settings.lockout_message}
        onChange={handleSettingChange}
        desc2={__('Message for user(s) after reaching maximum login attempts. Use %TIME% for time remaining.', 'loginpress-pro')}
        placeholder={__('You are locked out for %TIME%', 'loginpress-pro')}
      />
  
      {/* Email Notifications Section */}
      <div className="email-notifications-section">
        <h3>{__('Email Notifications', 'loginpress-pro')}</h3>

        <SettingField
          type="checkbox"
          name="enable_lockout_notification"
          label={__('Enable Lockout Notification', 'loginpress-pro')}
          value={settings.enable_lockout_notification === 'on'}
          onChange={(name, value) => handleSettingChange(name, value ? 'on' : 'off')}
          desc2={__('If enabled, a lockout notification will be sent to the specified email address.', 'loginpress-pro')}
        />

        {settings.enable_lockout_notification === 'on' && (
          <>
            <SettingField
              name="notification_email"
              label={__('Email Address(es)', 'loginpress-pro')}
              type="tags"
              value={settings.notification_email || ''}
              onChange={handleSettingChange}
              placeholder="admin@example.com"
              tagsSeparator=", "
              tagType="email"
              desc2={__('Press Enter to add email address(es) to receive notifications.', 'loginpress-pro')}
            />

            <SettingField
              name="notification_subject"
              label={__('Email Subject', 'loginpress-pro')}
              type="text"
              value={settings.notification_subject}
              onChange={handleSettingChange}
              placeholder="%username% is locked out at %sitename%"
              descdiv={
                <>
                  {__('Available variables:', 'loginpress-pro')}
                  <ul className="variable-list">
                    <li><code>%sitename%</code> - {__('Site Name', 'loginpress-pro')}</li>
                    <li><code>%username%</code> - {__('User Name', 'loginpress-pro')}</li>
                  </ul>
                </>
              }
            />

              <SettingField
                name='notification_body'
                label={__('Email Body', 'loginpress-pro')}
                className="loginpress-textarea"
                type='textarea'
                value={settings.notification_body}
                onChange={handleSettingChange}
                placeholder={sprintf(__('Hello,\n\nAt your site %1$s, a user: %2$s was recently locked out from the IP: %3$s\non %4$s while trying to log in through %5$s.\n\nPlease visit your dashboard to unlock this user or add them to the blacklist.', 'loginpress-pro'), '%sitename%', '%username%', '%ip%', '%date%', '%gateway%')}
                rows={8}
                maxLength={500}
                descdiv={
                  <>
                    {__('Available variables:', 'loginpress-pro')}
                    <ul className="variable-list">
                      <li><code>%sitename%</code> - {__('Site Name', 'loginpress-pro')}</li>
                      <li><code>%username%</code> - {__('User Name', 'loginpress-pro')}</li>
                      <li><code>%date%</code> - {__('Lockout Time and Date', 'loginpress-pro')}</li>
                      <li><code>%ip%</code> - {__('Device IP Address', 'loginpress-pro')}</li>
                      <li><code>%gateway%</code> - {__('Login Gateway (wp-login, WooCommerce, etc.)', 'loginpress-pro')}</li>
                    </ul>
                  </>
                }
              />
          </>
        )}

        <div className="loginpress-form-group">
          <button
            type="submit"
            className="button button-primary"
            onClick={saveSettings}
            disabled={isLoading}
          >
            {isLoading ? __('Saving...', 'loginpress-pro') : __('Save Changes', 'loginpress-pro')}
          </button>
		  {message.text && (
        <div className={`message ${message.type}`}>
          <p>{message.text}</p>
		  <i className="dashicons dashicons-no-alt"></i>
        </div>
      )}
        </div>
      </div>
    </div>
  );
  
  const renderWhitelistTab = () => (
    <>
      <ListTable 
        data={paginatedWhitelist}
        isLoading={isLoading}
        listType="whitelist"
        onRemove={(ip) => handleRemoveFromList('whitelist', ip)}
        onClearAll={showClearAllWhitelistPopupHandler}
        perPage={perPage}
        onPerPageChange={(value) => {
          setPerPage(value);
          setCurrentWhitelistPage(1);
        }}
        searchTerm={searchTerm}
        onSearchChange={setSearchTerm}
        onSort={handleWhitelistSort}
        sortBy={whitelistSortBy}
        sortOrder={whitelistSortOrder}
        totalEntries={sortedFilteredWhitelist.length}
        totalUnfilteredEntries={whitelist.length}
        currentPage={currentWhitelistPage}
        message={message}
      />
      <PaginationControls
        currentPage={currentWhitelistPage}
        totalPages={totalWhitelistPages}
        onPageChange={setCurrentWhitelistPage}
      />
    </>

  );
  
  const renderBlacklistTab = () => (
    <>
      <ListTable 
        data={paginatedBlacklist}
        isLoading={isLoading}
        listType="blacklist"
        onRemove={(ip) => handleRemoveFromList('blacklist', ip)}
        onClearAll={showClearAllBlacklistPopupHandler}
        perPage={perPage}
        onPerPageChange={(value) => {
          setPerPage(value);
          setCurrentBlacklistPage(1);
        }}
        searchTerm={searchTerm}
        onSearchChange={setSearchTerm}
        onSort={handleBlacklistSort}
        sortBy={blacklistSortBy}
        sortOrder={blacklistSortOrder}
        totalEntries={sortedFilteredBlacklist.length}
        totalUnfilteredEntries={blacklist.length}
        currentPage={currentBlacklistPage}
        message={message}
      />
      <PaginationControls
        currentPage={currentBlacklistPage}
        totalPages={totalBlacklistPages}
        onPageChange={setCurrentBlacklistPage}
      />
    </>
  );

  return (
    <>
    <InfoBox  
      heading={__('Limit Login Attempts', 'loginpress-pro')}
      description={
        __(
          'The Limit Login Attempts add-on helps you easily keep track of how many times each user tries to log in and limits the number of attempts they can make. This way, your website is protected from brute force attacks, when hackers try lots of passwords to get in.',
          'loginpress-pro'
        )
      }
      videoUrl="https://www.youtube.com/watch?v=1-L14gHC8R0"
	  className="loginpress-less-padding"
    />
    <div className="loginpress-limit-login-attempts">
      <div className="loginpress-limit-login-tabs">
        <button
          className={`button ${activeTab === 'settings' ? 'button-primary' : ''}`}
          onClick={() => handleTabChange('settings')}
        >
          {__('Settings', 'loginpress-pro')}
        </button>
        <button
          className={`button ${activeTab === 'notifications' ? 'button-primary' : ''}`}
          onClick={() => handleTabChange('notifications')}
        >
          {__('Notifications', 'loginpress-pro')}
        </button>
        <button
          className={`button ${activeTab === 'logs' ? 'button-primary loginpress-limit-login-active' : ''}`}
          onClick={() => handleTabChange('logs')}
        >
          {__('Attempt Details', 'loginpress-pro')}
        </button>
        <button
          className={`button ${activeTab === 'whitelist' ? 'button-primary' : ''}`}
          onClick={() => handleTabChange('whitelist')}
        >
          {__('Whitelist', 'loginpress-pro')}
        </button>
        <button
          className={`button ${activeTab === 'blacklist' ? 'button-primary' : ''}`}
          onClick={() => handleTabChange('blacklist')}
        >
          {__('Blacklist', 'loginpress-pro')}
        </button>
      </div>

      <div className="loginpress-limit-login-content">
        {activeTab === 'settings' && renderSettingsTab()}
        {activeTab === 'notifications' && renderNotificationsTab()}
        {activeTab === 'logs' && renderLogsTab()}
        {activeTab === 'whitelist' && renderWhitelistTab()}
        {activeTab === 'blacklist' && renderBlacklistTab()}
      </div>

      {/* Clear All Popups */}
      {showClearAllPopup && (
        <div className="loginpress-edit-attempts-popup-containers llla_remove_all_popup" style={{display: 'block'}}>
          <div className="loginpress-edit-overlay" onClick={() => setShowClearAllPopup(false)}></div>
          <div className="loginpress-edit-popup loginpress-link-duration-popup loginpress-llla-popup">
            <div className="llla_popup_heading">
              <img src={`${loginpress_llla.img_url}`} alt="Confirm" />
              <h3>{__('Are you sure to delete all the entries?', 'loginpress-pro')}</h3>
            </div>
            <div className="loginpress-llla-duration-buttons">
              <button 
                className="button button-primary"
                onClick={() => {
                  handleClearAll('logs');
                  setShowClearAllPopup(false);
                }}
              >
                {__('Yes', 'loginpress-pro')}
              </button>
              <button 
                className="button button-primary"
                onClick={() => setShowClearAllPopup(false)}
              >
                {__('No', 'loginpress-pro')}
              </button>
            </div>
          </div>
        </div>
      )}

      {showClearAllWhitelistPopup && (
        <div className="loginpress-edit-white-popup-containers llla_remove_all_popup" style={{display: 'block'}}>
          <div className="loginpress-edit-overlay" onClick={() => setShowClearAllWhitelistPopup(false)}></div>
          <div className="loginpress-edit-popup loginpress-link-duration-popup loginpress-llla-popup">
            <div className="llla_popup_heading">
              <img src={`${loginpress_llla.img_url}`} alt="Confirm" />
              <h3>{__('Are you sure to delete all the entries?', 'loginpress-pro')}</h3>
            </div>
            <div className="loginpress-llla-duration-buttons">
              <button 
                className="button button-primary"
                onClick={() => {
                  handleClearAll('whitelist');
                  setShowClearAllWhitelistPopup(false);
                }}
              >
                {__('Yes', 'loginpress-pro')}
              </button>
              <button 
                className="button button-primary"
                onClick={() => setShowClearAllWhitelistPopup(false)}
              >
                {__('No', 'loginpress-pro')}
              </button>
            </div>
          </div>
        </div>
      )}

      {showClearAllBlacklistPopup && (
        <div className="loginpress-edit-black-popup-containers llla_remove_all_popup" style={{display: 'block'}}>
          <div className="loginpress-edit-overlay" onClick={() => setShowClearAllBlacklistPopup(false)}></div>
          <div className="loginpress-edit-popup loginpress-link-duration-popup loginpress-llla-popup">
            <div className="llla_popup_heading">
              <img src={`${loginpress_llla.img_url}`} alt="Confirm" />
              <h3>{__('Are you sure to delete all the entries?', 'loginpress-pro')}</h3>
            </div>
            <div className="loginpress-llla-duration-buttons">
              <button 
                className="button button-primary"
                onClick={() => {
                  handleClearAll('blacklist');
                  setShowClearAllBlacklistPopup(false);
                }}
              >
                {__('Yes', 'loginpress-pro')}
              </button>
              <button 
                className="button button-primary"
                onClick={() => setShowClearAllBlacklistPopup(false)}
              >
                {__('No', 'loginpress-pro')}
              </button>
            </div>
          </div>
        </div>
      )}
    
    
	</div>
    </>
  );
};

export default LimitLoginSettings;